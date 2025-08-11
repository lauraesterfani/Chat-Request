<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Matricula;
use App\Models\TipoRequerimento;
use App\Models\Requerimento;
use App\Models\RequerimentoAnexo;
use Carbon\Carbon;

class RequerimentoSeeder extends Seeder
{
    public function run(): void
    {
        $matriculas = Matricula::all();
        $tiposRequerimento = TipoRequerimento::with('anexosExigidos')->get()->keyBy('nome_requerimento');

        // ... verificação de segurança ...

        // --- Exemplo 1: Requerimento Deferido COM ANEXO ---
        $req1 = Requerimento::create([
            'protocolo' => '20250811-0001',
            'status' => 'Deferido',
            'observacoes' => 'Documentação verificada e aprovada pelo coordenador.',
            'data_finalizacao' => Carbon::now(),
            'id_matricula' => $matriculas->firstWhere('aluno.nome_completo', 'Catarina Silva')->id_matricula,
            'id_tipo_requerimento' => $tiposRequerimento['Justificativa de falta(s) ou prova 2ª chamada']->id_tipo_requerimento,
        ]);
        
        // Simula o anexo para o requerimento acima
        $tipoReq1 = $tiposRequerimento['Justificativa de falta(s) ou prova 2ª chamada'];
        if ($tipoReq1->anexosExigidos->isNotEmpty()) {
            RequerimentoAnexo::create([
                'id_requerimento' => $req1->id_requerimento,
                'id_tipo_anexo' => $tipoReq1->anexosExigidos->first()->id_tipo_anexo, // Pega o primeiro anexo exigido
                'caminho_arquivo' => 'anexos/atestado_medico_catarina.pdf', // Caminho FALSO
                'nome_original' => 'Atestado_2025.pdf',
            ]);
        }

        // --- Exemplo 2: Requerimento Aberto ---
        Requerimento::create([
            'protocolo' => '20250811-0002',
            'status' => 'Aberto',
            'id_matricula' => $matriculas->firstWhere('aluno.nome_completo', 'Dylan Borges')->id_matricula,
            'id_tipo_requerimento' => $tiposRequerimento['Dispensa da prática de Educação Física']->id_tipo_requerimento,
        ]);

        // ... outros requerimentos ...
    }
}