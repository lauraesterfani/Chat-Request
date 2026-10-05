<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\FormSchemaVersion;
use App\Models\RequestDraft;
use App\Models\StaffAdmin;
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

    public function test_updating_draft_requires_current_revision_and_preserves_newer_content(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001004', 'phone' => '81900001004', 'matricula' => 'QA004', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 3']);
        $draft = $this->actingAs($student, 'api')->postJson('/api/drafts', ['type_request_id' => $type->id, 'responses' => ['subject' => 'Primeira aba']])->assertCreated()->json();

        $this->putJson('/api/drafts/'.$draft['id'], ['type_request_id' => $type->id, 'responses' => ['subject' => 'Sem revisão']])->assertUnprocessable();
        $this->putJson('/api/drafts/'.$draft['id'], ['type_request_id' => $type->id, 'responses' => ['subject' => 'Segunda aba'], 'revision' => 1])->assertOk()->assertJsonPath('revision', 2);
        $this->putJson('/api/drafts/'.$draft['id'], ['type_request_id' => $type->id, 'responses' => ['subject' => 'Primeira aba atrasada'], 'revision' => 1])->assertConflict()->assertJsonPath('draft.revision', 2);

        $this->assertDatabaseHas('request_drafts', ['id' => $draft['id'], 'revision' => 2]);
        $this->assertSame('Segunda aba', RequestDraft::findOrFail($draft['id'])->responses['subject']);
    }

    public function test_draft_rejects_form_version_from_another_service(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001005', 'phone' => '81900001005', 'matricula' => 'QA005', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 4']);
        $otherType = TypeRequest::create(['name' => 'Tipo QA 5']);
        $schema = FormSchemaVersion::create(['type_request_id' => $otherType->id, 'version' => 1, 'status' => 'published', 'schema' => ['fields' => []]]);

        $this->actingAs($student, 'api')->postJson('/api/drafts', ['type_request_id' => $type->id, 'form_schema_version_id' => $schema->id])->assertUnprocessable();
        $this->assertDatabaseCount('request_drafts', 0);
    }

    public function test_student_cannot_use_unpublished_form_version_in_draft(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001014', 'phone' => '81900001014', 'matricula' => 'QA014', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 12']);
        $schema = FormSchemaVersion::create(['type_request_id' => $type->id, 'version' => 1, 'status' => 'draft', 'schema' => ['fields' => []]]);

        $this->actingAs($student, 'api')->postJson('/api/drafts', ['type_request_id' => $type->id, 'form_schema_version_id' => $schema->id])->assertUnprocessable();
        $this->assertDatabaseCount('request_drafts', 0);
    }

    public function test_draft_rejects_document_owned_by_another_student(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001006', 'phone' => '81900001006', 'matricula' => 'QA006', 'birthday' => '2000-01-01']);
        $other = User::factory()->create(['role' => 'student', 'cpf' => '90000001007', 'phone' => '81900001007', 'matricula' => 'QA007', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 6']);
        $document = Document::create(['user_id' => $other->id, 'path' => 'private/qa.pdf', 'name' => 'qa.pdf', 'mime_type' => 'application/pdf', 'file_size' => 123]);

        $this->actingAs($student, 'api')->postJson('/api/drafts', ['type_request_id' => $type->id, 'document_ids' => [$document->id]])->assertForbidden();
        $this->assertDatabaseCount('request_drafts', 0);
    }

    public function test_published_form_cannot_be_bypassed_by_omitting_its_version(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001008', 'phone' => '81900001008', 'matricula' => 'QA008', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 7']);
        $schema = FormSchemaVersion::create(['type_request_id' => $type->id, 'version' => 1, 'status' => 'published', 'schema' => ['fields' => [['key' => 'reason', 'label' => 'Motivo', 'type' => 'short_text', 'required' => true]]]]);
        $payload = ['type_id' => $type->id, 'subject' => 'Assunto', 'description' => 'Descrição'];

        $this->actingAs($student, 'api')->postJson('/api/requests', $payload)->assertConflict()->assertJsonPath('form_schema_version_id', $schema->id);
        $this->postJson('/api/requests', [...$payload, 'form_schema_version_id' => $schema->id])->assertUnprocessable();
        $this->assertDatabaseCount('requests', 0);
    }

    public function test_submission_rejects_outdated_published_form_version(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001009', 'phone' => '81900001009', 'matricula' => 'QA009', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 8']);
        $old = FormSchemaVersion::create(['type_request_id' => $type->id, 'version' => 1, 'status' => 'published', 'schema' => ['fields' => []]]);
        $current = FormSchemaVersion::create(['type_request_id' => $type->id, 'version' => 2, 'status' => 'published', 'schema' => ['fields' => []]]);

        $this->actingAs($student, 'api')->postJson('/api/requests', ['type_id' => $type->id, 'subject' => 'Assunto', 'description' => 'Descrição', 'form_schema_version_id' => $old->id])->assertConflict()->assertJsonPath('form_schema_version_id', $current->id);
        $this->assertDatabaseCount('requests', 0);
    }

    public function test_student_cannot_submit_another_students_draft(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001010', 'phone' => '81900001010', 'matricula' => 'QA010', 'birthday' => '2000-01-01']);
        $other = User::factory()->create(['role' => 'student', 'cpf' => '90000001011', 'phone' => '81900001011', 'matricula' => 'QA011', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 9']);
        $draft = RequestDraft::create(['user_id' => $other->id, 'type_request_id' => $type->id, 'responses' => ['subject' => 'Privado'], 'revision' => 1]);

        $this->actingAs($student, 'api')->postJson('/api/requests', ['type_id' => $type->id, 'subject' => 'Assunto', 'description' => 'Descrição', 'draft_id' => $draft->id])->assertForbidden();
        $this->assertDatabaseCount('requests', 0);
        $this->assertNull($draft->fresh()->submitted_at);
    }

    public function test_submission_key_replays_same_payload_but_rejects_changed_payload(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001012', 'phone' => '81900001012', 'matricula' => 'QA012', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 10']);
        $payload = ['type_id' => $type->id, 'subject' => 'Assunto', 'description' => 'Descrição', 'idempotency_key' => 'qa-submission-key-0001'];

        $created = $this->actingAs($student, 'api')->postJson('/api/requests', $payload)->assertCreated()->json();
        $this->postJson('/api/requests', $payload)->assertOk()->assertJsonPath('id', $created['id'])->assertJsonPath('idempotent_replay', true);
        $this->postJson('/api/requests', [...$payload, 'subject' => 'Outro assunto'])->assertConflict();
        $this->assertDatabaseCount('requests', 1);
    }

    public function test_idempotency_header_is_validated_and_can_replay_a_request(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001013', 'phone' => '81900001013', 'matricula' => 'QA013', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 11']);
        $payload = ['type_id' => $type->id, 'subject' => 'Assunto', 'description' => 'Descrição'];

        $this->actingAs($student, 'api')->withHeader('Idempotency-Key', 'short')->postJson('/api/requests', $payload)->assertUnprocessable();
        $created = $this->withHeader('Idempotency-Key', 'qa-header-only-key-0001')->postJson('/api/requests', $payload)->assertCreated()->json();
        $this->withHeader('Idempotency-Key', 'qa-header-only-key-0001')->postJson('/api/requests', $payload)->assertOk()->assertJsonPath('id', $created['id']);
        $this->assertDatabaseCount('requests', 1);
    }

    public function test_administrator_can_publish_valid_version_but_not_unsafe_schema(): void
    {
        $admin = StaffAdmin::create(['name' => 'Admin QA', 'email' => 'form-admin@example.test', 'cpf' => '90000008882', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        $type = TypeRequest::create(['name' => 'Tipo QA 13']);
        $endpoint = '/api/type-requests/'.$type->id.'/form-versions';
        $this->actingAs($admin, 'staff_admins')->postJson($endpoint, ['schema' => ['fields' => [['key' => 'reason', 'label' => 'Motivo', 'type' => 'short_text', 'script' => 'alert(1)']]]])->assertUnprocessable();

        $created = $this->postJson($endpoint, ['schema' => ['fields' => [['key' => 'reason', 'label' => 'Motivo', 'type' => 'short_text', 'required' => true]]], 'change_summary' => 'Versão inicial fictícia'])->assertCreated()->assertJsonPath('version', 1)->json();
        $this->postJson('/api/form-versions/'.$created['id'].'/publish')->assertOk()->assertJsonPath('status', 'published');
        $this->postJson('/api/form-versions/'.$created['id'].'/publish')->assertConflict();
        $this->getJson('/api/type-requests/'.$type->id.'/form')->assertOk()->assertJsonPath('version_id', $created['id']);
    }

    public function test_custom_schema_uses_fixed_subject_and_description_plus_dynamic_responses(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001015', 'phone' => '81900001015', 'matricula' => 'QA015', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 14']);
        $schema = FormSchemaVersion::create(['type_request_id' => $type->id, 'version' => 1, 'status' => 'published', 'schema' => ['fields' => [['key' => 'reason', 'label' => 'Motivo', 'type' => 'short_text', 'required' => true]]]]);

        $created = $this->actingAs($student, 'api')->postJson('/api/requests', ['type_id' => $type->id, 'subject' => 'Assunto principal', 'description' => 'Descrição principal', 'form_schema_version_id' => $schema->id, 'form_responses' => ['reason' => 'Motivo adicional']])->assertCreated()->json();
        $this->assertDatabaseHas('requests', ['id' => $created['id'], 'subject' => 'Assunto principal', 'description' => 'Descrição principal', 'form_schema_version_id' => $schema->id]);
    }
}
