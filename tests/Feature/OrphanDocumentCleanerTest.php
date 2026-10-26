<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Request as RequestModel;
use App\Models\RequestDraft;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrphanDocumentCleanerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_and_explicit_cleanup_preserve_every_historical_link(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001031', 'phone' => '81900001031', 'matricula' => 'QA031', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Serviço fictício']);
        $orphan = $this->document($student, 'orphan', 45);
        $linked = $this->document($student, 'linked', 45);
        $openDraft = $this->document($student, 'open-draft', 45);
        $discardedDraft = $this->document($student, 'discarded-draft', 45);
        $recent = $this->document($student, 'recent', 2);
        $legacyPublic = $this->document($student, 'public-copy', 45);
        Storage::disk('public')->put($legacyPublic->path, 'cópia pública legada');

        $request = RequestModel::create(['user_id' => $student->id, 'type_id' => $type->id, 'subject' => 'Teste', 'description' => 'Teste', 'protocol' => 'QA-ORPHAN-001']);
        $request->documents()->attach($linked);
        RequestDraft::create(['user_id' => $student->id, 'type_request_id' => $type->id, 'document_ids' => [$openDraft->id], 'revision' => 1]);
        RequestDraft::create(['user_id' => $student->id, 'type_request_id' => $type->id, 'document_ids' => [$discardedDraft->id], 'revision' => 1, 'discarded_at' => now()->subDays(20)]);

        $this->artisan('documents:prune-orphans', ['--older-than-days' => '30'])
            ->expectsOutputToContain('1 documento(s)')
            ->expectsOutputToContain('Simulação')
            ->assertExitCode(0);
        $this->assertDatabaseHas('documents', ['id' => $orphan->id]);
        Storage::disk('local')->assertExists($orphan->path);

        $this->artisan('documents:prune-orphans', ['--older-than-days' => '30', '--delete' => true])
            ->expectsOutputToContain('Exclusão não autorizada')
            ->assertExitCode(1);
        Storage::disk('local')->assertExists($orphan->path);

        $this->artisan('documents:prune-orphans', [
            '--older-than-days' => '30',
            '--delete' => true,
            '--confirm' => 'DELETE_UNLINKED_PRIVATE_DOCUMENTS',
        ])->expectsOutputToContain('1 documento(s) excluído(s)')->assertExitCode(0);

        $this->assertDatabaseMissing('documents', ['id' => $orphan->id]);
        Storage::disk('local')->assertMissing($orphan->path);
        foreach ([$linked, $openDraft, $discardedDraft, $recent, $legacyPublic] as $protected) {
            $this->assertDatabaseHas('documents', ['id' => $protected->id]);
            Storage::disk('local')->assertExists($protected->path);
        }
    }

    public function test_requires_explicit_valid_age_and_ignores_unexpected_paths_or_missing_files(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001032', 'phone' => '81900001032', 'matricula' => 'QA032', 'birthday' => '2000-01-01']);
        $unsafe = $this->document($student, 'unsafe', 45);
        $unsafe->forceFill(['path' => '../other/place.pdf'])->save();
        $missing = $this->document($student, 'missing', 45);
        Storage::disk('local')->delete($missing->path);

        $this->artisan('documents:prune-orphans')->assertExitCode(1);
        $this->artisan('documents:prune-orphans', ['--older-than-days' => '0'])->assertExitCode(1);
        $this->artisan('documents:prune-orphans', ['--older-than-days' => '30'])
            ->expectsOutputToContain('0 documento(s)')
            ->assertExitCode(0);
        $this->assertDatabaseHas('documents', ['id' => $unsafe->id]);
        $this->assertDatabaseHas('documents', ['id' => $missing->id]);
    }

    private function document(User $student, string $name, int $ageDays): Document
    {
        $path = 'documents/'.str_replace('-', '_', $name).'.pdf';
        Storage::disk('local')->put($path, 'arquivo fictício');
        $document = Document::create(['user_id' => $student->id, 'path' => $path, 'name' => $name.'.pdf', 'mime_type' => 'application/pdf', 'file_size' => 17]);
        $document->forceFill(['created_at' => now()->subDays($ageDays)])->save();

        return $document->fresh();
    }
}
