<?php

namespace App\Console\Commands;

use App\Models\Request as RequestModel;
use Illuminate\Console\Command;

class ReportUnresolvedRequestContexts extends Command
{
    protected $signature = 'requests:unresolved-access-contexts';

    protected $description = 'Lista requerimentos legados sem contexto histórico suficiente para escopo institucional.';

    public function handle(): int
    {
        $missingCourse = RequestModel::query()->whereNull('course_id')->orderBy('created_at')->get(['id', 'created_at']);
        $missingCampus = RequestModel::query()->whereNotNull('course_id')->whereNull('campus_id')->orderBy('created_at')->get(['id', 'created_at']);

        $this->line('Sem curso histórico confirmado: '.$missingCourse->count());
        foreach ($missingCourse as $request) {
            $this->line($request->id.' | '.optional($request->created_at)->toISOString());
        }

        $this->line('Sem campus histórico confirmado: '.$missingCampus->count());
        foreach ($missingCampus as $request) {
            $this->line($request->id.' | '.optional($request->created_at)->toISOString());
        }

        $this->comment('Relatório somente leitura. Não atribua escopos históricos com base no curso atual do aluno.');

        return self::SUCCESS;
    }
}
