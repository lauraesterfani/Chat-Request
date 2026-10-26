<?php

namespace Tests\Feature;

use App\Models\StaffAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypeRequestManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_cradt_can_manage_service_types_and_duplicate_policy(): void
    {
        $admin = $this->staff('admin');
        $cradt = $this->staff('cradt');

        $created = $this->actingAs($admin, 'staff_admins')->postJson('/api/type-requests', [
            'name' => 'Tipo gerenciado por admin',
            'description' => 'Pergunta inicial',
            'duplicate_window_days' => 12,
            'requires_decision' => false,
        ])->assertCreated()
            ->assertJsonPath('duplicate_window_days', 12)
            ->assertJsonPath('requires_decision', false)
            ->json();

        $this->actingAs($cradt, 'staff_admins')->putJson('/api/type-requests/'.$created['id'], [
            'name' => 'Tipo gerenciado por CRADT',
            'description' => 'Pergunta inicial',
            'duplicate_window_days' => null,
            'requires_decision' => true,
        ])->assertOk()
            ->assertJsonPath('duplicate_window_days', null)
            ->assertJsonPath('requires_decision', true);
    }

    public function test_technical_staff_cannot_manage_academic_service_types(): void
    {
        $staff = $this->staff('staff');
        $this->actingAs($staff, 'staff_admins')->postJson('/api/type-requests', ['name' => 'Não autorizado'])->assertForbidden();
    }

    private function staff(string $role): StaffAdmin
    {
        return StaffAdmin::create([
            'name' => $role,
            'email' => uniqid()."@$role.test",
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'role' => $role,
            'password' => 'secret',
            'must_change_password' => false,
        ]);
    }
}
