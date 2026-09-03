<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Course;
use App\Models\Message;
use App\Models\Request as RequestModel;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_send_and_list_own_messages(): void
    {
        $student = $this->student();
        $target = $this->requestFor($student);
        $created = $this->actingAs($student, 'api')->postJson("/api/requests/{$target->id}/messages", ['content' => 'Olá, preciso de ajuda.']);
        $created->assertCreated()->assertJsonPath('sender.role', 'student')->assertJsonPath('is_mine', true);
        $this->actingAs($student, 'api')->getJson("/api/requests/{$target->id}/messages")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_other_student_and_staff_without_academic_role_are_denied(): void
    {
        $owner = $this->student();
        $target = $this->requestFor($owner);
        $other = $this->student();
        $this->actingAs($other, 'api')->getJson("/api/requests/{$target->id}/messages")->assertForbidden();
        $staff = StaffAdmin::create(['name' => 'TI', 'email' => uniqid().'@example.test', 'cpf' => '9'.str_pad((string) random_int(1, 9999999999), 10, '0', STR_PAD_LEFT), 'role' => 'staff', 'password' => 'secret', 'must_change_password' => false]);
        $this->actingAs($staff, 'staff_admins')->getJson("/api/requests/{$target->id}/messages")->assertForbidden();
    }

    public function test_cradt_can_reply_and_closed_request_is_read_only(): void
    {
        $student = $this->student();
        $target = $this->requestFor($student);
        $cradt = StaffAdmin::create(['name' => 'CRADT', 'email' => uniqid().'@example.test', 'cpf' => '9'.str_pad((string) random_int(1, 9999999999), 10, '0', STR_PAD_LEFT), 'role' => 'cradt', 'password' => 'secret', 'must_change_password' => false]);
        $this->actingAs($cradt, 'staff_admins')->postJson("/api/requests/{$target->id}/messages", ['content' => 'Resposta da equipe'])->assertCreated();
        $target->update(['status' => RequestStatus::COMPLETED]);
        $this->actingAs($student, 'api')->getJson("/api/requests/{$target->id}/messages")->assertOk();
        $this->actingAs($student, 'api')->postJson("/api/requests/{$target->id}/messages", ['content' => 'Nova mensagem'])->assertStatus(409);
    }

    public function test_empty_long_and_spoofed_sender_are_rejected_and_identity_is_server_side(): void
    {
        $student = $this->student();
        $target = $this->requestFor($student);
        $url = "/api/requests/{$target->id}/messages";
        $this->actingAs($student, 'api')->postJson($url, ['content' => '   '])->assertUnprocessable();
        $this->actingAs($student, 'api')->postJson($url, ['content' => str_repeat('x', 2001)])->assertUnprocessable();
        $response = $this->actingAs($student, 'api')->postJson($url, ['content' => '<script>alert(1)</script>', 'sender_id' => 'forged', 'sender_type' => 'staff_admin']);
        $response->assertCreated()->assertJsonPath('sender.id', $student->id)->assertJsonMissingPath('sender.cpf');
        $this->assertSame($student->id, Message::first()->sender_id);
    }

    public function test_pagination_and_unread_count_are_updated_when_read(): void
    {
        $student = $this->student();
        $target = $this->requestFor($student);
        $cradt = StaffAdmin::create(['name' => 'CRADT', 'email' => uniqid().'@example.test', 'cpf' => '9'.str_pad((string) random_int(1, 9999999999), 10, '0', STR_PAD_LEFT), 'role' => 'cradt', 'password' => 'secret', 'must_change_password' => false]);
        $this->actingAs($cradt, 'staff_admins')->postJson("/api/requests/{$target->id}/messages", ['content' => 'Uma mensagem'])->assertCreated();
        $this->actingAs($student, 'api')->getJson('/api/requests')->assertJsonPath('0.unread_messages_count', 1);
        $this->actingAs($student, 'api')->getJson("/api/requests/{$target->id}/messages?per_page=1")->assertJsonPath('per_page', 1);
        $this->actingAs($student, 'api')->postJson("/api/requests/{$target->id}/messages/read")->assertOk();
        $this->actingAs($student, 'api')->getJson('/api/requests')->assertJsonPath('0.unread_messages_count', 0);
    }

    private function student(): User
    {
        $course = Course::create(['name' => 'Curso '.uniqid(), 'code' => 'C'.uniqid()]);

        return User::create(['name' => 'Aluno', 'email' => uniqid().'@example.test', 'cpf' => '8'.str_pad((string) random_int(1, 9999999999), 10, '0', STR_PAD_LEFT), 'phone' => '81900000000', 'matricula' => 'MAT'.random_int(1000, 9999), 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
    }

    private function requestFor(User $student): RequestModel
    {
        $type = TypeRequest::create(['name' => 'Serviço', 'description' => 'Descrição', 'requires_document' => false]);

        return RequestModel::create(['user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'Assunto', 'description' => 'Descrição', 'status' => RequestStatus::PENDING, 'protocol' => now()->format('Ymd').'-'.random_int(100000, 999999)]);
    }
}
