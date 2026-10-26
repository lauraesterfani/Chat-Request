<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\User;
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

    public function test_hard_duplicate_block_requires_a_rule_reference_and_unrestricted_administrator(): void
    {
        $admin = $this->staff('admin');
        $this->actingAs($admin, 'staff_admins')->postJson('/api/type-requests', [
            'name' => 'Tipo sem referência',
            'duplicate_mode' => 'block',
            'duplicate_window_days' => 30,
        ])->assertUnprocessable()->assertJsonValidationErrors('duplicate_rule_reference');

        $course = Course::create(['name' => 'Curso de teste', 'code' => 'TYPE-QA']);
        StaffAccessScope::create([
            'staff_admin_id' => $admin->id,
            'course_id' => $course->id,
            'abilities' => ['view_request'],
            'reason' => 'Escopo de QA',
        ]);

        $this->postJson('/api/type-requests', [
            'name' => 'Tipo com bloqueio',
            'duplicate_mode' => 'block',
            'duplicate_window_days' => 30,
            'duplicate_rule_reference' => 'DECISAO-QA-01',
        ])->assertForbidden();
    }

    public function test_public_service_type_list_hides_internal_rule_reference_and_admin_list_includes_it(): void
    {
        $admin = $this->staff('admin');
        $type = $this->actingAs($admin, 'staff_admins')->postJson('/api/type-requests', [
            'name' => 'Tipo para consulta pública autenticada',
            'duplicate_window_days' => 30,
            'duplicate_mode' => 'block',
            'duplicate_rule_reference' => 'DECISAO-QA-02',
        ])->assertCreated()->json();

        $this->getJson('/api/type-requests')->assertOk()->assertJsonMissingPath('0.duplicate_rule_reference');

        $student = User::factory()->create([
            'role' => 'student', 'cpf' => '90000007111', 'phone' => '81900007111',
            'matricula' => 'TYPE-QA-111', 'birthday' => '2000-01-01', 'must_change_password' => false,
        ]);
        $this->actingAs($student, 'api')->getJson('/api/type-requests')
            ->assertOk()->assertJsonMissingPath('0.duplicate_rule_reference')
            ->assertJsonPath('0.duplicate_mode', 'block');

        $this->actingAs($admin, 'staff_admins')->getJson('/api/admin/type-requests')
            ->assertOk()->assertJsonPath('0.duplicate_rule_reference', 'DECISAO-QA-02');
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
