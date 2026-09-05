<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Request as RequestModel;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use App\Services\RequestAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAccessScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_grants_and_revokes_a_scoped_request_access(): void
    {
        $course = Course::create(['name' => 'Curso QA', 'code' => 'QA']);
        $student = User::create(['name' => 'Aluno', 'email' => 'student@example.test', 'cpf' => '90000002001', 'phone' => '81900002001', 'matricula' => 'QA02001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Tipo QA']);
        $request = RequestModel::create(['user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'A', 'description' => 'B', 'status' => 'pending', 'protocol' => '20260905-123456', 'responsible_sector' => 'CRADT']);
        $admin = $this->staff('admin');
        $agent = $this->staff('cradt');

        $created = $this->actingAs($admin, 'staff_admins')->postJson('/api/staff-access-scopes', ['staff_admin_id' => $agent->id, 'course_id' => $course->id, 'sector' => 'CRADT', 'abilities' => ['view_request'], 'reason' => 'Cobertura temporária'])->assertCreated()->json();
        $this->assertTrue(app(RequestAccessService::class)->canView($agent, $request));
        $this->assertFalse(app(RequestAccessService::class)->canOperate($agent, $request, 'forward_request'));
        $this->actingAs($admin, 'staff_admins')->deleteJson('/api/staff-access-scopes/'.$created['id'])->assertOk();
        $this->assertDatabaseMissing('staff_access_scopes', ['id' => $created['id']]);
    }

    public function test_expired_scope_does_not_grant_access(): void
    {
        $staff = $this->staff('cradt');
        StaffAccessScope::create(['staff_admin_id' => $staff->id, 'abilities' => ['view_request'], 'expires_at' => now()->subMinute(), 'reason' => 'Expirado']);
        $this->assertFalse($staff->accessScopes()->first()->active());
    }

    private function staff(string $role): StaffAdmin
    {
        return StaffAdmin::create(['name' => $role, 'email' => uniqid()."@$role.test", 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'role' => $role, 'password' => 'secret', 'must_change_password' => false]);
    }
}
