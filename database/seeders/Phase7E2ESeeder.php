<?php

namespace Database\Seeders;

use App\Models\FormSchemaVersion;
use App\Models\ServiceCatalogEntry;
use App\Models\ServiceOpeningWindow;
use App\Models\TypeRequest;
use Illuminate\Database\Seeder;
use LogicException;

/** Fictitious, isolated data for the API-backed Phase 7 browser test only. */
class Phase7E2ESeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new LogicException('Phase7E2ESeeder só pode ser executado em APP_ENV=testing.');
        }

        $this->call([CourseSeeder::class, TypeRequestSeeder::class, UserSeeder::class]);

        $type = TypeRequest::query()->where('name', 'Justificativa de Falta / 2ª Chamada')->firstOrFail();
        FormSchemaVersion::updateOrCreate(
            ['type_request_id' => $type->id, 'version' => 1],
            [
                'status' => 'published',
                'schema' => ['fields' => [
                    ['key' => 'term', 'label' => 'Período letivo', 'type' => 'short_text', 'required' => true],
                    ['key' => 'subjects', 'label' => 'Disciplinas para 2ª chamada', 'type' => 'subjects', 'required' => true, 'max_items' => 5],
                ]],
                'change_summary' => 'Formulário fictício para teste automatizado da Fase 7.',
                'published_at' => now(),
            ]
        );
        ServiceOpeningWindow::updateOrCreate(
            [
                'type_request_id' => $type->id,
                'campus_id' => null,
                'course_id' => null,
                'source_reference' => 'Janela fictícia exclusiva do teste automatizado Fase 7',
            ],
            [
                'starts_at' => now()->subYear(),
                'ends_at' => now()->addYear(),
                'timezone' => 'UTC',
                'status' => 'published',
            ]
        );

        $externalType = TypeRequest::updateOrCreate(
            ['name' => 'Serviço externo QA Fase 7'],
            ['description' => 'Serviço fictício para verificar canal externo sem protocolo local.']
        );
        ServiceCatalogEntry::updateOrCreate(
            ['type_request_id' => $externalType->id],
            [
                'channel' => 'external',
                'channel_instructions' => 'Use o canal oficial indicado pela CRADT fictícia do teste.',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $concurrentType = TypeRequest::updateOrCreate(
            ['name' => 'Serviço digital concorrente QA Fase 7'],
            ['description' => 'Serviço fictício para testar envio concorrente idempotente.']
        );
        ServiceOpeningWindow::updateOrCreate(
            [
                'type_request_id' => $concurrentType->id,
                'campus_id' => null,
                'course_id' => null,
                'source_reference' => 'Janela fictícia exclusiva do teste concorrente Fase 7',
            ],
            [
                'starts_at' => now()->subYear(),
                'ends_at' => now()->addYear(),
                'timezone' => 'UTC',
                'status' => 'published',
            ]
        );
    }
}
