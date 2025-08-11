<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoRequerimento;
use App\Models\TipoAnexo;

class RequerimentoAnexosExigidosSeeder extends Seeder
{
    public function run(): void
    {
        // Busca todos os registros de uma vez para otimizar as consultas
        $anexos = TipoAnexo::all()->keyBy('nome_anexo');
        $requerimentos = TipoRequerimento::all()->keyBy('nome_requerimento');

        // Função auxiliar para ligar um requerimento a uma lista de anexos
        $link = function ($reqNome, array $anexosNomes) use ($requerimentos, $anexos) {
            if (isset($requerimentos[$reqNome])) {
                $requerimento = $requerimentos[$reqNome];
                $anexosIds = [];
                foreach ($anexosNomes as $anexoNome) {
                    if (isset($anexos[$anexoNome])) {
                        $anexosIds[] = $anexos[$anexoNome]->id_tipo_anexo;
                    }
                }
                // sync() é ideal para seeders: garante que APENAS estas relações existam.
                if (!empty($anexosIds)) {
                    $requerimento->anexosExigidos()->sync($anexosIds);
                }
            }
        };

        // --- MAPEAMENTO ATUALIZADO DAS LIGAÇÕES ---

        // -> Admissão por Transferência e Análise Curricular (c,f,g,h,i)
        $link('Admissão por Transferência e Análise Curricular', [
            'Declaração de Transferência',                             // c
            'Histórico Escolar do Ensino Fundamental (original)',      // f
            'Histórico Escolar do Ensino Médio (original)',            // g
            'Histórico Escolar do Ensino Superior (original)',         // h
            'Ementas das disciplinas cursadas com Aprovação',          // i
        ]);

        // -> Declaração de Colação de Grau e Tramitação de Diploma (curso tecnológico) (a,b,d)
        $link('Declaração de Colação de Grau e Tramitação de Diploma (curso tecnológico)', [
            'Atestado Médico',                                         // a
            'Cópia da CTPS - Identificação e Contrato',                // b
            'Declaração da Empresa com o respectivo horário',          // d
        ]);

        // -> Dispensa da prática de Educação Física (a,j)
        $link('Dispensa da prática de Educação Física', [
            'Atestado Médico',                                         // a
            'Declaração de Unidade Militar',                           // j
        ]);

        // -> Isenção de disciplinas cursadas (f,g,h,i)
        $link('Isenção de disciplinas cursadas', [
            'Histórico Escolar do Ensino Fundamental (original)',      // f
            'Histórico Escolar do Ensino Médio (original)',            // g
            'Histórico Escolar do Ensino Superior (original)',         // h
            'Ementas das disciplinas cursadas com Aprovação',          // i
        ]);

        // -> Justificativa de falta(s) ou prova 2ª chamada (a,d,i)
        $link('Justificativa de falta(s) ou prova 2ª chamada', [
            'Atestado Médico',                                         // a
            'Declaração da Empresa com o respectivo horário',          // d
            'Ementas das disciplinas cursadas com Aprovação',          // i
        ]);

        // -> Transferência de Turno (a,j)
        $link('Transferência de Turno', [
            'Atestado Médico',                                         // a
            'Declaração de Unidade Militar',                           // j
        ]);
    }
}