<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Request as RequestModel;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentPrivateStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_public_documents_are_not_served_and_can_be_migrated_with_verification(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $student = User::create([
            'name' => 'Aluno de migração',
            'email' => 'private-storage@example.test',
            'cpf' => '90000009871',
            'phone' => '81900009871',
            'matricula' => 'PRIVSTORE001',
            'birthday' => '2000-01-01',
            'password' => 'secret',
            'role' => 'student',
        ]);
        $type = TypeRequest::create(['name' => 'Serviço de migração']);
        $request = RequestModel::create([
            'user_id' => $student->id,
            'type_id' => $type->id,
            'subject' => 'Documento legado',
            'description' => 'Teste da migração privada.',
            'status' => 'pending',
            'protocol' => '20261026-910001',
        ]);
        $document = Document::create([
            'path' => 'documents/legacy-file.png',
            'name' => 'nome-original-privado.png',
            'mime_type' => 'image/png',
            'user_id' => $student->id,
            'file_size' => 12,
        ]);
        Storage::disk('public')->put($document->path, 'legacy-private-bytes');
        $request->documents()->attach($document->id);

        $this->actingAs($student, 'api')
            ->get('/api/requests/'.$request->id.'/documents/'.$document->id)
            ->assertNotFound();

        $this->artisan('documents:migrate-to-private')
            ->expectsOutput('Simulação concluída; nenhum arquivo foi alterado.')
            ->assertSuccessful();
        Storage::disk('public')->assertExists($document->path);
        Storage::disk('local')->assertMissing($document->path);

        $exitCode = Artisan::call('documents:migrate-to-private', ['--apply' => true]);
        $this->assertSame(0, $exitCode);
        Storage::disk('public')->assertMissing($document->path);
        Storage::disk('local')->assertExists($document->path);
        $this->assertSame('legacy-private-bytes', Storage::disk('local')->get($document->path));

        $this->actingAs($student, 'api')
            ->get('/api/requests/'.$request->id.'/documents/'.$document->id)
            ->assertOk();
    }
}
