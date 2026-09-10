<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Course;
use App\Models\Request as RequestModel;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SatisfactionResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_rate_own_closed_request_only_once(): void
    {
        $student = $this->student();
        $request = $this->requestFor($student, RequestStatus::COMPLETED);

        $this->actingAs($student, 'api')->postJson("/api/requests/{$request->id}/satisfaction", ['rating' => 5, 'comment' => 'Atendimento de teste.'])
            ->assertCreated()->assertJsonPath('rating', 5);
        $this->actingAs($student, 'api')->postJson("/api/requests/{$request->id}/satisfaction", ['rating' => 4])
            ->assertConflict();
    }

    public function test_student_cannot_rate_another_or_open_request(): void
    {
        $owner = $this->student();
        $other = $this->student();
        $closed = $this->requestFor($owner, RequestStatus::COMPLETED);
        $open = $this->requestFor($other, RequestStatus::PENDING);

        $this->actingAs($other, 'api')->postJson("/api/requests/{$closed->id}/satisfaction", ['rating' => 3])->assertForbidden();
        $this->actingAs($other, 'api')->postJson("/api/requests/{$open->id}/satisfaction", ['rating' => 3])->assertStatus(422);
    }

    private function student(): User
    {
        $course = Course::create(['name' => 'Curso '.uniqid(), 'code' => 'S'.uniqid()]);

        return User::create(['name' => 'Aluno', 'email' => uniqid().'@example.test', 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'phone' => '81900009991', 'matricula' => '20241SATIG'.random_int(100, 999), 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
    }

    private function requestFor(User $student, RequestStatus $status): RequestModel
    {
        $type = TypeRequest::create(['name' => 'Tipo '.uniqid()]);

        return RequestModel::create(['user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'Pedido', 'description' => 'Descrição', 'status' => $status, 'protocol' => uniqid('20260910-')]);
    }
}
