<?php

namespace Tests\Feature;

use App\Models\AuditRecord;
use App\Models\StaffAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffLoginAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_login_success_and_failure_are_audited_without_credentials_or_email(): void
    {
        $failedResponse = $this->postJson('/api/login/staff', [
            'email' => 'private-staff@example.test',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertHeader('X-Request-ID');

        $failed = AuditRecord::where('action', 'staff_login_failed')->firstOrFail();
        $this->assertNull($failed->actor_id);
        $this->assertNull($failed->resource_id);
        $this->assertNull($failed->ip_address);
        $this->assertSame(['reason' => 'invalid_credentials'], $failed->metadata);
        $this->assertSame('failure', $failed->outcome);
        $this->assertSame($failedResponse->headers->get('X-Request-ID'), $failed->correlation_id);
        $this->assertStringNotContainsString('private-staff@example.test', json_encode($failed->toArray()));

        $staff = StaffAdmin::create([
            'name' => 'Equipe QA',
            'email' => 'staff-login@example.test',
            'cpf' => '90000001001',
            'role' => 'cradt',
            'password' => Hash::make('Correct-Test-Password-2026!'),
            'must_change_password' => false,
            'is_active' => true,
        ]);

        $successResponse = $this->postJson('/api/login/staff', [
            'email' => 'staff-login@example.test',
            'password' => 'Correct-Test-Password-2026!',
        ])->assertOk()->assertHeader('X-Request-ID');

        $succeeded = AuditRecord::where('action', 'staff_login_succeeded')->firstOrFail();
        $this->assertSame('staff_admin', $succeeded->actor_type);
        $this->assertSame((string) $staff->id, $succeeded->actor_id);
        $this->assertSame('success', $succeeded->outcome);
        $this->assertSame($successResponse->headers->get('X-Request-ID'), $succeeded->correlation_id);
        $this->assertNull($succeeded->ip_address);
        $this->assertStringNotContainsString('staff-login@example.test', json_encode($succeeded->toArray()));
        $this->assertStringNotContainsString('Correct-Test-Password-2026!', json_encode($succeeded->toArray()));
    }

    public function test_inactive_staff_login_is_audited_as_a_rejection_without_actor_identity(): void
    {
        StaffAdmin::create([
            'name' => 'Equipe inativa',
            'email' => 'inactive-staff@example.test',
            'cpf' => '90000001002',
            'role' => 'cradt',
            'password' => Hash::make('Inactive-Test-Password-2026!'),
            'must_change_password' => false,
            'is_active' => false,
        ]);

        $this->postJson('/api/login/staff', [
            'email' => 'inactive-staff@example.test',
            'password' => 'Inactive-Test-Password-2026!',
        ])->assertUnauthorized();

        $record = AuditRecord::where('action', 'staff_login_failed')->firstOrFail();
        $this->assertNull($record->actor_id);
        $this->assertNull($record->resource_id);
        $this->assertNull($record->ip_address);
        $this->assertSame(['reason' => 'inactive_account'], $record->metadata);
    }

    public function test_staff_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login/staff', [
                'email' => 'unknown-staff@example.test',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/login/staff', [
            'email' => 'unknown-staff@example.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_staff_logout_revokes_the_session_token_and_prevents_refresh(): void
    {
        StaffAdmin::create([
            'name' => 'Equipe logout',
            'email' => 'staff-logout@example.test',
            'cpf' => '90000001003',
            'role' => 'cradt',
            'password' => Hash::make('Logout-Test-Password-2026!'),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $token = $this->postJson('/api/login/staff', [
            'email' => 'staff-logout@example.test',
            'password' => 'Logout-Test-Password-2026!',
        ])->assertOk()->json('token');

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
        $this->withToken($token)->postJson('/api/refresh')->assertUnauthorized();
    }

    public function test_forced_staff_password_reset_requires_current_password_and_revokes_the_old_token(): void
    {
        $staff = StaffAdmin::create([
            'name' => 'Equipe primeiro acesso',
            'email' => 'staff-first-login@example.test',
            'cpf' => '90000001004',
            'role' => 'cradt',
            'password' => Hash::make('Temporary-Password-2026!'),
            'must_change_password' => true,
            'is_active' => true,
        ]);
        $token = $this->postJson('/api/login/staff', [
            'email' => $staff->email,
            'password' => 'Temporary-Password-2026!',
        ])->assertOk()->json('token');

        $this->withToken($token)->postJson('/api/reset-password', [
            'current_password' => 'incorrect-temporary-password',
            'new_password' => 'New-Strong-Password-2026!',
            'new_password_confirmation' => 'New-Strong-Password-2026!',
        ])->assertUnprocessable();
        $this->withToken($token)->postJson('/api/reset-password', [
            'current_password' => 'Temporary-Password-2026!',
            'new_password' => 'weak-password',
            'new_password_confirmation' => 'weak-password',
        ])->assertUnprocessable();
        $this->assertTrue(Hash::check('Temporary-Password-2026!', $staff->fresh()->password));

        $this->withToken($token)->postJson('/api/reset-password', [
            'current_password' => 'Temporary-Password-2026!',
            'new_password' => 'New-Strong-Password-2026!',
            'new_password_confirmation' => 'New-Strong-Password-2026!',
        ])->assertOk();

        $staff->refresh();
        $this->assertFalse($staff->must_change_password);
        $this->assertTrue(Hash::check('New-Strong-Password-2026!', $staff->password));
        $this->assertDatabaseHas('audit_records', [
            'action' => 'staff_initial_password_changed',
            'actor_id' => (string) $staff->id,
            'outcome' => 'success',
        ]);
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
        $this->withToken($token)->postJson('/api/refresh')->assertUnauthorized();
    }

    public function test_regular_staff_cannot_use_the_forced_initial_password_reset_endpoint(): void
    {
        $staff = StaffAdmin::create([
            'name' => 'Equipe sem troca pendente',
            'email' => 'staff-no-reset@example.test',
            'cpf' => '90000001005',
            'role' => 'cradt',
            'password' => Hash::make('Current-Password-2026!'),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $token = $this->postJson('/api/login/staff', [
            'email' => $staff->email,
            'password' => 'Current-Password-2026!',
        ])->assertOk()->json('token');

        $this->withToken($token)->postJson('/api/reset-password', [
            'current_password' => 'Current-Password-2026!',
            'new_password' => 'New-Strong-Password-2026!',
            'new_password_confirmation' => 'New-Strong-Password-2026!',
        ])->assertForbidden();
    }

    public function test_staff_login_failures_for_separate_accounts_do_not_block_a_shared_network(): void
    {
        for ($attempt = 1; $attempt <= 100; $attempt++) {
            $this->postJson('/api/login/staff', [
                'email' => 'unknown-staff-'.$attempt.'@example.test',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/login/staff', [
            'email' => 'unknown-staff-101@example.test',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }
}
