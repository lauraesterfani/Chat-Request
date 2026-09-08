<?php

namespace Tests\Feature;

use App\Models\TypeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_exposes_only_service_information_not_institutional_promises(): void
    {
        TypeRequest::create(['name' => 'Declaração QA', 'description' => 'Descrição fictícia', 'requires_document' => true, 'document_instructions' => 'Anexo fictício']);
        $this->getJson('/api/service-catalog')->assertOk()->assertJsonPath('0.name', 'Declaração QA')->assertJsonPath('0.documentation.required', true)->assertJsonPath('0.institutional_status', 'pending_homologation');
    }
}
