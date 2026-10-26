<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StaffAccountAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_technical_staff_cannot_create_academic_administrators_or_operational_roles(): void
    {
        $technical = $this->staff('staff');
        $this->actingAs($technical, 'staff_admins')->postJson('/api/staff-admins', [
            'name' => 'Admin indevido', 'email' => 'forbidden-admin@example.test', 'role' => 'admin',
        ])->assertForbidden();

        $this->actingAs($technical, 'staff_admins')->postJson('/api/staffs', [
            'name' => 'CRADT indevido', 'email' => 'forbidden-cradt@example.test', 'phone' => '81999990000',
            'cpf' => '90000000010', 'role' => 'admin',
        ])->assertUnprocessable();
    }

    public function test_global_admin_can_create_and_deactivate_accounts_without_deleting_history(): void
    {
        $admin = $this->staff('admin');
        $createdResponse = $this->actingAs($admin, 'staff_admins')->postJson('/api/staff-admins', [
            'name' => 'Coordenação QA', 'email' => 'coord-qa@example.test', 'role' => 'coordenacao',
            'course_id' => null, 'phone' => '81999990001', 'cpf' => '90000003099',
        ]);
        $createdResponse->assertCreated()->assertJsonPath('user.role', 'coordenacao');

        $target = StaffAdmin::where('email', 'coord-qa@example.test')->firstOrFail();
        StaffAccessScope::create([
            'staff_admin_id' => $target->id,
            'course_id' => Course::create(['name' => 'Curso preservado', 'code' => 'CAP'])->id,
            'abilities' => ['view_request'],
            'starts_at' => now()->subMinute(),
            'reason' => 'Escopo histórico',
        ]);
        $staleToken = auth('staff_admins')->login($target->fresh());

        $this->actingAs($admin, 'staff_admins')->deleteJson('/api/staff-admins/'.$target->id)->assertOk();
        $this->assertDatabaseHas('staff_admins', ['id' => $target->id, 'is_active' => false]);
        $this->assertDatabaseHas('staff_access_scopes', ['staff_admin_id' => $target->id]);
        $this->assertDatabaseHas('audit_records', ['action' => 'staff_account_deactivated', 'resource_id' => (string) $target->id]);

        $this->actingAs($target->fresh(), 'staff_admins')->getJson('/api/notifications')->assertForbidden();
        $this->withToken($staleToken)->getJson('/api/me')->assertUnauthorized();
        $this->withToken($staleToken)->postJson('/api/refresh')->assertUnauthorized();
    }

    public function test_scoped_admin_cannot_manage_accounts_or_delegate_from_a_limited_grant(): void
    {
        $admin = $this->staff('admin');
        $course = Course::create(['name' => 'Curso do escopo admin', 'code' => 'CAS']);
        StaffAccessScope::create([
            'staff_admin_id' => $admin->id,
            'course_id' => $course->id,
            'abilities' => ['view_request'],
            'starts_at' => now()->subMinute(),
            'reason' => 'Admin temporariamente restrito',
        ]);

        $this->actingAs($admin, 'staff_admins')->getJson('/api/staff-access-scopes')->assertForbidden();
        $this->actingAs($admin, 'staff_admins')->getJson('/api/staff-admins')->assertForbidden();
    }

    public function test_last_administrator_and_its_own_account_cannot_be_deactivated(): void
    {
        $admin = $this->staff('admin');
        $second = $this->staff('admin');

        $this->actingAs($admin, 'staff_admins')->deleteJson('/api/staff-admins/'.$admin->id)->assertUnprocessable();
        $this->deleteJson('/api/staff-admins/'.$second->id)->assertOk();
        $this->deleteJson('/api/staff-admins/'.$admin->id)->assertUnprocessable();
        $this->assertDatabaseHas('staff_admins', ['id' => $admin->id, 'is_active' => true]);
        $this->assertDatabaseHas('staff_admins', ['id' => $second->id, 'is_active' => false]);
    }

    public function test_staff_account_changes_roll_back_when_audit_recording_fails(): void
    {
        $admin = $this->staff('admin');
        $target = $this->staff('cradt');
        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('record')->twice()->andThrow(new \RuntimeException('synthetic audit failure'));
        $this->app->instance(AuditService::class, $audit);

        $this->actingAs($admin, 'staff_admins')->deleteJson('/api/staff-admins/'.$target->id)->assertStatus(500);
        $storeResponse = $this->actingAs($admin, 'staff_admins')->postJson('/api/staff-admins', [
            'name' => 'Conta rollback',
            'email' => 'account-rollback@example.test',
            'cpf' => '90000009991',
            'role' => 'cradt',
        ]);
        $storeResponse->assertStatus(500);
        $this->assertDatabaseHas('staff_admins', ['id' => $target->id, 'is_active' => true]);
        $this->assertDatabaseMissing('staff_admins', ['email' => 'account-rollback@example.test']);
        $this->assertDatabaseMissing('audit_records', ['action' => 'staff_account_deactivated', 'resource_id' => (string) $target->id]);
    }

    private function staff(string $role): StaffAdmin
    {
        static $index = 0;
        $index++;

        return StaffAdmin::create([
            'name' => $role.' '.$index,
            'email' => $role.$index.'@example.test',
            'cpf' => str_pad((string) (90000003000 + $index), 11, '0', STR_PAD_LEFT),
            'role' => $role,
            'password' => 'secret',
            'must_change_password' => false,
        ]);
    }
}
