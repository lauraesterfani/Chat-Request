<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoRequerimento;
use App\Models\Requerimento;
use App\Models\RequerimentoAnexo;
use Carbon\Carbon;

class RequerimentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposRequerimento = TipoRequerimento::with('anexosExigidos')->get();

        if ($tiposRequerimento->isEmpty()) {
            $this->command->warn('Nenhum Tipo de Requerimento foi encontrado. O seeder não pode continuar.');
            return;
        }

        $tiposRequerimentoByKey = $tiposRequerimento->keyBy('nome_requerimento');

        // --- 10 REQUERIMENTOS CRIADOS MANUALMENTE ---

        // 1. Deferido (Com Anexo)
        $req1 = Requerimento::create([
            'protocolo' => '20250811-0001',
            'status' => 'Deferido',
            'observacoes' => 'Documentação verificada e aprovada pelo coordenador.',
            'data_finalizacao' => Carbon::now()->subDays(2),
            'id_matricula' => 1,
            'id_tipo_requerimento' => $tiposRequerimentoByKey['Justificativa de falta(s) ou prova 2ª chamada']->id_tipo_requerimento,
        ]);
        if ($req1->tipoRequerimento->anexosExigidos->isNotEmpty()) {
            RequerimentoAnexo::create([
                'id_requerimento' => $req1->id_requerimento,
                'id_tipo_anexo' => $req1->tipoRequerimento->anexosExigidos->first()->id_tipo_anexo,
                'caminho_arquivo' => 'anexos/fake_atestado_1.pdf',
                'nome_original' => 'Atestado_2025.pdf',
            ]);
        }

        // 2. Em Análise
        Requerimento::create([
            'protocolo' => '20250811-0002',
            'status' => 'Em Análise',
            'id_matricula' => 2,
            'id_tipo_requerimento' => $tiposRequerimentoByKey['Dispensa da prática de Educação Física']->id_tipo_requerimento,
        ]);

        // 3. Indeferido
        Requerimento::create([
            'protocolo' => '20250811-0003',
            'status' => 'Indeferido',
            'observacoes' => 'O anexo enviado não corresponde ao documento solicitado.',
            'data_finalizacao' => Carbon::now()->subDay(),
            'id_matricula' => 3,
            'id_tipo_requerimento' => $tiposRequerimentoByKey['Transferência de Turno']->id_tipo_requerimento,
        ]);

        // 4. Em Análise
        Requerimento::create([
            'protocolo' => '20250811-0004',
            'status' => 'Em Análise',
            'observacoes' => 'Aguardando parecer da coordenação do curso.',
            'id_matricula' => 4,
            'id_tipo_requerimento' => $tiposRequerimentoByKey['Isenção de disciplinas cursadas']->id_tipo_requerimento,
        ]);

        // 5. Em Análise (Sem Anexo)
        Requerimento::create([
            'protocolo' => '20250811-0005',
            'status' => 'Em Análise',
            'id_matricula' => 5,
            'id_tipo_requerimento' => $tiposRequerimentoByKey['Histórico Escolar']->id_tipo_requerimento,
        ]);

        // 6. Deferido (Sem Anexo)
        Requerimento::create([
            'protocolo' => '20250811-0006',
            'status' => 'Deferido',
            'data_finalizacao' => Carbon::now()->subDays(5),
            'id_matricula' => 6,
            'id_tipo_requerimento' => $tiposRequerimentoByKey['Cancelamento de Disciplina']->id_tipo_requerimento,
        ]);

        // 7. Pendente/Em Análise (Matrícula ID 4)
        Requerimento::create([
            'protocolo' => '20250811-0007',
            'status' => 'Em Análise',
            'observacoes' => 'Falta assinatura do responsável no documento anexado.',
            'id_matricula' => 4,
            'id_tipo_requerimento' => $tiposRequerimentoByKey['Trancamento de Matrícula']->id_tipo_requerimento,
        ]);

        // 8. Deferido (Outro requerimento para a matrícula 1)
        Requerimento::create([
            'protocolo' => '20250811-0008',
            'status' => 'Deferido',
            'data_finalizacao' => Carbon::now()->subDays(10),
            'id_matricula' => 1,
            'id_tipo_requerimento' => $tiposRequerimentoByKey['Ementa de disciplina']->id_tipo_requerimento,
        ]);

        // 9. Deferido (Matrícula ID 5)
        Requerimento::create([
            'protocolo' => '20250811-0009',
            'status' => 'Deferido',
            'data_finalizacao' => Carbon::now()->subDays(3),
            'id_matricula' => 5,
            'id_tipo_requerimento' => $tiposRequerimentoByKey['Ajuste de Matrícula Semestral']->id_tipo_requerimento,
        ]);

        // 10. Indeferido (Com Anexo)
        $req10 = Requerimento::create([
            'protocolo' => '20250811-0010',
            'status' => 'Indeferido',
            'observacoes' => 'Atestado médico com data inválida.',
            'data_finalizacao' => Carbon::now()->subHours(3),
            'id_matricula' => 3,
            'id_tipo_requerimento' => $tiposRequerimentoByKey['Justificativa de falta(s) ou prova 2ª chamada']->id_tipo_requerimento,
        ]);
        if ($req10->tipoRequerimento->anexosExigidos->isNotEmpty()) {
            RequerimentoAnexo::create([
                'id_requerimento' => $req10->id_requerimento,
                'id_tipo_anexo' => $req10->tipoRequerimento->anexosExigidos->first()->id_tipo_anexo,
                'caminho_arquivo' => 'anexos/fake_atestado_3.jpg',
                'nome_original' => 'atestado_vencido.jpg',
            ]);
        }
    }
}