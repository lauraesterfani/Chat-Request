<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Request as RequestModel;
use App\Models\RequestDraft;
use App\Models\ServiceOpeningWindow;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentSelectionTest extends TestCase
{
    use RefreshDatabase;

    private static int $studentSequence = 0;

    public function test_student_must_select_one_of_their_active_enrollments_when_there_are_multiple(): void
    {
        $student = $this->student();
        $other = $this->student();
        $firstCourse = Course::create(['name' => 'Curso A', 'code' => 'QA-A']);
        $secondCourse = Course::create(['name' => 'Curso B', 'code' => 'QA-B']);
        $first = $this->enroll($student, $firstCourse, 'QA-A-001');
        $second = $this->enroll($student, $secondCourse, 'QA-B-001');
        $foreign = $this->enroll($other, $secondCourse, 'QA-B-002');
        $locked = $this->enroll($student, $firstCourse, 'QA-A-LOCKED', 'locked');
        $type = TypeRequest::create(['name' => 'Serviço de teste']);

        $options = $this->actingAs($student, 'api')->getJson('/api/enrollments/active')->assertOk()
            ->assertJsonPath('requires_selection', true)->assertJsonCount(2, 'enrollments')->json('enrollments');
        $this->assertEqualsCanonicalizing([$first->id, $second->id], array_column($options, 'id'));
        $this->assertSame('Curso B', collect($options)->firstWhere('id', $second->id)['course_name']);

        $payload = ['type_request_id' => $type->id, 'responses' => ['subject' => 'Teste']];
        $this->postJson('/api/drafts', $payload)->assertUnprocessable()->assertJsonValidationErrors('enrollment_id');
        $this->postJson('/api/drafts', [...$payload, 'enrollment_id' => $foreign->id])->assertUnprocessable()->assertJsonValidationErrors('enrollment_id');
        $this->postJson('/api/drafts', [...$payload, 'enrollment_id' => $locked->id])->assertUnprocessable()->assertJsonValidationErrors('enrollment_id');
        $this->assertDatabaseCount('request_drafts', 0);

        $draft = $this->postJson('/api/drafts', [...$payload, 'enrollment_id' => $second->id])
            ->assertCreated()->assertJsonPath('course_id', $secondCourse->id)->assertJsonPath('enrollment_id', $second->id)->json();
        $this->putJson('/api/drafts/'.$draft['id'], [...$payload, 'revision' => 1, 'enrollment_id' => $first->id])->assertConflict();
        $this->putJson('/api/drafts/'.$draft['id'], [...$payload, 'revision' => 1, 'enrollment_id' => null])
            ->assertOk()->assertJsonPath('enrollment_id', $second->id);
        $this->assertDatabaseHas('request_drafts', ['id' => $draft['id'], 'course_id' => $secondCourse->id, 'enrollment_id' => $second->id]);
    }

    public function test_legacy_draft_without_enrollment_can_be_assigned_explicitly(): void
    {
        $student = $this->student();
        $course = Course::create(['name' => 'Curso legado', 'code' => 'QA-LEG']);
        $enrollment = $this->enroll($student, $course, 'QA-LEG-001');
        $type = TypeRequest::create(['name' => 'Serviço legado']);
        $draft = RequestDraft::create(['user_id' => $student->id, 'type_request_id' => $type->id, 'revision' => 1]);

        $this->actingAs($student, 'api')->putJson('/api/drafts/'.$draft->id, [
            'type_request_id' => $type->id,
            'revision' => 1,
            'enrollment_id' => $enrollment->id,
        ])->assertOk()->assertJsonPath('course_id', $course->id)->assertJsonPath('enrollment_id', $enrollment->id);
    }

    public function test_single_active_enrollment_is_selected_without_prompt(): void
    {
        $student = $this->student();
        $course = Course::create(['name' => 'Curso único', 'code' => 'QA-ONE']);
        $enrollment = $this->enroll($student, $course, 'QA-ONE-001');
        $type = TypeRequest::create(['name' => 'Serviço único']);

        $this->actingAs($student, 'api')->getJson('/api/enrollments/active')->assertOk()->assertJsonPath('requires_selection', false);
        $this->postJson('/api/drafts', ['type_request_id' => $type->id])
            ->assertCreated()->assertJsonPath('course_id', $course->id)->assertJsonPath('enrollment_id', $enrollment->id);
    }

    public function test_campus_is_snapshotted_on_the_draft_and_submitted_request(): void
    {
        $student = $this->student();
        $campus = Campus::create(['name' => 'Campus de teste', 'code' => 'CAMP-QA']);
        $course = Course::create(['name' => 'Curso em campus', 'code' => 'QA-CAMP', 'campus_id' => $campus->id]);
        $enrollment = $this->enroll($student, $course, 'QA-CAMP-001');
        $type = TypeRequest::create(['name' => 'Serviço com campus']);
        ServiceOpeningWindow::create([
            'type_request_id' => $type->id,
            'campus_id' => $campus->id,
            'course_id' => $course->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
            'timezone' => 'America/Recife',
            'status' => 'published',
        ]);

        $draft = $this->actingAs($student, 'api')->postJson('/api/drafts', ['type_request_id' => $type->id])
            ->assertCreated()->assertJsonPath('campus_id', $campus->id)->json();
        $created = $this->postJson('/api/requests', [
            'type_id' => $type->id,
            'subject' => 'Pedido de campus',
            'description' => 'Solicitação de teste',
            'enrollment_id' => $enrollment->id,
        ])->assertCreated();

        $this->assertDatabaseHas('request_drafts', ['id' => $draft['id'], 'campus_id' => $campus->id]);
        $this->assertDatabaseHas('requests', ['id' => $created->json('id'), 'course_id' => $course->id, 'campus_id' => $campus->id]);
    }

    public function test_direct_submission_requires_selection_and_idempotency_cannot_cross_enrollments(): void
    {
        $student = $this->student();
        $courseA = Course::create(['name' => 'Curso A', 'code' => 'QA-DIR-A']);
        $courseB = Course::create(['name' => 'Curso B', 'code' => 'QA-DIR-B']);
        $first = $this->enroll($student, $courseA, 'QA-DIR-A-001');
        $second = $this->enroll($student, $courseB, 'QA-DIR-B-001');
        $type = TypeRequest::create(['name' => 'Envio direto']);
        $this->publishGlobalWindow($type);
        $payload = ['type_id' => $type->id, 'subject' => 'Mesmo assunto', 'description' => 'Mesma descrição', 'idempotency_key' => 'qa-enrollment-selection-001'];

        $this->actingAs($student, 'api')->postJson('/api/requests', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('enrollment_id');
        $created = $this->postJson('/api/requests', [...$payload, 'enrollment_id' => $first->id])->assertCreated();
        $this->assertDatabaseHas('requests', ['id' => $created->json('id'), 'course_id' => $courseA->id, 'enrollment_id' => $first->id]);
        $this->postJson('/api/requests', [...$payload, 'enrollment_id' => $first->id])
            ->assertOk()->assertJsonPath('id', $created->json('id'))->assertJsonPath('idempotent_replay', true);
        $this->postJson('/api/requests', [...$payload, 'enrollment_id' => $second->id])->assertConflict();
        $this->assertDatabaseCount('requests', 1);
    }

    public function test_ambiguous_legacy_draft_cannot_submit_until_enrollment_is_saved(): void
    {
        $student = $this->student();
        $courseA = Course::create(['name' => 'Curso A', 'code' => 'QA-DRAFT-A']);
        $courseB = Course::create(['name' => 'Curso B', 'code' => 'QA-DRAFT-B']);
        $first = $this->enroll($student, $courseA, 'QA-DRAFT-A-001');
        $second = $this->enroll($student, $courseB, 'QA-DRAFT-B-001');
        $type = TypeRequest::create(['name' => 'Rascunho ambíguo']);
        $this->publishGlobalWindow($type);
        $draft = RequestDraft::create(['user_id' => $student->id, 'type_request_id' => $type->id, 'revision' => 1, 'course_id' => $courseA->id]);
        $payload = ['type_id' => $type->id, 'subject' => 'Pedido legado', 'description' => 'Descrição', 'draft_id' => $draft->id];

        $this->actingAs($student, 'api')->postJson('/api/requests', $payload)->assertConflict();
        $this->postJson('/api/requests', [...$payload, 'enrollment_id' => $first->id])->assertConflict();
        $this->putJson('/api/drafts/'.$draft->id, ['type_request_id' => $type->id, 'revision' => 1, 'enrollment_id' => $second->id])
            ->assertOk()->assertJsonPath('course_id', $courseB->id);
        $this->postJson('/api/requests', [...$payload, 'enrollment_id' => $first->id])->assertConflict();
        $created = $this->postJson('/api/requests', [...$payload, 'enrollment_id' => $second->id])->assertCreated();
        $this->assertSame($second->id, RequestModel::findOrFail($created->json('id'))->enrollment_id);
    }

    public function test_legacy_idempotency_hash_replays_without_changing_enrollment(): void
    {
        $student = $this->student();
        $type = TypeRequest::create(['name' => 'Envio antigo']);
        $this->publishGlobalWindow($type);
        $payload = ['type_id' => $type->id, 'subject' => 'Assunto antigo', 'description' => 'Descrição antiga', 'idempotency_key' => 'qa-legacy-enrollment-001'];
        $created = $this->actingAs($student, 'api')->postJson('/api/requests', $payload)->assertCreated();
        $legacyHash = hash('sha256', json_encode([$type->id, 'Assunto antigo', 'Descrição antiga', [], []]));
        RequestModel::findOrFail($created->json('id'))->update(['submission_payload_hash' => $legacyHash]);

        $this->postJson('/api/requests', $payload)->assertOk()->assertJsonPath('id', $created->json('id'));
        $course = Course::create(['name' => 'Curso posterior', 'code' => 'QA-LEG-NEW']);
        $enrollment = $this->enroll($student, $course, 'QA-LEG-NEW-001');
        $this->postJson('/api/requests', [...$payload, 'enrollment_id' => $enrollment->id])->assertConflict();
        $this->assertDatabaseCount('requests', 1);
    }

    private function student(): User
    {
        $number = ++self::$studentSequence;

        return User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'cpf' => str_pad((string) (90000005000 + $number), 11, '0', STR_PAD_LEFT),
            'phone' => '81900000000',
            'matricula' => 'QA-ENR-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            'birthday' => '2000-01-01',
        ]);
    }

    private function enroll(User $student, Course $course, string $number, string $status = 'active'): Enrollment
    {
        return Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'enrollment' => $number, 'status' => $status]);
    }

    private function publishGlobalWindow(TypeRequest $type): void
    {
        ServiceOpeningWindow::create([
            'type_request_id' => $type->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
            'timezone' => 'America/Recife',
            'status' => 'published',
        ]);
    }
}
