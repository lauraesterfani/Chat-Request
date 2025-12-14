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
                'description' => 'Solicitação para inclusão ou exclusão de disciplinas fora do prazo regular.',
                'icon' => 'calendar'
            ],
            [
                'name' => 'Trancamento de Matrícula',
                'description' => 'Suspensão temporária das atividades acadêmicas. O prazo de validade segue o regimento.',
                'icon' => 'pause'
            ],
            [
                'name' => 'Reabertura de Matrícula',
                'description' => 'Solicitação para retornar ao curso após período de trancamento ou abandono justificado.',
                'icon' => 'lock-open'
            ],
            [
                'name' => 'Cancelamento de Matrícula',
                'description' => 'Desligamento definitivo do curso e da instituição.',
                'icon' => 'x-circle'
            ],
            [
                'name' => 'Comp. de Matrícula / Transferência de Turno',
                'description' => 'Solicitação para mudar de turno ou complementar carga horária em outro horário.',
                'icon' => 'refresh-cw'
            ],

            // --- Grupo: Documentação e Certificados ---
            [
                'name' => 'Declaração de Matrícula / Vínculo',
                'description' => 'Documento que comprova que o aluno está regularmente matriculado no período atual.',
                'icon' => 'file-text'
            ],
            [
                'name' => 'Histórico Escolar',
                'description' => 'Documento oficial contendo notas, frequência e carga horária de todas as disciplinas cursadas.',
                'icon' => 'clipboard'
            ],
            [
                'name' => 'Ementa de Disciplina',
                'description' => 'Conteúdo programático detalhado das disciplinas cursadas (útil para transferências).',
                'icon' => 'book'
            ],
            [
                'name' => 'Diploma / Certificado de Conclusão',
                'description' => 'Solicitação de expedição de diploma (1ª ou 2ª via) ou certificado de conclusão de curso.',
                'icon' => 'award'
            ],
            [
                'name' => 'Guia de Transferência',
                'description' => 'Documento necessário para levar seu vínculo acadêmico para outra instituição.',
                'icon' => 'external-link'
            ],

            // --- Grupo: Aproveitamento e Justificativas ---
            [
                'name' => 'Isenção de Disciplinas (Aproveitamento)',
                'description' => 'Requer Histórico Escolar e Ementas das disciplinas cursadas em outra instituição (Anexos F, G, H, I).',
                'icon' => 'check-square'
            ],
            [
                'name' => 'Justificativa de Falta / 2ª Chamada',
                'description' => 'Requer comprovação documental (Atestado Médico, Declaração de Trabalho ou Militar).',
                'icon' => 'alert-circle'
            ],
            [
                'name' => 'Revisão de Nota ou Faltas',
                'description' => 'Solicitação de verificação de notas lançadas ou contagem de faltas (especificar unidade/disciplina).',
                'icon' => 'search'
            ],
            [
                'name' => 'Dispensa de Prática de Educação Física',
                'description' => 'Exclusivo para casos previstos em lei. Requer Atestado Médico, CTPS ou Declaração Militar.',
                'icon' => 'activity'
            ],
            [
                'name' => 'Autorização para cursar em outra IES',
                'description' => 'Permissão para cursar disciplinas em outras Instituições de Ensino Superior.',
                'icon' => 'globe'
            ],
        ];

        foreach ($types as $type) {
            // VERIFICAÇÃO DE SEGURANÇA: Só insere se o nome não existir
            if (DB::table('type_requests')->where('name', $type['name'])->doesntExist()) {
                DB::table('type_requests')->insert([
                    'id' => (string) Str::uuid(),
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'icon' => $type['icon'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}