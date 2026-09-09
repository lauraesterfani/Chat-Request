<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Request as RequestModel;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            RequestModel::create(['user_id' => $student->id, 'type_id' => $type->id, 'subject' => "S$index", 'description' => 'D', 'status' => $status, 'protocol' => "20260907-1234$index"]);
        }
        $admin = StaffAdmin::create(['name' => 'Admin', 'email' => 'metric-admin@example.test', 'cpf' => '90000003002', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        $this->actingAs($admin, 'staff_admins')->getJson('/api/metrics?course_id='.$course->id)->assertOk()->assertJsonPath('total', 3)->assertJsonPath('open', 1)->assertJsonPath('completed', 1)->assertJsonPath('canceled', 1);
    }

    public function test_scoped_admin_cannot_derive_totals_from_requests_outside_its_scope(): void
    {
        $allowedCourse = Course::create(['name' => 'Curso permitido', 'code' => 'MP1']);
        $otherCourse = Course::create(['name' => 'Curso bloqueado', 'code' => 'MP2']);
        $type = TypeRequest::create(['name' => 'Métrica escopo']);
        foreach ([$allowedCourse, $otherCourse] as $index => $course) {
            $student = User::create(['name' => "Aluno $index", 'email' => "scope$index@example.test", 'cpf' => '9000000310'.$index, 'phone' => '81900009991', 'matricula' => '20241MP'.($index + 1).'IG001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
            RequestModel::create(['user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'S', 'description' => 'D', 'status' => 'pending', 'protocol' => '20260909-00000'.$index]);
        }
        $admin = StaffAdmin::create(['name' => 'Admin escopado', 'email' => 'scoped-admin@example.test', 'cpf' => '90000003199', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        StaffAccessScope::create(['staff_admin_id' => $admin->id, 'course_id' => $allowedCourse->id, 'abilities' => ['view_request'], 'reason' => 'QA']);

        $this->actingAs($admin, 'staff_admins')->getJson('/api/metrics')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('open', 1);
    }
}
