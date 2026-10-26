<?php

namespace Tests\Feature;

use App\Models\Request as StudentRequest;
use App\Models\ServiceOpeningWindow;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateRequestPreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_warns_before_allowing_an_acknowledged_same_student_duplicate(): void
    {
        $student = User::factory()->create([
            'role' => 'student', 'cpf' => '90000007001', 'phone' => '81900007001',
            'matricula' => 'DUP001', 'birthday' => '2000-01-01',
        ]);
        $type = TypeRequest::create(['name' => 'Serviço com regra de duplicidade', 'duplicate_window_days' => 30]);
        $this->publishGlobalWindow($type);
        $payload = ['type_id' => $type->id, 'subject' => 'Revisão de nota', 'description' => 'Solicito revisão.'];

        $first = $this->actingAs($student, 'api')->postJson('/api/requests', $payload)->assertCreated();
        $payload['idempotency_key'] = 'qa-duplicate-warn-retry-01';
        $duplicate = $this->postJson('/api/requests', [
            ...$payload,
            'subject' => '  REVISÃO   DE NOTA ',
            'description' => 'Texto diferente, mesmo assunto de negócio.',
        ])->assertConflict()
            ->assertJsonPath('duplicate_mode', 'warn')
            ->assertJsonPath('duplicate_matches.0.id', $first->json('id'))
            ->assertJsonPath('duplicate_matches.0.protocol', StudentRequest::findOrFail($first->json('id'))->protocol);

        $this->assertDatabaseCount('requests', 1);
        $this->assertStringContainsString('antes de continuar', $duplicate->json('message'));

        $this->postJson('/api/requests', [
            ...$payload,
            'subject' => '  REVISÃO   DE NOTA ',
            'description' => 'Texto diferente, mesmo assunto de negócio.',
            'duplicate_acknowledged' => true,
        ])->assertCreated();
        $this->assertDatabaseCount('requests', 2);
    }

    public function test_duplicate_warning_is_limited_to_the_same_student_and_never_exposes_other_students_requests(): void
    {
        $student = User::factory()->create([
            'role' => 'student', 'cpf' => '90000007003', 'phone' => '81900007003',
            'matricula' => 'DUP003', 'birthday' => '2000-01-01',
        ]);
        $other = User::factory()->create([
            'role' => 'student', 'cpf' => '90000007004', 'phone' => '81900007004',
            'matricula' => 'DUP004', 'birthday' => '2000-01-01',
        ]);
        $type = TypeRequest::create(['name' => 'Serviço com sugestão']);
        $this->publishGlobalWindow($type);
        $payload = ['type_id' => $type->id, 'subject' => 'Histórico Escolar', 'description' => 'Pedido de histórico.'];
        $created = $this->actingAs($student, 'api')->postJson('/api/requests', $payload)->assertCreated();
        $otherRequest = $this->actingAs($other, 'api')->postJson('/api/requests', $payload)->assertCreated();

        $matches = $this->actingAs($student, 'api')->postJson('/api/requests/duplicates/check', [
            'type_id' => $type->id,
            'subject' => '  HISTÓRICO   ESCOLAR ',
        ])->assertOk()->assertJsonPath('mode', 'warn')->assertJsonPath('matches.0.id', $created->json('id'));

        $this->assertFalse(collect($matches->json('matches'))->contains('id', $otherRequest->json('id')));
        $this->assertArrayNotHasKey('description', $matches->json('matches.0'));
    }

    public function test_hard_block_requires_an_explicit_rule_and_cannot_be_bypassed_by_acknowledgement(): void
    {
        $student = User::factory()->create([
            'role' => 'student', 'cpf' => '90000007005', 'phone' => '81900007005',
            'matricula' => 'DUP005', 'birthday' => '2000-01-01',
        ]);
        $type = TypeRequest::create([
            'name' => 'Serviço com bloqueio homologado',
            'duplicate_window_days' => 30,
            'duplicate_mode' => 'block',
            'duplicate_rule_reference' => 'REGRA-QA-01',
        ]);
        $this->publishGlobalWindow($type);
        $payload = ['type_id' => $type->id, 'subject' => 'Ajuste de matrícula', 'description' => 'Primeiro pedido.'];
        $this->actingAs($student, 'api')->postJson('/api/requests', $payload)->assertCreated();

        $this->postJson('/api/requests', [
            ...$payload,
            'description' => 'Segundo pedido.',
            'duplicate_acknowledged' => true,
        ])->assertConflict()->assertJsonPath('duplicate_mode', 'block');

        $this->assertDatabaseCount('requests', 1);
    }

    public function test_duplicate_policy_defaults_to_warning_and_terminal_requests_do_not_warn(): void
    {
        $student = User::factory()->create([
            'role' => 'student', 'cpf' => '90000007002', 'phone' => '81900007002',
            'matricula' => 'DUP002', 'birthday' => '2000-01-01',
        ]);
        $defaultType = TypeRequest::create(['name' => 'Serviço sem regra de duplicidade']);
        $this->publishGlobalWindow($defaultType);
        $payload = ['type_id' => $defaultType->id, 'subject' => 'Histórico', 'description' => 'Solicito histórico.'];
        $this->actingAs($student, 'api')->postJson('/api/requests', $payload)->assertCreated();
        $this->postJson('/api/requests', [...$payload, 'duplicate_acknowledged' => true])->assertCreated();

        $type = TypeRequest::create(['name' => 'Serviço com janela curta', 'duplicate_window_days' => 30]);
        $this->publishGlobalWindow($type);
        $configuredPayload = ['type_id' => $type->id, 'subject' => 'Declaração', 'description' => 'Solicito declaração.'];
        $created = $this->postJson('/api/requests', $configuredPayload)->assertCreated();
        StudentRequest::findOrFail($created->json('id'))->update(['status' => 'completed']);
        $this->postJson('/api/requests', $configuredPayload)->assertCreated();
    }

    private function publishGlobalWindow(TypeRequest $type): void
    {
        ServiceOpeningWindow::create([
            'type_request_id' => $type->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
            'timezone' => 'America/Recife',
            'status' => 'published',
        ]);
    }
}
