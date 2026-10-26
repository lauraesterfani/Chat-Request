<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\StaffAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampusAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_unrestricted_global_admin_can_register_campuses_and_assign_courses(): void
    {
        $admin = $this->staff('admin');
        $course = Course::create(['name' => 'Sistemas', 'code' => 'SIS']);
        $response = $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/campuses', [
            'name' => 'Campus de teste', 'code' => 'CAMPUS-QA',
        ])->assertCreated();

        $campusId = $response->json('id');
        $this->putJson('/api/admin/courses/'.$course->id.'/campus', ['campus_id' => $campusId])->assertOk();
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'campus_id' => $campusId]);
        $this->assertDatabaseHas('audit_records', ['action' => 'campus_created', 'resource_id' => $campusId]);
        $this->assertDatabaseHas('audit_records', ['action' => 'course_campus_assigned', 'resource_id' => $course->id]);

        $this->patchJson('/api/admin/campuses/'.$campusId, ['is_active' => false])->assertOk();
        $this->putJson('/api/admin/courses/'.$course->id.'/campus', ['campus_id' => $campusId])->assertUnprocessable();
    }

    public function test_cradt_and_scoped_admin_cannot_manage_campus_records(): void
    {
        $cradt = $this->staff('cradt');
        $this->actingAs($cradt, 'staff_admins')->getJson('/api/admin/campuses')->assertForbidden();

        $admin = $this->staff('admin');
        $course = Course::create(['name' => 'Outro curso', 'code' => 'OUT']);
        \App\Models\StaffAccessScope::create([
            'staff_admin_id' => $admin->id,
            'course_id' => $course->id,
            'abilities' => ['view_request'],
            'starts_at' => now()->subMinute(),
            'reason' => 'Limite de escopo de teste.',
        ]);

        $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/campuses', [
            'name' => 'Campus bloqueado', 'code' => 'BLOCKED',
        ])->assertForbidden();
    }

    private function staff(string $role): StaffAdmin
    {
        static $index = 0;
        $index++;

        return StaffAdmin::create([
            'name' => 'Operador '.$index,
            'email' => 'operator'.$index.'@example.test',
            'cpf' => str_pad((string) (90000005000 + $index), 11, '0', STR_PAD_LEFT),
            'role' => $role,
            'password' => 'secret',
            'must_change_password' => false,
        ]);
    }
}
