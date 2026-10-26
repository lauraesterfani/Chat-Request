<?php

namespace Tests\Feature;

use App\Models\StaffAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_health_endpoint_exposes_only_basic_availability(): void
    {
        $this->getJson('/api/health')->assertOk()->assertExactJson(['status' => 'ok']);
        $this->getJson('/api/health?detailed=true')->assertOk()->assertExactJson(['status' => 'ok']);
    }

    public function test_detailed_health_is_only_available_to_authenticated_technical_staff(): void
    {
        $this->getJson('/api/health/detailed')->assertUnauthorized();

        $admin = $this->staff('admin');
        $this->actingAs($admin, 'staff_admins')->getJson('/api/health/detailed')->assertForbidden();

        $technical = $this->staff('staff');
        $this->actingAs($technical, 'staff_admins')->getJson('/api/health/detailed')->assertOk()
            ->assertJsonPath('checks.database', true)
            ->assertJsonStructure(['status', 'checks' => ['database', 'queue_jobs' => ['pending']], 'timestamp']);
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
