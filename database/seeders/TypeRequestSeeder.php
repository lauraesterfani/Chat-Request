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
                'icon' => 'calendar'
            ],
            [
                'name' => 'Trancamento de Matrícula',
                'description' => 'Explique o motivo do trancamento.',
                'icon' => 'pause'
            ],
            [
                'name' => 'Reabertura de Matrícula',
                'description' => 'Qual o motivo da reabertura?',
                'icon' => 'lock-open'
            ],
            [
                'name' => 'Cancelamento de Matrícula',
                'description' => 'Qual o motivo do cancelamento do vínculo?',
                'icon' => 'x-circle'
            ],
            [
                'name' => 'Comp. de Matrícula / Transferência de Turno',
                'description' => 'Descreva a complementação ou o turno desejado. ; Se houver, anexe documentos comprobatórios (ex: declaração de trabalho).',
                'icon' => 'refresh-cw'
            ],

            // --- Grupo: Documentação e Certificados ---
            [
                'name' => 'Declaração de Matrícula / Vínculo',
                'description' => 'Para qual finalidade você precisa da declaração?',
                'icon' => 'file-text'
            ],
            [
                'name' => 'Histórico Escolar',
                'description' => 'Informe o ano e semestre de referência.',
                'icon' => 'clipboard'
            ],
            [
                'name' => 'Ementa de Disciplina',
                'description' => 'De qual disciplina você precisa da ementa?',
                'icon' => 'book'
            ],
            [
                'name' => 'Diploma / Certificado de Conclusão',
                'description' => 'Informe o ano e semestre de conclusão (Ex: 2024.2).',
                'icon' => 'award'
            ],
            [
                'name' => 'Guia de Transferência',
                'description' => 'Para qual instituição você deseja se transferir? ; Anexe a declaração de vaga da instituição de destino.',
                'icon' => 'external-link'
            ],

            // --- Grupo: Aproveitamento e Justificativas ---
            [
                'name' => 'Isenção de Disciplinas (Aproveitamento)',
                'description' => 'Informe quais disciplinas deseja aproveitar. ; Anexe seu Histórico Escolar e as Ementas das disciplinas.',
                'icon' => 'check-square'
            ],
            [
                'name' => 'Justificativa de Falta / 2ª Chamada',
                'description' => 'Descreva o motivo da falta ou da perda da prova. ; Anexe o(s) atestado(s) médico(s) ou declaração de trabalho.',
                'icon' => 'alert-circle'
            ],
            [
                'name' => 'Revisão de Nota ou Faltas',
                'description' => 'Especifique a disciplina e o motivo da revisão.',
                'icon' => 'search'
            ],
            [
                'name' => 'Dispensa de Prática de Educação Física',
                'description' => 'Informe o motivo da dispensa. ; Anexe o atestado médico ou declaração militar.',
                'icon' => 'activity'
            ],
            [
                'name' => 'Autorização para cursar em outra IES',
                'description' => 'Qual a instituição e quais disciplinas pretende cursar?',
                'icon' => 'globe'
            ],
        ];

        foreach ($types as $type) {
            // VERIFICAÇÃO DE SEGURANÇA: Se o nome já existir, ele ATUALIZA a descrição para o novo padrão
            $existing = DB::table('type_requests')->where('name', $type['name'])->first();

            if ($existing) {
                DB::table('type_requests')->where('id', $existing->id)->update([
                    'description' => $type['description'],
                    'updated_at' => now(),
                ]);
            } else {
                // Se não existir, ele cria o registro do zero
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