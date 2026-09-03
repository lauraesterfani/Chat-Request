<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Course;
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

        $this->actingAs($admin, 'staff_admins')->putJson("/api/requests/{$request->id}", [
            'status' => 'completed', 'observation' => 'Seu requerimento foi deferido.',
        ])->assertOk();

        $this->assertDatabaseHas('messages', [
            'request_id' => $request->id, 'sender_id' => $admin->id,
            'sender_type' => 'staff_admin', 'content' => 'Seu requerimento foi deferido.',
        ]);
        $this->actingAs($student, 'api')->getJson("/api/requests/{$request->id}/messages")
            ->assertJsonPath('data.0.content', 'Seu requerimento foi deferido.');
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

    public function test_document_upload_belongs_to_student_and_rejects_invalid_format(): void
    {
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
