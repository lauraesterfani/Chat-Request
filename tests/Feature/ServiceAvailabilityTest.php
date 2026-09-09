<?php

namespace Tests\Feature;

use App\Models\Course;
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

    public function test_only_administration_can_configure_an_opening_window(): void
    {
        $type = TypeRequest::create(['name' => 'Serviço configurável']);
        $admin = StaffAdmin::create(['name' => 'Admin janela', 'email' => 'window-admin@example.test', 'cpf' => '90000007112', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);

        $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/service-opening-windows', [
            'type_request_id' => $type->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2), 'timezone' => 'America/Recife', 'status' => 'draft',
        ])->assertCreated()->assertJsonPath('status', 'draft');
    }
}
