<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoAnexo;

class TipoAnexoSeeder extends Seeder
{
    public function run(): void
    {
        $anexos = [
            ['nome_anexo' => 'Atestado Médico'],
            ['nome_anexo' => 'Cópia da CTPS - Identificação e Contrato'],
            ['nome_anexo' => 'Declaração de Transferência'],
            ['nome_anexo' => 'Declaração da Empresa com o respectivo horário'],
            ['nome_anexo' => 'Guia de Transferência'],
            ['nome_anexo' => 'Histórico Escolar do Ensino Fundamental (original)'],
            ['nome_anexo' => 'Histórico Escolar do Ensino Médio (original)'],
            ['nome_anexo' => 'Histórico Escolar do Ensino Superior (original)'],
            ['nome_anexo' => 'Ementas das disciplinas cursadas com Aprovação'],
            ['nome_anexo' => 'Declaração de Unidade Militar'],
        ];

        TipoAnexo::insert($anexos);
    }
}