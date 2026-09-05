<?php

namespace Database\Seeders;

use App\Models\NotificationRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dados exclusivamente fictícios para validar a interface de notificações local.
 * Este seeder não é chamado pelo DatabaseSeeder de ambientes comuns.
 */
class NotificationQaSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::where('email', 'qa.student@example.test')->first();

        if (! $student) {
            $this->command?->warn('Conta QA de aluno ausente. Execute UserSeeder antes deste seeder.');

            return;
        }

        foreach ([
            ['qa:notification:message', 'message', 'Nova mensagem de atendimento', 'Esta é uma mensagem fictícia para validar a leitura individual.'],
            ['qa:notification:status', 'status', 'Atualização do requerimento', 'Esta é uma atualização fictícia para validar a leitura em lote.'],
        ] as [$key, $category, $title, $body]) {
            NotificationRecord::updateOrCreate(
                ['idempotency_key' => $key],
                [
                    'recipient_type' => 'student',
                    'recipient_id' => (string) $student->getKey(),
                    'category' => $category,
                    'title' => $title,
                    'body' => $body,
                    'channel' => 'internal',
                    'email_status' => 'not_requested',
                ],
            );
        }
    }
}
