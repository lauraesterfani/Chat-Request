<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Course;
use App\Models\Request as RequestModel;
use App\Models\SatisfactionResponse;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_are_calculated_from_filtered_requests(): void
    {
        $course = Course::create(['name' => 'Curso QA', 'code' => 'MQA']);
        $student = User::create(['name' => 'Aluno', 'email' => 'metric@example.test', 'cpf' => '90000003001', 'phone' => '81900003001', 'matricula' => 'MQA001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Métrica']);
        foreach (['pending', 'completed', 'canceled'] as $index => $status) {
            RequestModel::create(['user_id' => $student->id, 'course_id' => $course->id, 'type_id' => $type->id, 'subject' => "S$index", 'description' => 'D', 'status' => $status, 'protocol' => "20260907-1234$index"]);
        }
        $admin = StaffAdmin::create(['name' => 'Admin', 'email' => 'metric-admin@example.test', 'cpf' => '90000003002', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        $this->actingAs($admin, 'staff_admins')->getJson('/api/metrics?course_id='.$course->id)->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('open', 1)
            ->assertJsonPath('completed', 1)
            ->assertJsonPath('canceled', 1)
            ->assertJsonPath('cancelled', 1)
            ->assertJsonPath('by_status.pending', 1)
            ->assertJsonPath('by_status.waiting', 0)
            ->assertJsonPath('distribution.cohort', 'requests_opened_in_selected_period')
            ->assertJsonPath('distribution.by_service.0.label', 'Métrica')
            ->assertJsonPath('distribution.by_service.0.count', 3)
            ->assertJsonPath('distribution.by_course.0.label', 'Curso QA')
            ->assertJsonPath('distribution.by_course.0.count', 3)
            ->assertJsonPath('timezone', 'UTC');
    }

    public function test_metrics_date_range_includes_the_entire_end_day_and_applies_supported_filters(): void
    {
        $campus = Campus::create(['name' => 'Campus Métricas', 'code' => 'MET']);
        $course = Course::create(['name' => 'Curso Métricas', 'code' => 'MTR', 'campus_id' => $campus->id]);
        $student = User::create(['name' => 'Aluno', 'email' => 'date-metric@example.test', 'cpf' => '90000003201', 'phone' => '81900003201', 'matricula' => 'MTR001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Métrica por período']);

        Carbon::setTestNow('2026-10-25 23:59:59 UTC');
        $beforeRange = RequestModel::create(['user_id' => $student->id, 'course_id' => $course->id, 'campus_id' => $campus->id, 'type_id' => $type->id, 'subject' => 'Anterior', 'description' => 'D', 'status' => 'pending', 'protocol' => '20261025-000001']);
        Carbon::setTestNow('2026-10-26 23:45:00 UTC');
        $insideRange = RequestModel::create(['user_id' => $student->id, 'course_id' => $course->id, 'campus_id' => $campus->id, 'type_id' => $type->id, 'subject' => 'No período', 'description' => 'D', 'status' => 'completed', 'protocol' => '20261026-000001', 'responsible_sector' => 'COORDENACAO']);
        $insideRange->forceFill(['resolved_at' => Carbon::parse('2026-10-26 23:50:00 UTC')])->save();
        Carbon::setTestNow('2026-10-27 00:00:01 UTC');
        $afterRange = RequestModel::create(['user_id' => $student->id, 'course_id' => $course->id, 'campus_id' => $campus->id, 'type_id' => $type->id, 'subject' => 'Posterior', 'description' => 'D', 'status' => 'completed', 'protocol' => '20261027-000001', 'responsible_sector' => 'COORDENACAO']);
        $afterRange->forceFill(['resolved_at' => Carbon::parse('2026-10-27 00:00:01 UTC')])->save();
        Carbon::setTestNow();

        $admin = StaffAdmin::create(['name' => 'Admin período', 'email' => 'date-metric-admin@example.test', 'cpf' => '90000003202', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        $response = $this->actingAs($admin, 'staff_admins')->getJson('/api/metrics?from=2026-10-26&to=2026-10-26&campus_id='.$campus->id.'&course_id='.$course->id.'&type_id='.$type->id.'&sector=COORDENACAO&status=completed');

        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('completed', 1)
            ->assertJsonPath('open', 0)
            ->assertJsonPath('filters.from', '2026-10-26')
            ->assertJsonPath('filters.to', '2026-10-26')
            ->assertJsonPath('cohorts.opened_in_period.count', 1)
            ->assertJsonPath('cohorts.resolved_in_period.count', 1)
            ->assertJsonPath('cohorts.resolved_in_period.by_status.completed', 1)
            ->assertJsonPath('cohorts.current_stock.count', 2)
            ->assertJsonPath('filters.campus_id', $campus->id)
            ->assertJsonPath('filters.type_id', $type->id);

        $this->assertNotSame($beforeRange->id, $insideRange->id);
        $this->assertNotSame($insideRange->id, $afterRange->id);
    }

    public function test_metrics_expose_only_aggregated_satisfaction(): void
    {
        $course = Course::create(['name' => 'Curso satisfação', 'code' => 'MSA']);
        $student = User::create(['name' => 'Aluno', 'email' => 'satisfaction-metric@example.test', 'cpf' => '90000003003', 'phone' => '81900009991', 'matricula' => '20241MSAIG001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Métrica satisfação']);
        $request = RequestModel::create(['user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'S', 'description' => 'D', 'status' => 'completed', 'protocol' => '20260910-000001']);
        SatisfactionResponse::create(['request_id' => $request->id, 'student_id' => $student->id, 'rating' => 4, 'comment' => 'Comentário privado']);
        $admin = StaffAdmin::create(['name' => 'Admin', 'email' => 'satisfaction-admin@example.test', 'cpf' => '90000003004', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);

        $this->actingAs($admin, 'staff_admins')->getJson('/api/metrics')->assertOk()
            ->assertJsonPath('satisfaction.responses', 1)
            ->assertJsonPath('satisfaction.total_responses', 1)
            ->assertJsonPath('satisfaction.eligible_closed_requests', 1)
            ->assertJsonPath('satisfaction.response_rate_percent', 100)
            ->assertJsonPath('satisfaction.average_rating', 4)
            ->assertJsonPath('satisfaction.by_rating.1', 0)
            ->assertJsonPath('satisfaction.by_rating.4', 1)
            ->assertJsonMissing(['comment' => 'Comentário privado']);
    }

    public function test_metrics_return_null_satisfaction_rate_when_no_closed_requests_are_eligible(): void
    {
        $course = Course::create(['name' => 'Curso sem encerrados', 'code' => 'MSE']);
        $student = User::create(['name' => 'Aluno', 'email' => 'no-closed@example.test', 'cpf' => '90000003203', 'phone' => '81900003203', 'matricula' => 'MSE001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Métrica sem encerrados']);
        RequestModel::create(['user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'Aberto', 'description' => 'D', 'status' => 'pending', 'protocol' => '20261026-000002']);
        $admin = StaffAdmin::create(['name' => 'Admin sem encerrados', 'email' => 'no-closed-admin@example.test', 'cpf' => '90000003204', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);

        $this->actingAs($admin, 'staff_admins')->getJson('/api/metrics')->assertOk()
            ->assertJsonPath('satisfaction.eligible_closed_requests', 0)
            ->assertJsonPath('satisfaction.response_rate_percent', null)
            ->assertJsonPath('satisfaction.average_rating', null);
    }

    public function test_metrics_reject_unknown_status_and_reversed_date_ranges(): void
    {
        $admin = StaffAdmin::create(['name' => 'Admin validação', 'email' => 'invalid-metrics-admin@example.test', 'cpf' => '90000003205', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);

        $this->actingAs($admin, 'staff_admins')->getJson('/api/metrics?status=approved')->assertUnprocessable();
        $this->getJson('/api/metrics?from=2026-10-27&to=2026-10-26')->assertUnprocessable();
        $this->getJson('/api/metrics?from=26-10-2026')->assertUnprocessable();
    }

    public function test_scoped_admin_cannot_derive_totals_from_requests_outside_its_scope(): void
    {
        $allowedCourse = Course::create(['name' => 'Curso permitido', 'code' => 'MP1']);
        $otherCourse = Course::create(['name' => 'Curso bloqueado', 'code' => 'MP2']);
        $type = TypeRequest::create(['name' => 'Métrica escopo']);
        foreach ([$allowedCourse, $otherCourse] as $index => $course) {
            $student = User::create(['name' => "Aluno $index", 'email' => "scope$index@example.test", 'cpf' => '9000000310'.$index, 'phone' => '81900009991', 'matricula' => '20241MP'.($index + 1).'IG001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
            RequestModel::create(['user_id' => $student->id, 'course_id' => $course->id, 'type_id' => $type->id, 'subject' => 'S', 'description' => 'D', 'status' => 'pending', 'protocol' => '20260909-00000'.$index]);
        }
        $admin = StaffAdmin::create(['name' => 'Admin escopado', 'email' => 'scoped-admin@example.test', 'cpf' => '90000003199', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        StaffAccessScope::create(['staff_admin_id' => $admin->id, 'course_id' => $allowedCourse->id, 'abilities' => ['view_request'], 'reason' => 'QA']);

        $this->actingAs($admin, 'staff_admins')->getJson('/api/metrics')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('open', 1)
            ->assertJsonCount(1, 'distribution.by_course')
            ->assertJsonPath('distribution.by_course.0.label', 'Curso permitido')
            ->assertJsonMissing(['label' => 'Curso bloqueado']);
    }

    public function test_metrics_filter_choices_are_limited_to_requests_visible_to_the_actor(): void
    {
        $allowedCampus = Campus::create(['name' => 'Campus autorizado', 'code' => 'MFA']);
        $hiddenCampus = Campus::create(['name' => 'Campus restrito', 'code' => 'MFH']);
        $allowedCourse = Course::create(['name' => 'Curso autorizado', 'code' => 'MFA', 'campus_id' => $allowedCampus->id]);
        $hiddenCourse = Course::create(['name' => 'Curso restrito', 'code' => 'MFH', 'campus_id' => $hiddenCampus->id]);
        $allowedType = TypeRequest::create(['name' => 'Serviço autorizado']);
        $hiddenType = TypeRequest::create(['name' => 'Serviço restrito']);

        foreach ([[$allowedCourse, $allowedCampus, $allowedType, 'CRADT'], [$hiddenCourse, $hiddenCampus, $hiddenType, 'COORDENACAO']] as $index => [$course, $campus, $type, $sector]) {
            $student = User::create(['name' => "Aluno filtro $index", 'email' => "filter$index@example.test", 'cpf' => '9000000330'.$index, 'phone' => '81900003301', 'matricula' => 'MFF00'.$index, 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
            RequestModel::create(['user_id' => $student->id, 'course_id' => $course->id, 'campus_id' => $campus->id, 'type_id' => $type->id, 'subject' => 'Pedido', 'description' => 'Descrição', 'status' => 'pending', 'protocol' => '20261026-33000'.$index, 'responsible_sector' => $sector]);
        }

        $admin = StaffAdmin::create(['name' => 'Admin filtros escopados', 'email' => 'scoped-filters@example.test', 'cpf' => '90000003399', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        StaffAccessScope::create(['staff_admin_id' => $admin->id, 'course_id' => $allowedCourse->id, 'abilities' => ['view_request'], 'reason' => 'QA']);

        $this->actingAs($admin, 'staff_admins')->getJson('/api/metrics/filters')->assertOk()
            ->assertJsonCount(1, 'campuses')->assertJsonPath('campuses.0.id', $allowedCampus->id)
            ->assertJsonCount(1, 'courses')->assertJsonPath('courses.0.id', $allowedCourse->id)
            ->assertJsonCount(1, 'services')->assertJsonPath('services.0.id', $allowedType->id)
            ->assertJsonPath('sectors.0.value', 'CRADT')
            ->assertJsonMissing(['name' => 'Campus restrito'])
            ->assertJsonMissing(['name' => 'Curso restrito'])
            ->assertJsonMissing(['name' => 'Serviço restrito']);
    }

    public function test_expired_scope_does_not_restore_global_dashboard_totals(): void
    {
        $course = Course::create(['name' => 'Curso visível somente antes do vencimento', 'code' => 'MEX']);
        $student = User::create(['name' => 'Aluno', 'email' => 'expired-metrics@example.test', 'cpf' => '90000003198', 'phone' => '81900003198', 'matricula' => 'MEX001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Métrica expirada']);
        RequestModel::create(['user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'Privado', 'description' => 'D', 'status' => 'pending', 'protocol' => '20261026-000098', 'course_id' => $course->id]);
        $admin = StaffAdmin::create(['name' => 'Admin expirado', 'email' => 'expired-admin@example.test', 'cpf' => '90000003197', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        StaffAccessScope::create(['staff_admin_id' => $admin->id, 'course_id' => $course->id, 'abilities' => ['view_request'], 'expires_at' => now()->subMinute(), 'reason' => 'Expirado']);

        $this->actingAs($admin, 'staff_admins')->getJson('/api/dashboard/requerimentos')->assertOk()
            ->assertJsonPath('total_requerimentos', 0)->assertJsonPath('total_usuarios', 0)->assertJsonPath('pendentes', 0);
        $this->getJson('/api/dashboard/graficos/status')->assertOk()->assertExactJson([]);
        $this->getJson('/api/metrics')->assertOk()->assertJsonPath('total', 0);
    }
}
