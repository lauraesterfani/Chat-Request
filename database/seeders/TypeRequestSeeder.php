<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TypeRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            // --- Grupo: Matrícula e Ingresso ---
            [
                'name' => 'Ajuste de Matrícula Semestral',
                'description' => 'Quais disciplinas você deseja incluir ou excluir?',
                'icon' => 'calendar',
                'requires_document' => false,
                'document_instructions' => null
            ],
            [
                'name' => 'Trancamento de Matrícula',
                'description' => 'Explique o motivo do trancamento.',
                'icon' => 'pause',
                'requires_document' => false,
                'document_instructions' => null
            ],
            [
                'name' => 'Reabertura de Matrícula',
                'description' => 'Qual o motivo da reabertura?',
                'icon' => 'lock-open',
                'requires_document' => false,
                'document_instructions' => null
            ],
            [
                'name' => 'Cancelamento de Matrícula',
                'description' => 'Qual o motivo do cancelamento do vínculo?',
                'icon' => 'x-circle',
                'requires_document' => false,
                'document_instructions' => null
            ],
            [
                'name' => 'Comp. de Matrícula / Transferência de Turno',
                'description' => 'Descreva a complementação ou o turno desejado.',
                'icon' => 'refresh-cw',
                'requires_document' => true,
                'document_instructions' => 'Se houver, anexe documentos comprobatórios (ex: declaração de trabalho).'
            ],

            // --- Grupo: Documentação e Certificados ---
            [
                'name' => 'Declaração de Matrícula / Vínculo',
                'description' => 'Para qual finalidade você precisa da declaração?',
                'icon' => 'file-text',
                'requires_document' => false,
                'document_instructions' => null
            ],
            [
                'name' => 'Histórico Escolar',
                'description' => 'Informe o ano e semestre de referência.',
                'icon' => 'clipboard',
                'requires_document' => false,
                'document_instructions' => null
            ],
            [
                'name' => 'Ementa de Disciplina',
                'description' => 'De qual disciplina você precisa da ementa?',
                'icon' => 'book',
                'requires_document' => false,
                'document_instructions' => null
            ],
            [
                'name' => 'Diploma / Certificado de Conclusão',
                'description' => 'Informe o ano e semestre de conclusão (Ex: 2024.2).',
                'icon' => 'award',
                'requires_document' => false,
                'document_instructions' => null
            ],
            [
                'name' => 'Guia de Transferência',
                'description' => 'Para qual instituição você deseja se transferir?',
                'icon' => 'external-link',
                'requires_document' => true,
                'document_instructions' => 'Anexe a declaração de vaga da instituição de destino.'
            ],

            // --- Grupo: Aproveitamento e Justificativas ---
            [
                'name' => 'Isenção de Disciplinas (Aproveitamento)',
                'description' => 'Informe quais disciplinas deseja aproveitar.',
                'icon' => 'check-square',
                'requires_document' => true,
                'document_instructions' => 'Anexe seu Histórico Escolar e as Ementas das disciplinas.'
            ],
            [
                'name' => 'Justificativa de Falta / 2ª Chamada',
                'description' => 'Descreva o motivo da falta ou da perda da prova.',
                'icon' => 'alert-circle',
                'requires_document' => true,
                'document_instructions' => 'Anexe o(s) atestado(s) médico(s) ou declaração de trabalho.'
            ],
            [
                'name' => 'Revisão de Nota ou Faltas',
                'description' => 'Especifique a disciplina e o motivo da revisão.',
                'icon' => 'search',
                'requires_document' => false,
                'document_instructions' => null
            ],
            [
                'name' => 'Dispensa de Prática de Educação Física',
                'description' => 'Informe o motivo da dispensa.',
                'icon' => 'activity',
                'requires_document' => true,
                'document_instructions' => 'Anexe o atestado médico ou declaração militar.'
            ],
            [
                'name' => 'Autorização para cursar em outra IES',
                'description' => 'Qual a instituição e quais disciplinas pretende cursar?',
                'icon' => 'globe',
                'requires_document' => false,
                'document_instructions' => null
            ],
        ];

        foreach ($types as $type) {
            $existing = DB::table('type_requests')->where('name', $type['name'])->first();

            if ($existing) {
                DB::table('type_requests')->where('id', $existing->id)->update([
                    'description' => $type['description'],
                    'requires_document' => $type['requires_document'],
                    'document_instructions' => $type['document_instructions'],
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('type_requests')->insert([
                    'id' => (string) Str::uuid(),
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'icon' => $type['icon'] ?? null,
                    'requires_document' => $type['requires_document'],
                    'document_instructions' => $type['document_instructions'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}