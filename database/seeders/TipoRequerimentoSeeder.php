<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoRequerimento;

class TipoRequerimentoSeeder extends Seeder
{
    public function run(): void
    {
        $requerimentos = [
            ['nome_requerimento' => 'Admissão por Transferência e Análise Curricular'],
            ['nome_requerimento' => 'Ajuste de Matrícula Semestral'],
            ['nome_requerimento' => 'Autorização para cursar disciplinas em outras IES'],
            ['nome_requerimento' => 'Cancelamento de Matrícula'],
            ['nome_requerimento' => 'Cancelamento de Disciplina'],
            ['nome_requerimento' => 'Certificado de Conclusão'],
            ['nome_requerimento' => 'Certidão - Autenticidade'],
            ['nome_requerimento' => 'Complementação de Matrícula'],
            ['nome_requerimento' => 'Cópia Xerox de Documento'],
            ['nome_requerimento' => 'Declaração de Colação de Grau e Tramitação de Diploma (curso tecnológico)'],
            ['nome_requerimento' => 'Declaração de Matrícula ou Matrícula Vínculo'],
            ['nome_requerimento' => 'Declaração de Monitoria'],
            ['nome_requerimento' => 'Declaração para Estágio'],
            ['nome_requerimento' => 'Diploma (1ª ou 2ª via)'],
            ['nome_requerimento' => 'Dispensa da prática de Educação Física'],
            ['nome_requerimento' => 'Declaração de Tramitação de Diploma (técnico)'],
            ['nome_requerimento' => 'Ementa de disciplina'],
            ['nome_requerimento' => 'Guia de Transferência'],
            ['nome_requerimento' => 'Histórico Escolar'],
            ['nome_requerimento' => 'Isenção de disciplinas cursadas'],
            ['nome_requerimento' => 'Justificativa de falta(s) ou prova 2ª chamada'],
            ['nome_requerimento' => 'Matriz curricular'],
            ['nome_requerimento' => 'Reabertura de Matrícula'],
            ['nome_requerimento' => 'Reintegração / Estágio / TCC'],
            ['nome_requerimento' => 'Reintegração para Cursar'],
            ['nome_requerimento' => 'Solicitação de Conselho de Classe'],
            ['nome_requerimento' => 'Trancamento de Matrícula'],
            ['nome_requerimento' => 'Transferência de Turno'],
        ];

        TipoRequerimento::insert($requerimentos);
    }
}