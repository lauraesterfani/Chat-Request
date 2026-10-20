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

    public function test_student_lists_and_discards_only_own_open_drafts(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001017', 'phone' => '81900001017', 'matricula' => 'QA017', 'birthday' => '2000-01-01']);
        $other = User::factory()->create(['role' => 'student', 'cpf' => '90000001018', 'phone' => '81900001018', 'matricula' => 'QA018', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 19']);
        $own = RequestDraft::create(['user_id' => $student->id, 'type_request_id' => $type->id, 'responses' => ['subject' => 'Somente meu'], 'revision' => 1]);
        $foreign = RequestDraft::create(['user_id' => $other->id, 'type_request_id' => $type->id, 'responses' => ['subject' => 'Outro aluno'], 'revision' => 1]);

        $this->actingAs($student, 'api')->getJson('/api/drafts')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $own->id);
        $this->deleteJson('/api/drafts/'.$foreign->id)->assertForbidden();
        $this->deleteJson('/api/drafts/'.$own->id)->assertOk();
        $this->getJson('/api/drafts')->assertOk()->assertExactJson([]);
        $this->assertNotNull($own->fresh()->discarded_at);
        $this->assertNull($foreign->fresh()->discarded_at);
    }

    public function test_old_draft_migration_requires_review_and_preserves_only_compatible_answers(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001021', 'phone' => '81900001021', 'matricula' => 'QA021', 'birthday' => '2000-01-01']);
        $other = User::factory()->create(['role' => 'student', 'cpf' => '90000001022', 'phone' => '81900001022', 'matricula' => 'QA022', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Migração QA']);
        $old = FormSchemaVersion::create(['type_request_id' => $type->id, 'version' => 1, 'status' => 'superseded', 'schema' => ['fields' => [
            ['key' => 'reason', 'label' => 'Motivo', 'type' => 'short_text'],
            ['key' => 'option', 'label' => 'Opção', 'type' => 'single_select', 'options' => ['A', 'B']],
            ['key' => 'removed', 'label' => 'Antigo', 'type' => 'long_text'],
        ]]]);
        $current = FormSchemaVersion::create(['type_request_id' => $type->id, 'version' => 2, 'status' => 'published', 'schema' => ['fields' => [
            ['key' => 'reason', 'label' => 'Justificativa', 'type' => 'short_text', 'required' => true],
            ['key' => 'option', 'label' => 'Opção nova', 'type' => 'single_select', 'options' => ['B', 'C']],
            ['key' => 'new_field', 'label' => 'Campo novo', 'type' => 'long_text', 'required' => true],
        ]]]);
        $draft = RequestDraft::create(['user_id' => $student->id, 'type_request_id' => $type->id, 'form_schema_version_id' => $old->id, 'responses' => ['subject' => 'Assunto salvo', 'description' => 'Descrição salva', 'reason' => 'Compatível', 'option' => 'A', 'removed' => 'Texto antigo'], 'revision' => 3]);
        $url = '/api/drafts/'.$draft->id.'/migration';

        $this->actingAs($other, 'api')->getJson($url)->assertForbidden();
        $this->postJson($url, ['revision' => 3])->assertForbidden();
        $this->actingAs($student, 'api')->getJson($url)->assertOk()
            ->assertJsonPath('needed', true)
            ->assertJsonPath('to_version_id', $current->id)
            ->assertJsonPath('responses.reason', 'Compatível')
            ->assertJsonPath('required_keys.0', 'new_field');
        $this->putJson('/api/drafts/'.$draft->id, ['type_request_id' => $type->id, 'form_schema_version_id' => $current->id, 'responses' => ['subject' => 'Forçado'], 'revision' => 3])->assertConflict();
        $this->postJson($url, ['revision' => 2])->assertConflict()->assertJsonPath('draft.revision', 3);
        $this->postJson($url, ['revision' => 3])->assertOk()
            ->assertJsonPath('draft.form_schema_version_id', $current->id)
            ->assertJsonPath('draft.revision', 4)
            ->assertJsonPath('migration.reset_keys.0', 'option');
        $this->assertSame(['subject' => 'Assunto salvo', 'description' => 'Descrição salva', 'reason' => 'Compatível'], $draft->fresh()->responses);
        $this->getJson($url)->assertOk()->assertJsonPath('needed', false);
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

    public function test_publishing_a_new_version_supersedes_the_old_one_without_changing_its_contents(): void
    {
        $admin = StaffAdmin::create(['name' => 'Admin QA', 'email' => 'form-versions@example.test', 'cpf' => '90000008883', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        $type = TypeRequest::create(['name' => 'Tipo QA 15']);
        $endpoint = '/api/type-requests/'.$type->id.'/form-versions';
        $firstSchema = ['fields' => [['key' => 'reason', 'label' => 'Motivo', 'type' => 'short_text', 'required' => true]]];

        $first = $this->actingAs($admin, 'staff_admins')->postJson($endpoint, ['schema' => $firstSchema, 'change_summary' => 'Versão inicial'])->assertCreated()->json();
        $this->postJson('/api/form-versions/'.$first['id'].'/publish')->assertOk();
        $second = $this->postJson($endpoint, ['schema' => ['fields' => [['key' => 'reason', 'label' => 'Justificativa', 'type' => 'long_text', 'required' => true]]], 'change_summary' => 'Rótulo e tipo revisados'])->assertCreated()->json();
        $this->postJson('/api/form-versions/'.$second['id'].'/publish')->assertOk();

        $this->getJson($endpoint)->assertOk()->assertJsonCount(2)->assertJsonPath('0.id', $second['id'])->assertJsonPath('0.status', 'published')->assertJsonPath('1.status', 'superseded');
        $this->getJson('/api/type-requests/'.$type->id.'/form')->assertJsonPath('version_id', $second['id']);
        $this->assertSame($firstSchema, FormSchemaVersion::findOrFail($first['id'])->schema);
    }

    public function test_student_cannot_list_administrative_form_versions(): void
    {
        $student = User::factory()->create(['role' => 'student', 'cpf' => '90000001016', 'phone' => '81900001016', 'matricula' => 'QA016', 'birthday' => '2000-01-01']);
        $type = TypeRequest::create(['name' => 'Tipo QA 16']);

        $this->actingAs($student, 'api')->getJson('/api/type-requests/'.$type->id.'/form-versions')->assertUnauthorized();
    }

    public function test_admin_can_edit_draft_but_cannot_mutate_published_version(): void
    {
        $admin = StaffAdmin::create(['name' => 'Admin QA', 'email' => 'form-draft@example.test', 'cpf' => '90000008884', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        $type = TypeRequest::create(['name' => 'Tipo QA 17']);
        $original = ['fields' => [['key' => 'reason', 'label' => 'Motivo', 'type' => 'short_text']]];
        $revised = ['fields' => [['key' => 'reason', 'label' => 'Justificativa', 'type' => 'long_text']]];
        $created = $this->actingAs($admin, 'staff_admins')->postJson('/api/type-requests/'.$type->id.'/form-versions', ['schema' => $original])->assertCreated()->json();

        $this->postJson('/api/form-versions/'.$created['id'].'/publish')->assertUnprocessable();
        $this->putJson('/api/form-versions/'.$created['id'], ['schema' => $revised, 'change_summary' => 'Ajuste fictício'])->assertOk()->assertJsonPath('schema.fields.0.label', 'Justificativa');
        $this->postJson('/api/form-versions/'.$created['id'].'/publish')->assertOk();
        $this->putJson('/api/form-versions/'.$created['id'], ['schema' => $original])->assertConflict();
        $this->assertSame($revised, FormSchemaVersion::findOrFail($created['id'])->schema);
    }

    public function test_older_draft_cannot_replace_a_newer_form_version(): void
    {
        $admin = StaffAdmin::create(['name' => 'Admin QA', 'email' => 'form-order@example.test', 'cpf' => '90000008885', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        $type = TypeRequest::create(['name' => 'Tipo QA 18']);
        $endpoint = '/api/type-requests/'.$type->id.'/form-versions';
        $payload = ['schema' => ['fields' => []], 'change_summary' => 'Versão fictícia'];
        $old = $this->actingAs($admin, 'staff_admins')->postJson($endpoint, $payload)->assertCreated()->json();
        $new = $this->postJson($endpoint, $payload)->assertCreated()->json();

        $this->postJson('/api/form-versions/'.$old['id'].'/publish')->assertConflict();
        $this->postJson('/api/form-versions/'.$new['id'].'/publish')->assertOk();
        $this->getJson('/api/type-requests/'.$type->id.'/form')->assertJsonPath('version_id', $new['id']);
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
