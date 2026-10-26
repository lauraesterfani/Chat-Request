<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\PrivacyNotice;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class PrivacyNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoint_shows_pending_without_leaking_unpublished_versions(): void
    {
        $this->getJson('/api/privacy-notice/current')
            ->assertOk()
            ->assertJsonPath('status', 'pending_approval')
            ->assertJsonPath('notice', null);

        $admin = $this->staff('admin');
        $draft = $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/privacy-notices', [
            'title' => 'Rascunho fictício do aviso',
            'content' => 'Este é um conteúdo interno que ainda não pode ser exibido ao público.',
        ])->assertCreated()->json();
        $this->assertSame(PrivacyNotice::STATUS_DRAFT, PrivacyNotice::findOrFail($draft['id'])->status);

        $this->getJson('/api/privacy-notice/current')
            ->assertOk()
            ->assertJsonPath('status', 'pending_approval')
            ->assertJsonPath('notice', null)
            ->assertJsonMissing(['title' => 'Rascunho fictício do aviso'])
            ->assertJsonMissing(['content' => 'Este é um conteúdo interno que ainda não pode ser exibido ao público.']);

        $this->postJson('/api/admin/privacy-notices/'.$draft['id'].'/submit-review')
            ->assertOk()->assertJsonPath('status', PrivacyNotice::STATUS_REVIEW);

        $this->getJson('/api/privacy-notice/current')
            ->assertOk()->assertJsonPath('status', 'pending_approval')->assertJsonPath('notice', null);
    }

    public function test_notice_requires_complete_institutional_approval_to_publish_and_published_versions_are_immutable(): void
    {
        $admin = $this->staff('admin');
        $this->actingAs($admin, 'staff_admins');

        $first = $this->postJson('/api/admin/privacy-notices', [
            'title' => 'Aviso institucional de privacidade',
            'content' => 'Texto institucional cuja fonte e aprovação deverão ser confirmadas antes da divulgação.',
        ])->assertCreated()
            ->assertJsonPath('version', 1)
            ->assertJsonPath('status', PrivacyNotice::STATUS_DRAFT)
            ->assertJsonPath('author_id', $admin->id)
            ->json();

        $this->postJson('/api/admin/privacy-notices/'.$first['id'].'/submit-review')
            ->assertOk()->assertJsonPath('status', PrivacyNotice::STATUS_REVIEW);

        $this->postJson('/api/admin/privacy-notices/'.$first['id'].'/publish', [
            'controller_name' => 'Instituição fictícia',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'contact_channel', 'legal_basis_summary', 'retention_summary', 'source_reference',
            'institutional_approval_reference', 'effective_date',
        ]);
        $this->assertDatabaseHas('privacy_notices', ['id' => $first['id'], 'status' => PrivacyNotice::STATUS_REVIEW]);

        $publishData = $this->approvedInstitutionalFields();
        $published = $this->postJson('/api/admin/privacy-notices/'.$first['id'].'/publish', $publishData)
            ->assertOk()
            ->assertJsonPath('status', PrivacyNotice::STATUS_PUBLISHED)
            ->assertJsonPath('reviewer_id', $admin->id)
            ->assertJsonPath('source_reference', 'Ato institucional QA nº 123/2026')
            ->json();

        $this->assertNotNull($published['reviewed_at']);
        $this->assertNotNull($published['published_at']);
        $this->getJson('/api/privacy-notice/current')
            ->assertOk()
            ->assertJsonPath('status', 'published')
            ->assertJsonPath('notice.version', 1)
            ->assertJsonPath('notice.title', 'Aviso institucional de privacidade');

        $this->putJson('/api/admin/privacy-notices/'.$first['id'], [
            'title' => 'Tentativa de alterar versão publicada',
            'content' => 'Uma versão publicada não pode ter seu conteúdo substituído.',
        ])->assertStatus(409);

        $second = $this->postJson('/api/admin/privacy-notices/'.$first['id'].'/new-version')
            ->assertCreated()
            ->assertJsonPath('version', 2)
            ->assertJsonPath('status', PrivacyNotice::STATUS_DRAFT)
            ->assertJsonPath('source_reference', null)
            ->assertJsonPath('institutional_approval_reference', null)
            ->assertJsonPath('author_id', $admin->id)
            ->json();

        $this->assertDatabaseHas('privacy_notices', ['id' => $first['id'], 'version' => 1, 'status' => 'published']);
        $this->assertDatabaseHas('privacy_notices', ['id' => $second['id'], 'version' => 2, 'status' => 'draft']);
        $this->getJson('/api/privacy-notice/current')->assertJsonPath('notice.version', 1);
    }

    public function test_admin_workflow_is_restricted_to_active_global_unscoped_admins(): void
    {
        $this->getJson('/api/admin/privacy-notices')->assertUnauthorized();
        $student = User::create([
            'name' => 'Aluno QA',
            'email' => 'student-privacy-notice@example.test',
            'cpf' => '90000008001',
            'phone' => '81900008001',
            'matricula' => 'PV000001',
            'birthday' => '2000-01-01',
            'password' => 'secret',
            'role' => 'student',
        ]);
        $this->actingAs($student, 'api')->getJson('/api/admin/privacy-notices')->assertUnauthorized();

        $cradt = $this->staff('cradt');
        $this->actingAs($cradt, 'staff_admins')->getJson('/api/admin/privacy-notices')->assertForbidden();
        $inactive = $this->staff('admin');
        $inactive->forceFill(['is_active' => false])->save();
        $this->actingAs($inactive, 'staff_admins')->getJson('/api/admin/privacy-notices')->assertForbidden();

        $scoped = $this->staff('admin');
        $course = Course::create(['name' => 'Curso Privacidade', 'code' => 'PVT']);
        StaffAccessScope::create([
            'staff_admin_id' => $scoped->id,
            'course_id' => $course->id,
            'abilities' => ['view_request'],
            'starts_at' => now()->subMinute(),
            'reason' => 'Escopo restrito de teste.',
        ]);
        $this->actingAs($scoped, 'staff_admins')->getJson('/api/admin/privacy-notices')->assertForbidden();
    }

    public function test_privacy_notice_update_rolls_back_when_its_audit_event_cannot_be_written(): void
    {
        $admin = $this->staff('admin');
        $draft = $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/privacy-notices', [
            'title' => 'Rascunho original',
            'content' => 'Conteúdo original do rascunho para testar a transação de auditoria.',
        ])->assertCreated()->json();

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('record')->once()->andThrow(new \RuntimeException('synthetic audit failure'));
        $this->app->instance(AuditService::class, $audit);

        $this->putJson('/api/admin/privacy-notices/'.$draft['id'], [
            'title' => 'Alteração que precisa reverter',
            'content' => 'Este texto não pode persistir sem seu evento de auditoria correspondente.',
        ])->assertStatus(500);

        $this->assertDatabaseHas('privacy_notices', ['id' => $draft['id'], 'title' => 'Rascunho original']);
        $this->assertDatabaseMissing('privacy_notices', ['id' => $draft['id'], 'title' => 'Alteração que precisa reverter']);
    }

    private function approvedInstitutionalFields(): array
    {
        return [
            'controller_name' => 'Instituição fictícia para teste',
            'contact_channel' => 'Canal institucional fictício para teste',
            'legal_basis_summary' => 'Resumo fictício a ser validado pela área institucional competente.',
            'retention_summary' => 'Prazo fictício a ser validado antes de qualquer publicação real.',
            'source_reference' => 'Ato institucional QA nº 123/2026',
            'institutional_approval_reference' => 'Aprovação fictícia QA nº 456/2026',
            'effective_date' => '2026-11-01',
        ];
    }

    private function staff(string $role): StaffAdmin
    {
        static $sequence = 0;
        $sequence++;

        return StaffAdmin::create([
            'name' => 'Staff Aviso '.$role.$sequence,
            'email' => 'notice-'.$role.'-'.$sequence.'@example.test',
            'cpf' => str_pad((string) (90000009000 + $sequence), 11, '0', STR_PAD_LEFT),
            'role' => $role,
            'password' => Hash::make('Privacy-Notice-Test-Password-2026!'),
            'must_change_password' => false,
            'is_active' => true,
        ]);
    }
}
