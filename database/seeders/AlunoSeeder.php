<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Aluno; // Importe o seu model Aluno

class AlunoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Dados dos alunos para popular a tabela
        $alunos = [
            [
                'nome_completo' => 'Catarina Silva',
                'email' => 'catarina.silva@example.com',
                'password' => bcrypt('senha123'), // Use bcrypt para hashear a senha
                'cpf' => '44556677880',
                'telefone' => '11966665555',
                'identidade' => '4455667',
                'orgao_expedidor' => 'SSP/PE',
                'tipo_usuario' => 'Estudante',
            ],
            [
                'nome_completo' => 'Dylan Borges',
                'email' => 'dylan.borges@example.com',
                'password' => bcrypt('senha123'), // Use bcrypt para hashear a senha
                'cpf' => '55667788990',
                'telefone' => '11955554444',
                'identidade' => '5566778',
                'orgao_expedidor' => 'SSP/PE',
                'tipo_usuario' => 'Estudante',
            ],
            [
                'nome_completo' => 'Izabelle Alves',
                'email' => 'izabelle.alves@example.com',
                'password' => bcrypt('senha123'), // Use bcrypt para hashear a senha
                'cpf' => '66778899000',
                'telefone' => '11944443333',
                'identidade' => '6677889',
                'orgao_expedidor' => 'SSP/PE',
                'tipo_usuario' => 'Estudante',
            ],
            [
                'nome_completo' => 'Laura Esterfani',
                'email' => 'laura.esterfani@example.com',
                'password' => bcrypt('senha123'), // Use bcrypt para hashear a senha
                'cpf' => '77889900110',
                'telefone' => '11933332222',
                'identidade' => '7788990',
                'orgao_expedidor' => 'SSP/PE',
                'tipo_usuario' => 'Estudante',
            ],
            [
                'nome_completo' => 'Keila Isabelle',
                'email' => 'keila.izabelle@example.com',
                'password' => bcrypt('senha123'), // Use bcrypt para hashear a senha
                'cpf' => '88990011220',
                'telefone' => '11922221111',
                'identidade' => '8899001',
                'orgao_expedidor' => 'SSP/PE',
                'tipo_usuario' => 'Estudante',
            ],
            [
                'nome_completo' => 'Rubens Lira',
                'email' => 'rubens.lira@example.com',
                'password' => bcrypt('senha123'), // Use bcrypt para hashear a senha
                'cpf' => '22334455660',
                'telefone' => '11988887777',
                'identidade' => '2233445',
                'orgao_expedidor' => 'SSP/PE',
                'tipo_usuario' => 'Estudante',
            ],
            [
                'nome_completo' => 'Victor Gustavo',
                'email' => 'victor.gustavo@example.com',
                'password' => bcrypt('senha123'), // Use bcrypt para hashear a senha
                'cpf' => '33445566770',
                'telefone' => '11977776666',
                'identidade' => '3344556',
                'orgao_expedidor' => 'SSP/PE',
                'tipo_usuario' => 'Estudante',
            ],
        ];

        // Itera sobre o array e cria cada aluno no banco de dados
        foreach ($alunos as $aluno) {
            Aluno::create($aluno);
        }
    }
}