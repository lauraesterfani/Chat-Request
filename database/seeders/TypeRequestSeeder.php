<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
// use App\Models\TypeRequest; // Se você estiver usando o Eloquent

class TypeRequestSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Admissão por Transferência e Análise Curricular',
            'Ajuste de Matrícula Semestral',
            'Autorização para cursar disciplinas em outras Instituições de Ensino Superior',
            'Cancelamento de Matrícula',
            'Cancelamento de Disciplina',
            'Certificado de Conclusão',
            'Certidão - Autenticidade',
            'Complementação de Matrícula',
            'Cópia Xerox de Documento',
            'Declaração de Colação de Grau e Tramitação de Diploma',
            'Declaração de Matrícula ou Matrícula Vínculo',
            'Declaração de Monitoria',
            'Declaração para Estágio',
            'Diploma 1ª via/2ª via',
            'Dispensa da prática de Educação Física',
            'Declaração Tramitação de Diploma',
            'Ementa de disciplina',
            'Guia de Transferência',
            'Histórico Escolar',
            'Isenção de disciplinas cursadas',
            'Justificativa de falta(s) ou prova 2ª chamada',
            'Matriz curricular',
            'Reabertura de Matrícula',
            'Reintegração para Cursar',
            'Solicitação de Conselho de Classe',
            'Trancamento de Matrícula',
            'Transferência de Turno',
            'Lançamento de Nota',
            'Revisão de Nota',
            'Revisão de Faltas',
            'Tempo de escolaridade',
            'Outros'
        ];

        $data = [];
        foreach ($types as $type) {
            $data[] = ['name' => $type, 'created_at' => now(), 'updated_at' => now()];
        }

        // Insere a lista na tabela 'type_requests'
        DB::table('type_requests')->insert($data);
    }
}