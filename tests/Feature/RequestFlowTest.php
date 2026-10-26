<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Request as RequestModel;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_student_can_create_a_request_with_a_unique_protocol(): void
    {
        $student = $this->student();
        $type = $this->type();

        $response = $this->actingAs($student, 'api')->postJson('/api/requests', [
            'type_id' => $type->id,
            'subject' => 'Solicitação de teste',
            'description' => 'Descrição do requerimento de teste.',
        ]);

        $response->assertCreated()->assertJsonStructure(['id', 'message']);
        $request = RequestModel::findOrFail($response->json('id'));
        $this->assertMatchesRegularExpression('/^\d{8}-\d{6}$/', $request->protocol);
        $this->assertSame($student->course_id, $request->course_id);
        $this->assertNull($request->enrollment_id);

        $second = $this->actingAs($student, 'api')->postJson('/api/requests', [
            'type_id' => $type->id,
            'subject' => 'Outra solicitação',
            'description' => 'Outra descrição válida.',
        ])->assertCreated();

        $this->assertNotSame($request->protocol, RequestModel::findOrFail($second->json('id'))->protocol);
    }

    public function test_request_creation_rejects_missing_or_unknown_fields(): void
    {
        $student = $this->student();
        $this->actingAs($student, 'api')
            ->postJson('/api/requests', ['subject' => '', 'description' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type_id', 'subject', 'description']);
    }

    public function test_student_cannot_view_another_students_request(): void
    {
        $owner = $this->student();
        $otherStudent = $this->student();
        $request = $this->requestFor($owner);

        $this->actingAs($otherStudent, 'api')
            ->getJson("/api/requests/{$request->id}")
            ->assertForbidden();
    }

    public function test_request_preserves_draft_enrollment_and_course_for_coordination_access(): void
    {
        $student = $this->student();
        $originalCourse = $student->course_id;
        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $originalCourse,
            'enrollment' => 'MATRICULA-FICTICIA-001',
            'status' => 'active',
        ]);
        $type = $this->type();
        $draft = $this->actingAs($student, 'api')->postJson('/api/drafts', [
            'type_request_id' => $type->id,
            'responses' => ['subject' => 'Pedido antes da troca de curso'],
        ])->assertCreated()->json();
        $this->assertDatabaseHas('request_drafts', [
            'id' => $draft['id'], 'course_id' => $originalCourse, 'enrollment_id' => $enrollment->id,
        ]);

        $newCourse = Course::create(['name' => 'Outro curso fictício', 'code' => 'NOVO'.uniqid()]);
        $student->update(['course_id' => $newCourse->id]);
        $created = $this->postJson('/api/requests', [
            'type_id' => $type->id,
            'subject' => 'Pedido antes da troca de curso',
            'description' => 'Descrição de teste',
            'draft_id' => $draft['id'],
        ])->assertCreated();
        $request = RequestModel::findOrFail($created->json('id'));
        $this->assertSame($originalCourse, $request->course_id);
        $this->assertSame($enrollment->id, $request->enrollment_id);
        $request->update(['responsible_sector' => 'COORDENACAO']);
        auth('api')->forgetUser();

        $originalCoordination = StaffAdmin::create(['name' => 'Coordenação original', 'email' => uniqid().'@example.test', 'cpf' => '90000002001', 'role' => 'coordenacao', 'course_id' => $originalCourse, 'password' => 'secret', 'must_change_password' => false]);
        $newCoordination = StaffAdmin::create(['name' => 'Coordenação nova', 'email' => uniqid().'@example.test', 'cpf' => '90000002002', 'role' => 'coordenacao', 'course_id' => $newCourse->id, 'password' => 'secret', 'must_change_password' => false]);
        $this->actingAs($originalCoordination, 'staff_admins')->getJson("/api/requests/{$request->id}")->assertOk();
        $this->getJson('/api/requests')->assertOk()->assertJsonFragment(['id' => $request->id]);
        $this->actingAs($newCoordination, 'staff_admins')->getJson("/api/requests/{$request->id}")->assertForbidden();
        $this->getJson('/api/requests')->assertOk()->assertDontSee($request->id);
    }

    public function test_coordination_is_limited_to_its_course_and_technical_staff_is_denied(): void
    {
        $owner = $this->student();
        $other = $this->student();
        $request = $this->requestFor($owner);
        $coordination = StaffAdmin::create(['name' => 'Coordenação', 'email' => uniqid().'@example.test', 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'role' => 'coordenacao', 'course_id' => $other->course_id, 'password' => 'secret', 'must_change_password' => false]);
        $technical = StaffAdmin::create(['name' => 'TI', 'email' => uniqid().'@example.test', 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'role' => 'staff', 'password' => 'secret', 'must_change_password' => false]);

        $this->actingAs($coordination, 'staff_admins')->getJson("/api/requests/{$request->id}")->assertForbidden();
        $this->actingAs($coordination, 'staff_admins')->getJson("/api/requests/{$request->id}/events")->assertForbidden();
        $this->actingAs($technical, 'staff_admins')->getJson("/api/requests/{$request->id}")->assertForbidden();
        $this->actingAs($technical, 'staff_admins')->getJson("/api/requests/{$request->id}/messages")->assertForbidden();
    }

    public function test_invalid_status_and_student_status_updates_are_rejected(): void
    {
        $student = $this->student();
        $request = $this->requestFor($student);
        $admin = StaffAdmin::create([
            'name' => 'Admin de teste',
            'email' => uniqid().'@example.test',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'role' => 'admin',
            'password' => 'secret',
            'must_change_password' => false,
        ]);

        $this->actingAs($student, 'api')
            ->putJson("/api/requests/{$request->id}", ['status' => 'completed'])
            ->assertForbidden();
        $this->actingAs($admin, 'staff_admins')
            ->putJson("/api/requests/{$request->id}", ['status' => 'not-a-status'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
        $this->putJson("/api/requests/{$request->id}", ['status' => 'completed', 'observation' => 'Tentativa legada'])
            ->assertConflict();
    }

    public function test_staff_decision_observation_is_added_to_chat_history(): void
    {
        $student = $this->student();
        $request = $this->requestFor($student);
        $admin = StaffAdmin::create([
            'name' => 'Admin mensagem', 'email' => uniqid().'@example.test',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'role' => 'admin', 'password' => 'secret', 'must_change_password' => false,
        ]);

        $this->actingAs($admin, 'staff_admins')->postJson("/api/requests/{$request->id}/decision", [
            'result' => 'deferido',
            'justification' => 'Documentação conferida e solicitação regular.',
            'conclusion_summary' => 'Seu requerimento foi deferido.',
        ])->assertOk();

        $this->assertDatabaseHas('messages', [
            'request_id' => $request->id, 'sender_id' => $admin->id,
            'sender_type' => 'staff_admin', 'content' => 'Seu requerimento foi deferido.',
        ]);
        $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'completed', 'result' => 'deferido']);
        $this->actingAs($student, 'api')->getJson("/api/requests/{$request->id}/messages")
            ->assertJsonPath('data.0.content', 'Seu requerimento foi deferido.');
        $this->getJson('/api/notifications')->assertOk()
            ->assertJsonPath('data.0.title', 'Decisão registrada')
            ->assertJsonPath('data.0.body', 'Seu requerimento foi deferido.');
    }

    public function test_authorized_staff_can_forward_and_return_request_with_history(): void
    {
        $student = $this->student();
        $request = $this->requestFor($student);
        $admin = StaffAdmin::create(['name' => 'Admin fluxo', 'email' => uniqid().'@example.test', 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);

        $this->actingAs($admin, 'staff_admins')->postJson("/api/requests/{$request->id}/forward", ['sector' => 'COORDENACAO', 'reason' => 'Análise acadêmica necessária'])->assertOk();
        $this->assertDatabaseHas('requests', ['id' => $request->id, 'responsible_sector' => 'COORDENACAO']);
        $this->actingAs($admin, 'staff_admins')->postJson("/api/requests/{$request->id}/return", ['reason' => 'Parecer concluído'])->assertOk();
        $this->assertDatabaseHas('requests', ['id' => $request->id, 'responsible_sector' => 'CRADT']);
        $this->actingAs($student, 'api')->getJson("/api/requests/{$request->id}/events")->assertOk()->assertJsonCount(2);
    }

    public function test_status_transitions_require_reasons_and_cannot_bypass_final_decision(): void
    {
        $student = $this->student();
        $request = $this->requestFor($student);
        $admin = StaffAdmin::create(['name' => 'Admin status', 'email' => uniqid().'@example.test', 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);

        $this->actingAs($admin, 'staff_admins')->putJson("/api/requests/{$request->id}", ['status' => 'waiting'])->assertConflict();
        $this->putJson("/api/requests/{$request->id}", ['status' => 'analyzing'])->assertOk();
        $this->putJson("/api/requests/{$request->id}", ['status' => 'waiting'])->assertUnprocessable();
        $this->putJson("/api/requests/{$request->id}", ['status' => 'waiting', 'observation' => 'Envie o verso do documento.'])->assertOk();
        $this->putJson("/api/requests/{$request->id}", ['status' => 'canceled'])->assertUnprocessable();
        $this->putJson("/api/requests/{$request->id}", ['status' => 'canceled', 'observation' => 'Solicitação cancelada a pedido do aluno.'])->assertOk();
        $this->putJson("/api/requests/{$request->id}", ['status' => 'analyzing'])->assertConflict();
    }

    public function test_forward_rejects_unknown_sectors_and_return_requires_operational_scope(): void
    {
        $student = $this->student();
        $request = $this->requestFor($student);
        $admin = StaffAdmin::create(['name' => 'Admin setor', 'email' => uniqid().'@example.test', 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        $this->actingAs($admin, 'staff_admins')->postJson("/api/requests/{$request->id}/forward", ['sector' => 'QUALQUER', 'reason' => 'Teste'])->assertUnprocessable();
        $this->postJson("/api/requests/{$request->id}/forward", ['sector' => 'COORDENACAO', 'reason' => 'Encaminhamento permitido'])->assertOk();

        $scopedAdmin = StaffAdmin::create(['name' => 'Admin sem escopo', 'email' => uniqid().'@example.test', 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        \App\Models\StaffAccessScope::create(['staff_admin_id' => $scopedAdmin->id, 'course_id' => $this->student()->course_id, 'abilities' => ['view_request'], 'reason' => 'Escopo não relacionado']);
        $this->actingAs($scopedAdmin, 'staff_admins')->postJson("/api/requests/{$request->id}/return", ['reason' => 'Não autorizado'])->assertForbidden();
    }

    public function test_final_decision_cannot_defer_service_with_known_missing_required_document(): void
    {
        $student = $this->student();
        $type = TypeRequest::create(['name' => 'Requer documento QA', 'requires_document' => true]);
        $request = RequestModel::create(['user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'Com anexo', 'description' => 'Descrição', 'status' => 'pending', 'protocol' => '20261026-123457']);
        $admin = StaffAdmin::create(['name' => 'Admin documento', 'email' => uniqid().'@example.test', 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);

        $this->actingAs($admin, 'staff_admins')->postJson("/api/requests/{$request->id}/decision", [
            'result' => 'deferido', 'justification' => 'Conferido.', 'conclusion_summary' => 'Deferido.',
        ])->assertUnprocessable();
        $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'pending', 'result' => null]);
    }

    public function test_not_applicable_result_requires_service_to_explicitly_allow_it(): void
    {
        $student = $this->student();
        $requiredDecisionType = TypeRequest::create(['name' => 'Serviço exige decisão']);
        $request = RequestModel::create(['user_id' => $student->id, 'type_id' => $requiredDecisionType->id, 'subject' => 'Pedido', 'description' => 'Descrição', 'status' => 'pending', 'protocol' => '20261026-123458']);
        $admin = StaffAdmin::create(['name' => 'Admin resultado', 'email' => uniqid().'@example.test', 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        $payload = ['result' => 'na', 'justification' => 'Não aplicável.', 'conclusion_summary' => 'Pedido concluído sem decisão acadêmica.'];

        $this->actingAs($admin, 'staff_admins')->postJson("/api/requests/{$request->id}/decision", $payload)->assertUnprocessable();
        $requiredDecisionType->update(['requires_decision' => false]);
        $this->postJson("/api/requests/{$request->id}/decision", $payload)->assertOk();
        $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'completed', 'result' => 'na']);
    }

    public function test_document_upload_belongs_to_student_and_rejects_invalid_format(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $student = $this->student();

        $invalid = $this->actingAs($student, 'api')->post('/api/documents/upload', [
            'arquivo' => UploadedFile::fake()->create('script.exe', 20, 'application/octet-stream'),
        ]);
        $invalid->assertUnprocessable();

        $valid = $this->actingAs($student, 'api')->post('/api/documents/upload', [
            'arquivo' => UploadedFile::fake()->create('comprovante.png', 20, 'image/png'),
        ]);
        $valid->assertCreated();
        $this->assertDatabaseHas('documents', [
            'id' => $valid->json('id'),
            'user_id' => $student->id,
        ]);
        Storage::disk('local')->assertExists(\App\Models\Document::findOrFail($valid->json('id'))->path);
    }

    public function test_coordination_can_open_own_course_document_but_other_users_cannot(): void
    {
        Storage::fake('local');
        $student = $this->student();
        $target = $this->requestFor($student);
        $upload = $this->actingAs($student, 'api')->post('/api/documents/upload', [
            'arquivo' => UploadedFile::fake()->create('atestado.png', 20, 'image/png'),
        ])->assertCreated();
        $target->documents()->attach($upload->json('id'));
        $url = "/api/requests/{$target->id}/documents/{$upload->json('id')}";

        $coordination = StaffAdmin::create([
            'name' => 'Coordenação de teste', 'email' => uniqid().'@example.test',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'role' => 'coordenacao', 'course_id' => $student->course_id,
            'password' => 'secret', 'must_change_password' => false,
        ]);
        $this->actingAs($coordination, 'staff_admins')->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($this->student(), 'api')->get($url)->assertForbidden();
        $this->get('/api/requests/'.$target->id.'/documents/'.\Illuminate\Support\Str::uuid())->assertNotFound();
    }

    public function test_dashboard_counts_only_non_final_requests_older_than_configured_days(): void
    {
        $student = $this->student();
        $type = $this->type();
        foreach ([RequestStatus::PENDING, RequestStatus::COMPLETED, RequestStatus::CANCELED] as $status) {
            $request = RequestModel::create([
                'user_id' => $student->id,
                'type_id' => $type->id,
                'subject' => 'Atraso '.$status->value,
                'description' => 'Teste',
                'status' => $status,
                'protocol' => uniqid('20260903-'),
            ]);
            $request->created_at = now()->subDays(6);
            $request->save();
        }

        $admin = StaffAdmin::create([
            'name' => 'Admin dashboard',
            'email' => uniqid().'@example.test',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'role' => 'admin',
            'password' => 'secret',
            'must_change_password' => false,
        ]);

        $this->actingAs($admin, 'staff_admins')
            ->getJson('/api/dashboard/estatisticas')
            ->assertOk()
            ->assertJsonPath('atrasados', 1);
    }

    private function student(): User
    {
        $course = Course::create(['name' => 'Curso '.uniqid(), 'code' => 'C'.uniqid()]);

        return User::create([
            'name' => 'Aluno de teste',
            'email' => uniqid().'@student.test',
            'password' => 'secret',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'phone' => '81999999999',
            'matricula' => 'M'.uniqid(),
            'course_id' => $course->id,
            'role' => 'student',
            'birthday' => '2000-01-01',
        ]);
    }

    private function type(): TypeRequest
    {
        return TypeRequest::create(['name' => 'Tipo '.uniqid()]);
    }

    private function requestFor(User $student): RequestModel
    {
        return RequestModel::create([
            'user_id' => $student->id,
            'type_id' => $this->type()->id,
            'subject' => 'Requerimento',
            'description' => 'Descrição',
            'status' => RequestStatus::PENDING,
            'protocol' => uniqid('20260903-'),
        ]);
    }
}
