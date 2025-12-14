<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeDocumentsSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            'RG',
            'CPF',
            'Comprovante de Residência',
            'Histórico Escolar',
            'Declaração de Matrícula',
            'Certidão de Nascimento',
            'Certidão de Casamento',
            'Foto 3x4',
            'Título de Eleitor',
            'Reservista',
            'Contrato de Estágio',
            'Boletim Escolar',
            'Atestado Médico',
            'Diploma',
            'Outros'
        ];

        $data = [];
        foreach ($documents as $doc) {
            $data[] = [
                'name' => $doc,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        DB::table('type_documents')->insert($data);
    }
}
