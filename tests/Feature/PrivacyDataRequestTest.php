<?php

namespace Tests\Feature;

use App\Models\AuditRecord;
use App\Models\Course;
use App\Models\PrivacyDataRequest;
use App\Models\Request as RequestModel;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrivacyDataRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_create_and_list_only_their_own_privacy_requests_with_unique_protocols(): void
    {
        $owner = $this->student('owner');
        $other = $this->student('other');

        $this->postJson('/api/privacy/data-requests', [
            'request_type' => 'access',
            'description' => 'Quero consultar os dados associados à minha conta.',
        ])->assertUnauthorized();

        $first = $this->actingAs($owner, 'api')->postJson('/api/privacy/data-requests', [
            'request_type' => 'access',
            'description' => 'Quero consultar os dados associados à minha conta.',
        ])->assertCreated()
            ->assertJsonPath('status', PrivacyDataRequest::STATUS_SUBMITTED)
            ->assertJsonPath('user_id', $owner->id)
            ->json();

        $second = $this->actingAs($owner, 'api')->postJson('/api/privacy/data-requests', [
            'request_type' => 'correction',
            'description' => 'Preciso solicitar análise de uma informação cadastral.',
        ])->assertCreated()->json();

        $this->assertNotSame($first['protocol'], $second['protocol']);
        $this->assertDatabaseCount('privacy_data_requests', 2);
        $this->assertDatabaseHas('audit_records', [
            'action' => 'privacy_data_request_created',
            'actor_type' => 'student',
            'actor_id' => (string) $owner->id,
            'resource_id' => $first['id'],
        ]);

        $this->actingAs($other, 'api')->getJson('/api/privacy/data-requests')
            ->assertOk()->assertJsonPath('total', 0)->assertJsonMissing(['id' => $first['id']]);
        $this->getJson('/api/privacy/data-requests/'.$first['id'])->assertNotFound();

        $this->actingAs($owner, 'api')->getJson('/api/privacy/data-requests')
            ->assertOk()->assertJsonPath('total', 2)->assertJsonFragment(['protocol' => $first['protocol']]);
        $this->getJson('/api/privacy/data-requests/'.$first['id'])->assertOk()->assertJsonPath('id', $first['id']);
        $this->deleteJson('/api/privacy/data-requests/'.$first['id'])->assertStatus(405);
        $this->getJson('/api/privacy/data-requests/export')->assertNotFound();
    }

    public function test_student_cannot_set_status_protocol_owner_decision_or_attach_files(): void
    {
        $student = $this->student('forged');

        $this->actingAs($student, 'api')->postJson('/api/privacy/data-requests', [
            'request_type' => 'erasure',
            'description' => 'Peço avaliação de uma solicitação relacionada aos meus dados.',
            'status' => 'completed',
            'protocol' => 'PRV-FAKE-0001',
            'user_id' => $this->student('target')->id,
            'responded_by' => 'admin-user-controlled',
            'attachments' => ['private-file-id'],
            'document_ids' => ['private-file-id'],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('privacy_data_requests', 0);
        $this->assertDatabaseCount('audit_records', 0);
    }

    public function test_only_active_unscoped_global_admin_can_process_requests_and_transition_audit_omits_private_text(): void
    {
        $student = $this->student('processing');
        $admin = $this->staff('admin');
        $inactiveAdmin = $this->staff('admin');
        $inactiveAdmin->forceFill(['is_active' => false])->save();
        $cradt = $this->staff('cradt');
        $course = Course::create(['name' => 'Curso privacidade QA', 'code' => 'PRV']);
        $limitedAdmin = $this->staff('admin');
        StaffAccessScope::create([
            'staff_admin_id' => $limitedAdmin->id,
            'course_id' => $course->id,
            'abilities' => ['view_request'],
            'starts_at' => now()->subMinute(),
            'reason' => 'Escopo limitado para teste.',
        ]);

        $created = $this->actingAs($student, 'api')->postJson('/api/privacy/data-requests', [
            'request_type' => 'erasure',
            'description' => 'Peço avaliação da retenção dos dados da minha conta.',
        ])->assertCreated()->json();

        $this->actingAs($cradt, 'staff_admins')->getJson('/api/admin/privacy/data-requests')->assertForbidden();
        $this->actingAs($inactiveAdmin, 'staff_admins')->getJson('/api/admin/privacy/data-requests')->assertForbidden();
        $this->actingAs($admin, 'staff_admins')->getJson('/api/admin/privacy/data-requests/export')->assertNotFound();
        $this->actingAs($admin, 'staff_admins')->getJson('/api/admin/privacy/data-requests')
            ->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/admin/privacy/data-requests/'.$created['id'])->assertOk();
        $this->assertDatabaseHas('audit_records', [
            'action' => 'privacy_data_requests_viewed',
            'actor_id' => (string) $admin->id,
            'outcome' => 'success',
        ]);
        $this->assertDatabaseHas('audit_records', [
            'action' => 'privacy_data_request_viewed',
            'actor_id' => (string) $admin->id,
            'resource_id' => $created['id'],
            'outcome' => 'success',
        ]);
        $this->assertDatabaseHas('audit_records', [
            'action' => 'privacy_data_request_access_denied',
            'actor_id' => (string) $cradt->id,
            'outcome' => 'denied',
        ]);
        $this->actingAs($limitedAdmin, 'staff_admins')->patchJson('/api/admin/privacy/data-requests/'.$created['id'], [
            'status' => 'in_review',
        ])->assertForbidden();

        $this->actingAs($admin, 'staff_admins')->patchJson('/api/admin/privacy/data-requests/'.$created['id'], [
            'status' => 'completed',
            'response_summary' => 'Decisão prematura.',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->patchJson('/api/admin/privacy/data-requests/'.$created['id'], [
            'status' => 'in_review',
            'responded_by' => $student->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('responded_by');

        $this->patchJson('/api/admin/privacy/data-requests/'.$created['id'], [
            'status' => 'in_review',
        ])->assertOk()->assertJsonPath('status', PrivacyDataRequest::STATUS_IN_REVIEW);

        $this->patchJson('/api/admin/privacy/data-requests/'.$created['id'], [
            'status' => 'in_review',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->patchJson('/api/admin/privacy/data-requests/'.$created['id'], [
            'status' => 'rejected',
            'response_summary' => 'Resumo privado para o solicitante.',
        ])->assertUnprocessable()->assertJsonValidationErrors('rejection_reason');

        $this->patchJson('/api/admin/privacy/data-requests/'.$created['id'], [
            'status' => 'rejected',
            'response_summary' => 'Resumo privado para o solicitante.',
            'rejection_reason' => 'A solicitação precisa de informações adicionais.',
        ])->assertOk()
            ->assertJsonPath('status', PrivacyDataRequest::STATUS_REJECTED)
            ->assertJsonPath('responded_by', $admin->id)
            ->assertJsonPath('response_summary', 'Resumo privado para o solicitante.')
            ->assertJsonPath('rejection_reason', 'A solicitação precisa de informações adicionais.');

        $this->patchJson('/api/admin/privacy/data-requests/'.$created['id'], [
            'status' => 'rejected',
            'response_summary' => 'Outra resposta.',
            'rejection_reason' => 'Outra justificativa.',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $record = PrivacyDataRequest::findOrFail($created['id']);
        $this->assertSame($student->id, $record->user_id);
        $this->assertNotNull($record->responded_at);
        $this->assertSame(0, RequestModel::count());
        $this->assertDatabaseHas('audit_records', [
            'action' => 'privacy_data_request_transitioned',
            'actor_type' => 'staff_admin',
            'actor_id' => (string) $admin->id,
            'resource_id' => $created['id'],
        ]);

        $auditRecords = AuditRecord::where('resource_id', $created['id'])->get();
        foreach ($auditRecords as $auditRecord) {
            $serialized = json_encode($auditRecord->metadata);
            $this->assertStringNotContainsString('Resumo privado para o solicitante.', $serialized);
            $this->assertStringNotContainsString('A solicitação precisa de informações adicionais.', $serialized);
            $this->assertNull($auditRecord->ip_address);
        }
        $readEvent = AuditRecord::where('action', 'privacy_data_request_viewed')->where('resource_id', $created['id'])->firstOrFail();
        $this->assertStringNotContainsString('Peço avaliação da retenção dos dados da minha conta.', json_encode($readEvent->metadata));

        $this->actingAs($student, 'api')->getJson('/api/privacy/data-requests/'.$created['id'])
            ->assertOk()
            ->assertJsonPath('response_summary', 'Resumo privado para o solicitante.')
            ->assertJsonPath('rejection_reason', 'A solicitação precisa de informações adicionais.');

        $this->assertDatabaseHas('users', ['id' => $student->id]);
        $this->assertDatabaseCount('privacy_data_requests', 1);
    }

    private function student(string $suffix): User
    {
        static $sequence = 0;
        $sequence++;

        return User::create([
            'name' => 'Aluno '.$suffix,
            'email' => $suffix.'-'.$sequence.'@example.test',
            'cpf' => str_pad((string) (90000006000 + $sequence), 11, '0', STR_PAD_LEFT),
            'phone' => '8190000600'.$sequence,
            'matricula' => 'PRV'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
            'birthday' => '2000-01-01',
            'password' => 'secret',
            'role' => 'student',
        ]);
    }

    private function staff(string $role): StaffAdmin
    {
        static $sequence = 0;
        $sequence++;

        return StaffAdmin::create([
            'name' => 'Staff '.$role.$sequence,
            'email' => $role.'-'.$sequence.'@example.test',
            'cpf' => str_pad((string) (90000007000 + $sequence), 11, '0', STR_PAD_LEFT),
            'role' => $role,
            'password' => Hash::make('Privacy-Test-Password-2026!'),
            'must_change_password' => false,
            'is_active' => true,
        ]);
    }
}
