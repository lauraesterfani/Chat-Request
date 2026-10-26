<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Campus;
use App\Models\Document;
use App\Models\Request as RequestModel;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use App\Services\RequestAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffAccessScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_grants_and_revokes_a_scoped_request_access(): void
    {
        $course = Course::create(['name' => 'Curso QA', 'code' => 'QA']);
        $student = User::create(['name' => 'Aluno', 'email' => 'student@example.test', 'cpf' => '90000002001', 'phone' => '81900002001', 'matricula' => 'QA02001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Tipo QA']);
        $request = RequestModel::create(['user_id' => $student->id, 'course_id' => $course->id, 'type_id' => $type->id, 'subject' => 'A', 'description' => 'B', 'status' => 'pending', 'protocol' => '20260905-123456', 'responsible_sector' => 'CRADT']);
        $admin = $this->staff('admin');
        $agent = $this->staff('cradt');

        $created = $this->actingAs($admin, 'staff_admins')->postJson('/api/staff-access-scopes', ['staff_admin_id' => $agent->id, 'course_id' => $course->id, 'sector' => 'CRADT', 'abilities' => ['view_request'], 'starts_at' => now()->toIso8601String(), 'reason' => 'Cobertura temporária'])->assertCreated()->json();
        $this->assertTrue(app(RequestAccessService::class)->canView($agent, $request));
        $this->assertFalse(app(RequestAccessService::class)->canOperate($agent, $request, 'forward_request'));
        $this->assertDatabaseHas('audit_records', ['action' => 'scope_granted', 'resource_id' => (string) $created['id']]);
        $this->actingAs($admin, 'staff_admins')->deleteJson('/api/staff-access-scopes/'.$created['id'])->assertOk();
        $this->assertDatabaseHas('audit_records', ['action' => 'scope_revoked', 'resource_id' => (string) $created['id']]);
        $this->assertDatabaseHas('staff_access_scopes', ['id' => $created['id'], 'revoked_by' => $admin->id]);
        $this->assertFalse(app(RequestAccessService::class)->canView($agent, $request));
    }

    public function test_expired_scope_does_not_grant_access(): void
    {
        $staff = $this->staff('cradt');
        $course = Course::create(['name' => 'Curso expirado', 'code' => 'QAE']);
        StaffAccessScope::create(['staff_admin_id' => $staff->id, 'course_id' => $course->id, 'abilities' => ['view_request'], 'starts_at' => now()->subDay(), 'expires_at' => now()->subMinute(), 'reason' => 'Expirado']);
        $this->assertFalse($staff->accessScopes()->first()->active());

        $student = User::create(['name' => 'Aluno', 'email' => 'expired@example.test', 'cpf' => '90000002099', 'phone' => '81900002099', 'matricula' => 'QAE02099', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Tipo expirado']);
        $request = RequestModel::create(['user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'A', 'description' => 'B', 'status' => 'pending', 'protocol' => '20261026-123456', 'course_id' => $course->id]);
        $this->assertFalse(app(RequestAccessService::class)->canView($staff, $request));
    }

    public function test_only_operational_administration_can_list_audit_records(): void
    {
        $admin = $this->staff('admin');
        $technical = $this->staff('staff');
        $this->actingAs($admin, 'staff_admins')->getJson('/api/audit-records')->assertOk();
        $this->actingAs($technical, 'staff_admins')->getJson('/api/audit-records')->assertForbidden();
    }

    public function test_audit_metadata_excludes_sensitive_keys(): void
    {
        $admin = $this->staff('admin');
        $course = Course::create(['name' => 'Curso audit', 'code' => 'QAA']);
        $this->actingAs($admin, 'staff_admins')->postJson('/api/staff-access-scopes', ['staff_admin_id' => $this->staff('cradt')->id, 'course_id' => $course->id, 'abilities' => ['view_request'], 'starts_at' => now()->toIso8601String(), 'reason' => 'Teste'])->assertCreated();
        $metadata = \App\Models\AuditRecord::latest('created_at')->value('metadata');
        $this->assertArrayNotHasKey('password', $metadata ?? []);
        $this->assertArrayNotHasKey('token', $metadata ?? []);
    }

    public function test_scoped_staff_list_and_queue_only_include_authorized_course_and_sector(): void
    {
        $allowedCourse = Course::create(['name' => 'Curso permitido', 'code' => 'QAP']);
        $otherCourse = Course::create(['name' => 'Outro curso', 'code' => 'QAO']);
        $allowed = $this->requestForCourse($allowedCourse, 'CRADT');
        $otherCourseRequest = $this->requestForCourse($otherCourse, 'CRADT');
        $otherSectorRequest = $this->requestForCourse($allowedCourse, 'COORDENACAO');
        $agent = $this->staff('cradt');
        $admin = $this->staff('admin');
        StaffAccessScope::create([
            'staff_admin_id' => $agent->id,
            'course_id' => $allowedCourse->id,
            'sector' => 'CRADT',
            'abilities' => ['view_request'],
            'starts_at' => now()->subMinute(),
            'reason' => 'Escopo de prova',
        ]);

        $this->actingAs($agent, 'staff_admins')->getJson('/api/admin/requests')
            ->assertOk()->assertJsonCount(1)->assertJsonFragment(['id' => $allowed->id])
            ->assertJsonMissing(['id' => $otherCourseRequest->id])->assertJsonMissing(['id' => $otherSectorRequest->id]);
        $this->actingAs($agent, 'staff_admins')->getJson('/api/admin/queue')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonFragment(['id' => $allowed->id])
            ->assertJsonMissing(['id' => $otherCourseRequest->id])->assertJsonMissing(['id' => $otherSectorRequest->id]);
        $this->actingAs($admin, 'staff_admins')->getJson('/api/admin/requests')->assertOk()->assertJsonCount(3);
    }

    public function test_scope_grant_rejects_unbounded_scope_self_escalation_and_role_escalation(): void
    {
        $admin = $this->staff('admin');
        $agent = $this->staff('cradt');
        $this->actingAs($admin, 'staff_admins')->postJson('/api/staff-access-scopes', [
            'staff_admin_id' => $agent->id, 'abilities' => ['view_request'], 'reason' => 'Sem limite',
        ])->assertUnprocessable();
        $boundedCourse = Course::create(['name' => 'Curso sem setor de template', 'code' => 'QAT']);
        $this->postJson('/api/staff-access-scopes', [
            'staff_admin_id' => $agent->id, 'course_id' => $boundedCourse->id,
            'abilities' => ['use_templates'], 'starts_at' => now()->toIso8601String(), 'reason' => 'Template sem setor',
        ])->assertUnprocessable()->assertJsonValidationErrors('sector');
        $this->postJson('/api/staff-access-scopes', [
            'staff_admin_id' => $admin->id, 'sector' => 'CRADT', 'abilities' => ['view_request'], 'reason' => 'Autoconcessão',
        ])->assertUnprocessable();
        $this->postJson('/api/staff-access-scopes', [
            'staff_admin_id' => $this->staff('staff')->id, 'sector' => 'CRADT', 'abilities' => ['view_request'], 'reason' => 'TI',
        ])->assertUnprocessable();
        $this->assertDatabaseCount('staff_access_scopes', 0);
    }

    public function test_future_and_revoked_scopes_do_not_authorize_access(): void
    {
        $course = Course::create(['name' => 'Curso futuro', 'code' => 'QAF']);
        $request = $this->requestForCourse($course, 'CRADT');
        $agent = $this->staff('cradt');
        $future = StaffAccessScope::create([
            'staff_admin_id' => $agent->id,
            'course_id' => $course->id,
            'abilities' => ['view_request'],
            'starts_at' => now()->addDay(),
            'reason' => 'Acesso futuro',
        ]);
        $this->assertFalse(app(RequestAccessService::class)->canView($agent, $request));
        $future->update(['starts_at' => now()->subMinute(), 'revoked_at' => now(), 'revoked_by' => $this->staff('admin')->id]);
        $this->assertFalse(app(RequestAccessService::class)->canView($agent, $request));
    }

    public function test_scope_api_preserves_the_explicit_iso8601_start_time(): void
    {
        $admin = $this->staff('admin');
        $agent = $this->staff('cradt');
        $course = Course::create(['name' => 'Curso agendado', 'code' => 'QAS']);
        $scheduledStart = now()->addMinutes(30)->setMicrosecond(0);

        $scope = $this->actingAs($admin, 'staff_admins')->postJson('/api/staff-access-scopes', [
            'staff_admin_id' => $agent->id,
            'course_id' => $course->id,
            'sector' => 'CRADT',
            'abilities' => ['view_request'],
            'starts_at' => $scheduledStart->toIso8601String(),
            'reason' => 'Concessão agendada.',
        ])->assertCreated()->json();

        $this->assertTrue(\Illuminate\Support\Carbon::parse($scope['starts_at'])->equalTo($scheduledStart));
        $this->actingAs($admin, 'staff_admins')->postJson('/api/staff-access-scopes', [
            'staff_admin_id' => $this->staff('cradt')->id,
            'course_id' => $course->id,
            'sector' => 'CRADT',
            'abilities' => ['view_request'],
            'reason' => 'Sem horário obrigatório.',
        ])->assertUnprocessable()->assertJsonValidationErrors('starts_at');
    }

    public function test_campus_scope_is_checked_against_the_request_snapshot(): void
    {
        $campusA = Campus::create(['name' => 'Campus A', 'code' => 'CPA']);
        $campusB = Campus::create(['name' => 'Campus B', 'code' => 'CPB']);
        $courseA = Course::create(['name' => 'Curso A', 'code' => 'CA', 'campus_id' => $campusA->id]);
        $courseB = Course::create(['name' => 'Curso B', 'code' => 'CB', 'campus_id' => $campusB->id]);
        $requestA = $this->requestForCourse($courseA, 'CRADT');
        $requestA->update(['campus_id' => $campusA->id]);
        $requestB = $this->requestForCourse($courseB, 'CRADT');
        $requestB->update(['campus_id' => $campusB->id]);
        $agent = $this->staff('cradt');
        $admin = $this->staff('admin');

        $this->actingAs($admin, 'staff_admins')->postJson('/api/staff-access-scopes', [
            'staff_admin_id' => $agent->id,
            'campus_id' => $campusA->id,
            'sector' => 'CRADT',
            'abilities' => ['view_request', 'reply_request'],
            'starts_at' => now()->toIso8601String(),
            'reason' => 'Cobertura do Campus A',
        ])->assertCreated();

        $this->assertTrue(app(RequestAccessService::class)->canView($agent, $requestA));
        $this->assertFalse(app(RequestAccessService::class)->canView($agent, $requestB));
        $this->assertSame(['view_request', 'reply_request'], app(RequestAccessService::class)->capabilitiesFor($agent, $requestA));
        $this->assertSame([], app(RequestAccessService::class)->capabilitiesFor($agent, $requestB));
        $this->actingAs($agent, 'staff_admins')->getJson('/api/admin/requests')
            ->assertOk()->assertJsonCount(1)->assertJsonFragment(['id' => $requestA->id])->assertJsonMissing(['id' => $requestB->id]);
        $this->getJson('/api/admin/queue')->assertOk()->assertJsonPath('total', 1)->assertJsonFragment(['id' => $requestA->id]);
    }

    public function test_different_grants_cannot_combine_an_ability_from_one_scope_with_another_scope(): void
    {
        $courseA = Course::create(['name' => 'Curso capacidade A', 'code' => 'CAA']);
        $courseB = Course::create(['name' => 'Curso capacidade B', 'code' => 'CAB']);
        $requestA = $this->requestForCourse($courseA, 'CRADT');
        $requestB = $this->requestForCourse($courseB, 'CRADT');
        $agent = $this->staff('cradt');
        StaffAccessScope::create(['staff_admin_id' => $agent->id, 'course_id' => $courseA->id, 'abilities' => ['view_request'], 'starts_at' => now()->subMinute(), 'reason' => 'Consulta A']);
        StaffAccessScope::create(['staff_admin_id' => $agent->id, 'course_id' => $courseB->id, 'abilities' => ['reply_request'], 'starts_at' => now()->subMinute(), 'reason' => 'Resposta B']);

        $this->assertTrue(app(RequestAccessService::class)->canView($agent, $requestA));
        $this->assertFalse(app(RequestAccessService::class)->canReply($agent, $requestA));
        $this->assertSame(['view_request'], app(RequestAccessService::class)->capabilitiesFor($agent, $requestA));
        $this->assertFalse(app(RequestAccessService::class)->canView($agent, $requestB));
    }

    public function test_cradt_cannot_manage_access_grants(): void
    {
        $cradt = $this->staff('cradt');
        $this->actingAs($cradt, 'staff_admins')->getJson('/api/staff-access-scopes')->assertForbidden();
        $this->actingAs($cradt, 'staff_admins')->getJson('/api/staff-access-scopes/options')->assertForbidden();
    }

    public function test_legacy_request_without_course_snapshot_is_not_reassigned_from_current_student_profile(): void
    {
        $course = Course::create(['name' => 'Curso atual', 'code' => 'QAC']);
        $student = User::create(['name' => 'Aluno legado', 'email' => 'legacy@example.test', 'cpf' => '90000002009', 'phone' => '81900002009', 'matricula' => 'QA02009', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Tipo legado']);
        $legacy = RequestModel::create(['user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'A', 'description' => 'B', 'status' => 'pending', 'protocol' => '20261026-000009', 'responsible_sector' => 'CRADT']);
        $coordinator = $this->staff('coordenacao');
        $coordinator->update(['course_id' => $course->id]);
        $this->assertFalse(app(RequestAccessService::class)->canView($coordinator, $legacy));
        $this->actingAs($coordinator, 'staff_admins')->getJson('/api/requests')->assertOk()->assertJsonCount(0);
    }

    public function test_unscoped_coordinator_is_limited_to_their_course_and_coordination_queue(): void
    {
        $course = Course::create(['name' => 'Curso coordenação', 'code' => 'COORD-1']);
        $otherCourse = Course::create(['name' => 'Outro curso', 'code' => 'COORD-2']);
        $coordinationRequest = $this->requestForCourse($course, 'COORDENACAO');
        $cradtRequest = $this->requestForCourse($course, 'CRADT');
        $otherCourseRequest = $this->requestForCourse($otherCourse, 'COORDENACAO');
        $coordinator = $this->staff('coordenacao');
        $coordinator->update(['course_id' => $course->id]);

        $this->actingAs($coordinator, 'staff_admins')->getJson('/api/requests')
            ->assertOk()->assertJsonCount(1)->assertJsonFragment(['id' => $coordinationRequest->id])
            ->assertJsonMissing(['id' => $cradtRequest->id])->assertJsonMissing(['id' => $otherCourseRequest->id]);
        $this->getJson('/api/requests/'.$coordinationRequest->id)->assertOk();
        $this->getJson('/api/requests/'.$cradtRequest->id)->assertForbidden();
    }

    public function test_document_review_requires_its_own_scoped_capability(): void
    {
        Storage::fake('local');
        $course = Course::create(['name' => 'Curso de revisão', 'code' => 'DOC-1']);
        $request = $this->requestForCourse($course, 'CRADT');
        $document = Document::create([
            'path' => 'documents/private-review.png',
            'name' => 'atestado.png',
            'mime_type' => 'image/png',
            'user_id' => $request->user_id,
            'file_size' => 12,
        ]);
        Storage::disk('local')->put($document->path, 'private-bytes');
        $request->documents()->attach($document->id);
        $agent = $this->staff('cradt');

        StaffAccessScope::create([
            'staff_admin_id' => $agent->id,
            'course_id' => $course->id,
            'sector' => 'CRADT',
            'abilities' => ['view_request'],
            'starts_at' => now()->subMinute(),
            'reason' => 'Apenas acompanhamento.',
        ]);
        $this->actingAs($agent, 'staff_admins')->getJson('/api/requests/'.$request->id)
            ->assertOk()->assertJsonPath('can_review_documents', false)->assertJsonCount(0, 'documents');
        $this->get('/api/requests/'.$request->id.'/documents/'.$document->id)->assertForbidden();

        StaffAccessScope::create([
            'staff_admin_id' => $agent->id,
            'course_id' => $course->id,
            'sector' => 'CRADT',
            'abilities' => ['view_request', 'review_document'],
            'starts_at' => now()->subMinute(),
            'reason' => 'Revisão de documentos autorizada.',
        ]);
        $this->getJson('/api/requests/'.$request->id)
            ->assertOk()->assertJsonPath('can_review_documents', true)->assertJsonCount(1, 'documents');
        $this->get('/api/requests/'.$request->id.'/documents/'.$document->id)->assertOk()->assertHeader('content-type', 'image/png');
    }

    private function requestForCourse(Course $course, string $sector): RequestModel
    {
        static $sequence = 0;
        $sequence++;
        $student = User::create([
            'name' => 'Aluno '.$sequence,
            'email' => 'student'.$sequence.'@example.test',
            'cpf' => str_pad((string) (90000002000 + $sequence), 11, '0', STR_PAD_LEFT),
            'phone' => '8190000200'.$sequence,
            'matricula' => 'QA0200'.$sequence,
            'birthday' => '2000-01-01',
            'password' => 'secret',
            'role' => 'student',
            'course_id' => $course->id,
        ]);
        $type = TypeRequest::create(['name' => 'Tipo '.$sequence]);

        return RequestModel::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'type_id' => $type->id,
            'subject' => 'Pedido '.$sequence,
            'description' => 'Descrição',
            'status' => 'pending',
            'protocol' => '20261026-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'responsible_sector' => $sector,
        ]);
    }

    private function staff(string $role): StaffAdmin
    {
        return StaffAdmin::create(['name' => $role, 'email' => uniqid()."@$role.test", 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'role' => $role, 'password' => 'secret', 'must_change_password' => false]);
    }
}
