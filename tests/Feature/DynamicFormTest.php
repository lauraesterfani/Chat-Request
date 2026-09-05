<?php

namespace Tests\Feature;

use App\Models\FormSchemaVersion;
use App\Models\RequestDraft;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_save_only_own_draft_with_revision_guard(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001001', 'phone' => '81900001001', 'matricula' => 'QA001', 'birthday' => '2000-01-01']);
        $other = User::factory()->create(['role' => 'student', 'cpf' => '90000001002', 'phone' => '81900001002', 'matricula' => 'QA002', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 1']);
        $draft = $this->actingAs($student, 'api')->postJson('/api/drafts', ['type_request_id' => $type->id, 'responses' => ['subject' => 'Rascunho']])->assertCreated()->json();

        $this->actingAs($other, 'api')->putJson('/api/drafts/'.$draft['id'], ['type_request_id' => $type->id, 'responses' => []])->assertForbidden();
        $this->actingAs($student, 'api')->putJson('/api/drafts/'.$draft['id'], ['type_request_id' => $type->id, 'responses' => [], 'revision' => 99])->assertConflict();
        $this->assertDatabaseHas('request_drafts', ['id' => $draft['id'], 'user_id' => $student->id]);
    }

    public function test_published_schema_rejects_unknown_or_missing_required_fields(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001003', 'phone' => '81900001003', 'matricula' => 'QA003', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 2']);
        $schema = FormSchemaVersion::create(['type_request_id' => $type->id, 'version' => 1, 'status' => 'published', 'schema' => ['fields' => [['key' => 'subject', 'label' => 'Assunto', 'type' => 'short_text', 'required' => true], ['key' => 'description', 'label' => 'Descrição', 'type' => 'long_text', 'required' => true]]]]);

        $this->actingAs($student, 'api')->postJson('/api/requests', ['type_id' => $type->id, 'subject' => 'A', 'description' => 'B', 'form_schema_version_id' => $schema->id, 'form_responses' => ['unknown' => 'x']])->assertStatus(422);
    }
}
