<?php

namespace Tests\Feature;

use App\Models\Request as StudentRequest;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateRequestPreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_block_same_open_subject_within_its_configured_window(): void
    {
        $student = User::factory()->create([
            'role' => 'student', 'cpf' => '90000007001', 'phone' => '81900007001',
            'matricula' => 'DUP001', 'birthday' => '2000-01-01',
        ]);
        $type = TypeRequest::create(['name' => 'Serviço com regra de duplicidade', 'duplicate_window_days' => 30]);
        $payload = ['type_id' => $type->id, 'subject' => 'Revisão de nota', 'description' => 'Solicito revisão.'];

        $first = $this->actingAs($student, 'api')->postJson('/api/requests', $payload)->assertCreated();
        $duplicate = $this->postJson('/api/requests', [
            ...$payload,
            'subject' => '  REVISÃO   DE NOTA ',
            'description' => 'Texto diferente, mesmo assunto de negócio.',
        ])->assertConflict()
            ->assertJsonPath('duplicate.id', $first->json('id'))
            ->assertJsonPath('duplicate.protocol', StudentRequest::findOrFail($first->json('id'))->protocol);

        $this->assertDatabaseCount('requests', 1);
        $this->assertStringContainsString('requerimento em aberto', $duplicate->json('message'));
    }

    public function test_duplicate_policy_is_disabled_by_default_and_only_blocks_open_matching_requests(): void
    {
        $student = User::factory()->create([
            'role' => 'student', 'cpf' => '90000007002', 'phone' => '81900007002',
            'matricula' => 'DUP002', 'birthday' => '2000-01-01',
        ]);
        $defaultType = TypeRequest::create(['name' => 'Serviço sem regra de duplicidade']);
        $payload = ['type_id' => $defaultType->id, 'subject' => 'Histórico', 'description' => 'Solicito histórico.'];
        $this->actingAs($student, 'api')->postJson('/api/requests', $payload)->assertCreated();
        $this->postJson('/api/requests', $payload)->assertCreated();

        $type = TypeRequest::create(['name' => 'Serviço com janela curta', 'duplicate_window_days' => 30]);
        $configuredPayload = ['type_id' => $type->id, 'subject' => 'Declaração', 'description' => 'Solicito declaração.'];
        $created = $this->postJson('/api/requests', $configuredPayload)->assertCreated();
        StudentRequest::findOrFail($created->json('id'))->update(['status' => 'completed']);
        $this->postJson('/api/requests', $configuredPayload)->assertCreated();
    }
}
