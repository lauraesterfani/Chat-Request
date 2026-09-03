<?php

namespace Database\Seeders;

use App\Models\ResponseTemplate;
use App\Models\TypeRequest;
use Illuminate\Database\Seeder;

class ResponseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // As respostas básicas ficam disponíveis para qualquer tipo de
        // requerimento. A equipe ainda pode criar templates específicos no
        // painel administrativo quando necessário.
        $types = TypeRequest::all();

        if ($types->isEmpty()) {
            return;
        }

        $templates = [
            [
                'title' => 'Documentação incompleta',
                'content' => 'Olá! Identificamos que a documentação enviada está incompleta. Por favor, anexe os documentos indicados para que possamos continuar a análise do seu requerimento.',
            ],
            [
                'title' => 'Requerimento concluído',
                'content' => 'Olá! Seu requerimento foi analisado e concluído. Consulte os detalhes do atendimento e, caso tenha alguma dúvida, envie uma nova mensagem.',
            ],
        ];

        foreach ($templates as $data) {
            $template = ResponseTemplate::firstOrCreate(
                ['title' => $data['title']],
                [...$data, 'is_active' => true, 'created_by' => null],
            );

            $template->typeRequests()->syncWithoutDetaching($types->modelKeys());
        }
    }
}
