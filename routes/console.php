<?php

use App\Services\OrphanDocumentCleaner;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('documents:prune-orphans {--older-than-days= : Idade mínima obrigatória, em dias} {--limit=100 : Número máximo de candidatos} {--delete : Excluir em vez de apenas listar} {--confirm= : Frase explícita de confirmação}', function (OrphanDocumentCleaner $cleaner) {
    $days = filter_var($this->option('older-than-days'), FILTER_VALIDATE_INT);
    $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
    if ($days === false || $days < config('document_cleanup.minimum_age_days') || $limit === false || $limit < 1 || $limit > config('document_cleanup.maximum_batch_size')) {
        $this->error('Informe --older-than-days inteiro positivo e --limit entre 1 e '.config('document_cleanup.maximum_batch_size').'.');

        return 1;
    }

    $delete = (bool) $this->option('delete');
    if ($delete && $this->option('confirm') !== config('document_cleanup.confirmation_phrase')) {
        $this->error('Exclusão não autorizada: informe --confirm='.config('document_cleanup.confirmation_phrase').'.');

        return 1;
    }
    if ($delete && ! app()->isDownForMaintenance() && ! app()->environment('testing')) {
        $this->error('Coloque a aplicação em manutenção e pare os workers antes de excluir arquivos.');

        return 1;
    }

    $cutoff = now()->subDays($days);
    $candidates = $cleaner->candidates($cutoff, $limit);
    $this->info($candidates->count().' documento(s) privado(s), sem vínculo e anterior(es) ao corte encontrados.');
    if (! $delete) {
        $this->comment('Simulação: nenhum arquivo ou registro foi excluído.');

        return 0;
    }

    $removed = 0;
    foreach ($candidates as $candidate) {
        if ($cleaner->remove($candidate, $cutoff)) {
            $removed++;
        }
    }
    $this->info($removed.' documento(s) excluído(s); '.($candidates->count() - $removed).' preservado(s) após revalidação.');

    return 0;
})->purpose('Simula ou remove uploads privados antigos sem vínculo com pedidos ou rascunhos');
