<?php

namespace Tests\Feature;

use App\Models\ServiceCatalogEntry;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_exposes_only_published_service_information_not_institutional_promises(): void
    {
        $published = TypeRequest::create(['name' => 'Declaração QA', 'description' => 'Descrição fictícia', 'requires_document' => true, 'document_instructions' => 'Anexo fictício']);
        $draft = TypeRequest::create(['name' => 'Serviço rascunho']);
        ServiceCatalogEntry::create(['type_request_id' => $published->id, 'channel' => 'digital', 'status' => 'published', 'published_at' => now()]);
        ServiceCatalogEntry::create(['type_request_id' => $draft->id, 'channel' => 'digital', 'status' => 'draft']);

        $this->getJson('/api/service-catalog?q=Declaração')->assertOk()
            ->assertJsonPath('0.name', 'Declaração QA')->assertJsonPath('0.documentation.required', true)
            ->assertJsonPath('0.institutional_status', 'published')->assertJsonCount(1);
    }

    public function test_only_operational_administration_can_publish_or_edit_catalog_entries(): void
    {
        $type = TypeRequest::create(['name' => 'Serviço administrativo']);
        $admin = StaffAdmin::create(['name' => 'Admin', 'email' => 'catalog@example.test', 'cpf' => '90000008881', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);

        $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/service-catalog', [
            'type_request_id' => $type->id, 'channel' => 'external', 'channel_instructions' => 'Procure o canal oficial.', 'status' => 'review',
        ])->assertCreated()->assertJsonPath('status', 'review');

        $this->getJson('/api/service-catalog')->assertOk()->assertJsonCount(0);
    }
}
