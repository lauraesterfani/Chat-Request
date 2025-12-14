<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeRequestSeeder extends Seeder
{
    public function run(): void
    {
        // Mapeamento dos anexos (a, b, c ...) → nome do documento
        $documentsMap = [
            'a' => 'Atestado Médico',
            'b' => 'Cópia da CTPS – Identificação e Contrato',
            'c' => 'Declaração de Transferência',
            'd' => 'Declaração da Empresa com respectivo horário',
            'e' => 'Guia de Transferência',
            'f' => 'Histórico Escolar do Ensino Fundamental (original)',
            'g' => 'Histórico Escolar do Ensino Médio (original)',
            'h' => 'Histórico Escolar do Ensino Superior (original)',
            'i' => 'Ementas das disciplinas cursadas com aprovação',
            'j' => 'Declaração de Unidade Militar',
        ];

        // Lista de requerimentos e suas letras de documentos exigidos
        $requirements = [
            'Admissão por Transferência e Análise Curricular' => ['c', 'f', 'g', 'h', 'i'],
            'Ajuste de Matrícula Semestral' => [],
            'Autorização para cursar disciplinas em outras Instituições' => ['c'],
            'Cancelamento de Matrícula' => [],
            'Cancelamento de Disciplina (especifique)' => [],
            'Certificado de Conclusão' => [],
            'Certidão – Autenticidade' => [],
            'Complementação de Matrícula (especifique)' => [],
            'Cópia Xerox de Documento (especifique)' => [],
            'Declaração de Colação de Grau e Tramitação de Diploma (curso tecnológico)' => ['a', 'b', 'd'],
            'Declaração de Monitoria' => [],
            'Declaração de Matrícula ou Matrícula Vínculo' => [],
            'Declaração para Estágio – Conclusão Ano/Semestre' => [],
            'Diploma 1ª Via (2ª) – Conclusão Ano/Semestre' => [],
            'Dispensa da prática de Educação Física' => ['a', 'i'],
            'Declaração Tramitação de Diploma (técnico)' => [],
            'Ementa de disciplina (especifique)' => [],
            'Guia de Transferência' => [],
            'Histórico Escolar – Ano/Semestre' => ['f', 'g', 'h', 'i'],
            'Isenção de disciplinas cursadas' => [],
            'Justificativa de faltas / 2ª chamada' => ['a', 'd', 'i'],
            'Matriz Curricular' => [],
            'Reabertura de Matrícula' => [],
            'Reintegração (Estágio / Entrega de Relatório de Estágio / TCC)' => [],
            'Reintegração para Cursar' => [],
            'Solicitação de Conselho de Classe' => [],
            'Trancamento de Matrícula' => [],
            'Transferência de Turno' => ['a', 'i'],
        ];

        foreach ($requirements as $requestName => $documentLetters) {
            // Busca o ID do requerimento pelo nome
            $typeRequestId = DB::table('type_requests')
                ->where('name', $requestName)
                ->value('id');

            if (!$typeRequestId) {
                continue; // Caso o requerimento não exista, ignora
            }

            foreach ($documentLetters as $letter) {
                $documentName = $documentsMap[$letter];

                // Busca o ID do documento
                $typeDocumentId = DB::table('type_documents')
                    ->where('name', $documentName)
                    ->value('id');

                if (!$typeDocumentId) {
                    continue; // Caso o documento não exista, ignora
                }

                // Insere na tabela pivô
                DB::table('document_type_request')->insert([
                    'type_request_id' => $typeRequestId,
                    'type_document_id' => $typeDocumentId,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }
    }
}
