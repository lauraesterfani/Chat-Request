<?php

namespace Tests\Feature;

use App\Models\AuditRecord;
use App\Models\Course;
use App\Models\Document;
use App\Models\Request as RequestModel;
use App\Models\RequestDraft;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAccessAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_and_draft_document_access_is_audited_without_exposing_owner_or_file_details(): void
    {
        Storage::fake('local');

        $course = Course::create(['name' => 'Curso documento QA', 'code' => 'DQA']);
        $owner = $this->student('owner');
        $outsider = $this->student('outsider');
        $type = TypeRequest::create(['name' => 'Tipo documento QA']);
        $request = RequestModel::create([
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'type_id' => $type->id,
            'subject' => 'Pedido privado',
            'description' => 'Descrição privada',
            'status' => 'pending',
            'protocol' => '20261026-920001',
            'responsible_sector' => 'CRADT',
        ]);
        $document = Document::create([
            'path' => 'documents/private-qa.png',
            'name' => 'atestado-confidencial.png',
            'mime_type' => 'image/png',
            'user_id' => $owner->id,
            'file_size' => 12,
        ]);
        Storage::disk('local')->put($document->path, 'private-bytes');
        $request->documents()->attach($document->id);
        $draft = RequestDraft::create([
            'user_id' => $owner->id,
            'type_request_id' => $type->id,
            'document_ids' => [$document->id],
        ]);

        $this->actingAs($outsider, 'api')
            ->get('/api/requests/'.$request->id.'/documents/'.$document->id)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Acesso não autorizado.']);

        $this->actingAs($owner, 'api')
            ->get('/api/requests/'.$request->id.'/documents/'.$document->id)
            ->assertOk();

        $this->actingAs($outsider, 'api')
            ->get('/api/drafts/'.$draft->id.'/documents/'.$document->id)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Acesso não autorizado.']);

        $this->actingAs($owner, 'api')
            ->get('/api/drafts/'.$draft->id.'/documents/'.$document->id)
            ->assertOk();

        $this->assertDatabaseHas('audit_records', [
            'action' => 'document_access_denied',
            'actor_type' => 'student',
            'actor_id' => (string) $outsider->id,
            'resource_type' => 'request_document',
            'resource_id' => (string) $document->id,
        ]);
        $this->assertDatabaseHas('audit_records', [
            'action' => 'document_access_succeeded',
            'actor_type' => 'student',
            'actor_id' => (string) $owner->id,
            'resource_type' => 'request_document',
            'resource_id' => (string) $document->id,
        ]);
        $this->assertDatabaseHas('audit_records', [
            'action' => 'draft_document_access_denied',
            'actor_type' => 'student',
            'actor_id' => (string) $outsider->id,
            'resource_type' => 'draft_document',
            'resource_id' => (string) $document->id,
        ]);
        $this->assertDatabaseHas('audit_records', [
            'action' => 'draft_document_access_succeeded',
            'actor_type' => 'student',
            'actor_id' => (string) $owner->id,
            'resource_type' => 'draft_document',
            'resource_id' => (string) $document->id,
        ]);

        $records = AuditRecord::whereIn('action', [
            'document_access_denied',
            'document_access_succeeded',
            'draft_document_access_denied',
            'draft_document_access_succeeded',
        ])->get();
        foreach ($records as $record) {
            $this->assertNull($record->ip_address);
            $this->assertArrayNotHasKey('name', $record->metadata ?? []);
            $this->assertArrayNotHasKey('email', $record->metadata ?? []);
            $this->assertArrayNotHasKey('file_name', $record->metadata ?? []);
            $this->assertStringNotContainsString('atestado-confidencial.png', json_encode($record->metadata));
            $this->assertStringNotContainsString('private-bytes', json_encode($record->metadata));
        }
    }

    private function student(string $suffix): User
    {
        static $sequence = 0;
        $sequence++;

        return User::create([
            'name' => 'Aluno '.$suffix,
            'email' => $suffix.'-'.$sequence.'@example.test',
            'cpf' => str_pad((string) (90000005000 + $sequence), 11, '0', STR_PAD_LEFT),
            'phone' => '8190000500'.$sequence,
            'matricula' => 'DQ'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'birthday' => '2000-01-01',
            'password' => 'secret',
            'role' => 'student',
        ]);
    }
}
