<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ServiceOpeningWindow;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ServiceAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_window_blocks_submission_outside_period_and_allows_it_inside(): void
    {
        Carbon::setTestNow('2026-09-09 12:00:00');
        $course = Course::create(['name' => 'Curso janela', 'code' => 'JAN']);
        $student = User::create(['name' => 'Aluno janela', 'email' => 'window@example.test', 'cpf' => '90000007111', 'phone' => '81900009991', 'matricula' => '20241JANIG001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Serviço com janela']);
        ServiceOpeningWindow::create(['type_request_id' => $type->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2), 'status' => 'published']);

        $this->actingAs($student, 'api')->postJson('/api/requests', ['type_id' => $type->id, 'subject' => 'Pedido', 'description' => 'Descrição'])->assertUnprocessable();

        ServiceOpeningWindow::query()->update(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        $this->actingAs($student, 'api')->postJson('/api/requests', ['type_id' => $type->id, 'subject' => 'Pedido', 'description' => 'Descrição'])->assertCreated();
        Carbon::setTestNow();
    }

    public function test_missing_or_unpublished_window_fails_closed(): void
    {
        Carbon::setTestNow('2026-09-09 12:00:00');
        $course = Course::create(['name' => 'Curso sem janela', 'code' => 'SJ']);
        $student = User::create(['name' => 'Aluno sem janela', 'email' => 'no-window@example.test', 'cpf' => '90000007113', 'phone' => '81900009992', 'matricula' => '20241SJI001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Serviço sem calendário']);

        $this->actingAs($student, 'api')->postJson('/api/requests', ['type_id' => $type->id, 'subject' => 'Pedido', 'description' => 'Descrição'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Este serviço não possui uma janela publicada compatível com o período e o vínculo acadêmico selecionado. Seu rascunho foi preservado.');
        $this->assertDatabaseCount('requests', 0);

        ServiceOpeningWindow::create(['type_request_id' => $type->id, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'status' => 'draft']);
        $this->postJson('/api/requests', ['type_id' => $type->id, 'subject' => 'Pedido', 'description' => 'Descrição'])->assertUnprocessable();
        $this->assertDatabaseCount('requests', 0);
        Carbon::setTestNow();
    }

    public function test_submission_window_is_checked_against_the_selected_enrollment_course_and_campus(): void
    {
        Carbon::setTestNow('2026-09-09 12:00:00');
        $campusA = Campus::create(['name' => 'Campus A', 'code' => 'CAMP-A', 'is_active' => true]);
        $campusB = Campus::create(['name' => 'Campus B', 'code' => 'CAMP-B', 'is_active' => true]);
        $courseA = Course::create(['name' => 'Curso A', 'code' => 'CA', 'campus_id' => $campusA->id]);
        $courseB = Course::create(['name' => 'Curso B', 'code' => 'CB', 'campus_id' => $campusB->id]);
        $student = User::create(['name' => 'Aluno multi', 'email' => 'multi-window@example.test', 'cpf' => '90000007114', 'phone' => '81900009993', 'matricula' => '20241MWI001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $courseA->id]);
        $enrollmentA = Enrollment::create(['user_id' => $student->id, 'course_id' => $courseA->id, 'enrollment' => '20241CAIG001', 'status' => 'active']);
        $enrollmentB = Enrollment::create(['user_id' => $student->id, 'course_id' => $courseB->id, 'enrollment' => '20241CBIG001', 'status' => 'active']);
        $type = TypeRequest::create(['name' => 'Serviço por campus']);
        ServiceOpeningWindow::create([
            'type_request_id' => $type->id,
            'campus_id' => $campusA->id,
            'course_id' => $courseA->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'status' => 'published',
        ]);

        $created = $this->actingAs($student, 'api')->postJson('/api/requests', [
            'type_id' => $type->id, 'subject' => 'Pedido A', 'description' => 'Descrição', 'enrollment_id' => $enrollmentA->id,
        ])->assertCreated();
        $this->assertDatabaseHas('requests', ['id' => $created->json('id'), 'campus_id' => $campusA->id, 'course_id' => $courseA->id, 'enrollment_id' => $enrollmentA->id]);

        $this->postJson('/api/requests', [
            'type_id' => $type->id, 'subject' => 'Pedido B', 'description' => 'Descrição', 'enrollment_id' => $enrollmentB->id,
        ])->assertUnprocessable();
        $this->assertDatabaseCount('requests', 1);
        Carbon::setTestNow();
    }

    public function test_opening_window_rejects_course_and_campus_that_do_not_match(): void
    {
        $campusA = Campus::create(['name' => 'Campus C', 'code' => 'CAMP-C', 'is_active' => true]);
        $campusB = Campus::create(['name' => 'Campus D', 'code' => 'CAMP-D', 'is_active' => true]);
        $course = Course::create(['name' => 'Curso D', 'code' => 'CD', 'campus_id' => $campusB->id]);
        $type = TypeRequest::create(['name' => 'Serviço com escopo inconsistente']);
        $admin = StaffAdmin::create(['name' => 'Admin janela escopada', 'email' => 'scoped-window-admin@example.test', 'cpf' => '90000007115', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);

        $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/service-opening-windows', [
            'type_request_id' => $type->id,
            'campus_id' => $campusA->id,
            'course_id' => $course->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'timezone' => 'America/Recife',
            'status' => 'draft',
        ])->assertUnprocessable()->assertJsonValidationErrors('course_id');

        $this->assertDatabaseCount('service_opening_windows', 0);
    }

    public function test_only_administration_can_configure_an_opening_window(): void
    {
        $type = TypeRequest::create(['name' => 'Serviço configurável']);
        $admin = StaffAdmin::create(['name' => 'Admin janela', 'email' => 'window-admin@example.test', 'cpf' => '90000007112', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);

        $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/service-opening-windows', [
            'type_request_id' => $type->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2), 'timezone' => 'America/Recife', 'status' => 'draft',
        ])->assertCreated()->assertJsonPath('status', 'draft');
    }

    public function test_publishing_requires_reference_and_rejects_overlapping_windows_in_the_exact_scope(): void
    {
        $campus = Campus::create(['name' => 'Campus janela publicada', 'code' => 'CWP', 'is_active' => true]);
        $course = Course::create(['name' => 'Curso janela publicada', 'code' => 'CWP', 'campus_id' => $campus->id]);
        $otherCourse = Course::create(['name' => 'Outro curso janela publicada', 'code' => 'OWP', 'campus_id' => $campus->id]);
        $type = TypeRequest::create(['name' => 'Serviço com publicação']);
        $admin = StaffAdmin::create(['name' => 'Admin publicação', 'email' => 'publish-window@example.test', 'cpf' => '90000007116', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        $payload = [
            'type_request_id' => $type->id,
            'campus_id' => $campus->id,
            'course_id' => $course->id,
            'starts_at' => '2026-09-09T10:00:00Z',
            'ends_at' => '2026-09-09T12:00:00Z',
            'timezone' => 'America/Recife',
            'status' => 'published',
        ];

        $this->actingAs($admin, 'staff_admins')
            ->postJson('/api/admin/service-opening-windows', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('source_reference');

        $published = $this->postJson('/api/admin/service-opening-windows', $payload + ['source_reference' => 'Ato institucional fictício para teste'])
            ->assertCreated()
            ->assertJsonPath('status', 'published');

        $this->postJson('/api/admin/service-opening-windows', $payload + ['source_reference' => 'Outra referência fictícia'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('starts_at');

        $this->postJson('/api/admin/service-opening-windows', array_merge($payload, [
            'course_id' => $otherCourse->id,
            'source_reference' => 'Referência fictícia, outro curso',
        ]))->assertCreated();

        $this->postJson('/api/admin/service-opening-windows', array_merge($payload, [
            'starts_at' => '2026-09-09T12:00:00Z',
            'ends_at' => '2026-09-09T13:00:00Z',
            'source_reference' => 'Referência fictícia, período adjacente',
        ]))->assertCreated();

        $this->assertSame($published->json('id'), ServiceOpeningWindow::query()->where('course_id', $course->id)->where('starts_at', '2026-09-09 10:00:00')->value('id'));
    }

    public function test_updating_a_published_window_ignores_itself_but_rejects_another_overlap(): void
    {
        $type = TypeRequest::create(['name' => 'Serviço alteração de janela']);
        $admin = StaffAdmin::create(['name' => 'Admin alteração', 'email' => 'update-window@example.test', 'cpf' => '90000007117', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        $window = ServiceOpeningWindow::create([
            'type_request_id' => $type->id,
            'starts_at' => '2026-09-09 10:00:00',
            'ends_at' => '2026-09-09 12:00:00',
            'timezone' => 'America/Recife',
            'status' => 'published',
            'source_reference' => 'Referência fictícia de teste',
            'timestamps_utc_normalized' => true,
        ]);
        $payload = [
            'type_request_id' => $type->id,
            'starts_at' => '2026-09-09T10:00:00Z',
            'ends_at' => '2026-09-09T12:00:00Z',
            'timezone' => 'America/Recife',
            'status' => 'published',
            'source_reference' => 'Referência fictícia de teste',
        ];

        $this->actingAs($admin, 'staff_admins')
            ->putJson('/api/admin/service-opening-windows/'.$window->id, $payload)
            ->assertOk();

        ServiceOpeningWindow::create([
            'type_request_id' => $type->id,
            'starts_at' => '2026-09-09 11:00:00',
            'ends_at' => '2026-09-09 13:00:00',
            'timezone' => 'America/Recife',
            'status' => 'published',
            'source_reference' => 'Outra referência fictícia',
        ]);

        $this->putJson('/api/admin/service-opening-windows/'.$window->id, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('starts_at');
    }

    public function test_offset_timestamps_are_saved_as_utc_and_legacy_naive_timestamps_keep_their_value(): void
    {
        $type = TypeRequest::create(['name' => 'Serviço timezone UTC']);
        $admin = StaffAdmin::create(['name' => 'Admin timezone', 'email' => 'timezone-window@example.test', 'cpf' => '90000007118', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);

        $published = $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/service-opening-windows', [
            'type_request_id' => $type->id,
            'starts_at' => '2027-02-15T09:00:00-03:00',
            'ends_at' => '2027-02-15T11:00:00-03:00',
            'timezone' => 'America/Recife',
            'status' => 'published',
            'source_reference' => 'Referência fictícia de validação automatizada',
        ])->assertCreated()->assertJsonPath('timestamps_utc_normalized', true);

        $this->assertDatabaseHas('service_opening_windows', [
            'id' => $published->json('id'),
            'starts_at' => '2027-02-15 12:00:00',
            'ends_at' => '2027-02-15 14:00:00',
        ]);

        $this->putJson('/api/admin/service-opening-windows/'.$published->json('id'), [
            'type_request_id' => $type->id,
            'starts_at' => '2027-02-15T10:00:00-03:00',
            'ends_at' => '2027-02-15T12:00:00-03:00',
            'timezone' => 'America/Recife',
            'status' => 'published',
            'source_reference' => 'Referência fictícia de validação automatizada',
        ])->assertOk()->assertJsonPath('timestamps_utc_normalized', true);

        $this->assertDatabaseHas('service_opening_windows', [
            'id' => $published->json('id'),
            'starts_at' => '2027-02-15 13:00:00',
            'ends_at' => '2027-02-15 15:00:00',
        ]);

        $legacy = $this->postJson('/api/admin/service-opening-windows', [
            'type_request_id' => $type->id,
            'starts_at' => '2027-02-16 09:00:00',
            'ends_at' => '2027-02-16 11:00:00',
            'timezone' => 'America/Recife',
            'status' => 'draft',
        ])->assertCreated()->assertJsonPath('timestamps_utc_normalized', false);

        $this->assertDatabaseHas('service_opening_windows', [
            'id' => $legacy->json('id'),
            'starts_at' => '2027-02-16 09:00:00',
            'ends_at' => '2027-02-16 11:00:00',
        ]);

        $this->putJson('/api/admin/service-opening-windows/'.$legacy->json('id'), [
            'type_request_id' => $type->id,
            'starts_at' => '2027-02-16 10:00:00',
            'ends_at' => '2027-02-16 12:00:00',
            'timezone' => 'America/Recife',
            'status' => 'draft',
        ])->assertUnprocessable()->assertJsonValidationErrors(['starts_at', 'ends_at']);

        $this->putJson('/api/admin/service-opening-windows/'.$legacy->json('id'), [
            'type_request_id' => $type->id,
            'starts_at' => '2027-02-16 09:00:00',
            'ends_at' => '2027-02-16 11:00:00',
            'timezone' => 'America/Fortaleza',
            'status' => 'draft',
        ])->assertUnprocessable()->assertJsonValidationErrors('timezone');

        $this->assertDatabaseHas('service_opening_windows', [
            'id' => $legacy->json('id'),
            'starts_at' => '2027-02-16 09:00:00',
            'ends_at' => '2027-02-16 11:00:00',
            'timezone' => 'America/Recife',
            'timestamps_utc_normalized' => false,
        ]);
    }
}
