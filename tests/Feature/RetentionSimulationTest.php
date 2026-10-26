<?php

namespace Tests\Feature;

use App\Models\AuditRecord;
use App\Models\Course;
use App\Models\Document;
use App\Models\Request as RequestModel;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RetentionSimulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_simulate_old_data_without_deleting_it_or_exposing_identifiers(): void
    {
        Storage::fake('local');
        Carbon::setTestNow('2026-10-26 12:00:00 UTC');
        $student = $this->student();
        $type = TypeRequest::create(['name' => 'Serviço de teste']);
        $old = RequestModel::create([
            'user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'Assunto privado',
            'description' => 'Descrição privada', 'protocol' => '20260101-000001',
        ]);
        $old->forceFill(['created_at' => now()->subDays(90)])->save();
        $document = Document::create([
            'user_id' => $student->id, 'name' => 'atestado.pdf', 'path' => 'private/atestado.pdf',
            'mime_type' => 'application/pdf', 'size' => 100,
        ]);
        $document->forceFill(['created_at' => now()->subDays(90)])->save();
        Storage::disk('local')->put($document->path, 'conteúdo fictício');
        $new = RequestModel::create([
            'user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'Novo',
            'description' => 'Novo', 'protocol' => '20261026-000001',
        ]);
        $new->forceFill(['created_at' => now()->subDay()])->save();
        $admin = $this->admin();

        $result = $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/retention/simulate', [
            'category' => 'requests', 'before' => now()->subDays(30)->toIso8601String(),
        ])->assertOk()
            ->assertJsonPath('policy_status', 'unapproved')
            ->assertJsonPath('holds_status', 'not_configured')
            ->assertJsonPath('holds_applied', null)
            ->assertJsonPath('coverage_status', 'relational_records_only')
            ->assertJsonPath('uncovered_dependencies.0', 'exports')
            ->assertJsonPath('deletion_executed', false)
            ->assertJsonPath('candidates_count', 1)
            ->assertJsonMissingPath('references.0.id')
            ->assertJsonMissingPath('references.0.protocol');

        $this->assertNotSame((string) $old->id, $result->json('references.0.reference'));
        $this->assertDatabaseHas('requests', ['id' => $old->id]);
        $this->assertDatabaseHas('requests', ['id' => $new->id]);
        $this->assertDatabaseHas('documents', ['id' => $document->id]);
        Storage::disk('local')->assertExists($document->path);
        $this->assertDatabaseHas('audit_records', [
            'action' => 'retention_simulation_run',
            'actor_id' => (string) $admin->id,
            'outcome' => 'success',
        ]);
        $audit = AuditRecord::where('action', 'retention_simulation_run')->firstOrFail();
        $this->assertSame(['category' => 'requests', 'cutoff' => $result->json('cutoff'), 'candidates_count' => 1, 'deletion_executed' => false], $audit->metadata);
        Carbon::setTestNow();
    }

    public function test_retention_simulation_is_restricted_and_rejects_unknown_categories_or_future_cutoffs(): void
    {
        $cradt = StaffAdmin::create([
            'name' => 'CRADT teste', 'email' => 'cradt@example.test', 'cpf' => '12345678901',
            'role' => 'cradt', 'password' => 'secret', 'must_change_password' => false,
        ]);

        $this->postJson('/api/admin/retention/simulate', ['category' => 'requests', 'before' => now()->subDay()->toIso8601String()])->assertUnauthorized();
        $this->actingAs($cradt, 'staff_admins')->postJson('/api/admin/retention/simulate', [
            'category' => 'requests', 'before' => now()->subDay()->toIso8601String(),
        ])->assertForbidden();
        $this->assertDatabaseHas('audit_records', [
            'action' => 'retention_simulation_access_denied',
            'actor_id' => (string) $cradt->id,
            'outcome' => 'denied',
        ]);

        $admin = $this->admin();
        StaffAccessScope::create([
            'staff_admin_id' => $admin->id,
            'course_id' => Course::create(['name' => 'Curso restrito', 'code' => 'RES'.uniqid()])->id,
            'abilities' => ['view_request'],
            'starts_at' => now()->subMinute(),
            'reason' => 'Escopo limitado de teste',
        ]);
        $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/retention/simulate', [
            'category' => 'requests', 'before' => now()->subDay()->toIso8601String(),
        ])->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/retention/simulate', [
            'category' => 'unknown', 'before' => now()->addDay()->toIso8601String(),
        ])->assertUnprocessable();
    }

    private function student(): User
    {
        $course = Course::create(['name' => 'Curso de teste', 'code' => 'RET'.uniqid()]);

        return User::create([
            'name' => 'Aluno fictício', 'email' => uniqid().'@student.test', 'password' => 'secret',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'phone' => '81999999999',
            'matricula' => 'M'.uniqid(), 'course_id' => $course->id, 'role' => 'student', 'birthday' => '2000-01-01',
        ]);
    }

    private function admin(): StaffAdmin
    {
        return StaffAdmin::create([
            'name' => 'Admin teste', 'email' => uniqid().'@staff.test', 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'role' => 'admin', 'password' => 'secret', 'must_change_password' => false,
        ]);
    }
}
