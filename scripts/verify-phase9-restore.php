<?php

use App\Models\Request as StudentRequest;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$request = StudentRequest::with(['documents', 'events', 'formSchemaVersion'])->first();
if (! $request || $request->documents->count() !== 1 || $request->events->count() !== 1 || ! $request->formSchemaVersion) {
    fwrite(STDERR, "Falha: pedido, versão, evento ou anexo não foi restaurado.\n");
    exit(1);
}

$document = $request->documents->first();
$expected = 'CHAT_REQUEST_PHASE9_RESTORE_FIXTURE_V1';
$actual = Storage::disk('local')->get($document->path);
if (! hash_equals(hash('sha256', $expected), hash('sha256', $actual))) {
    fwrite(STDERR, "Falha: conteúdo do anexo de teste difere do original.\n");
    exit(1);
}

$jobCount = DB::table('jobs')->count();
if ($jobCount !== 0) {
    fwrite(STDERR, "Falha: o ensaio não pode restaurar jobs pendentes para reenvio.\n");
    exit(1);
}

fwrite(STDOUT, "Restauração íntegra: 1 pedido, 1 schema, 1 evento, 1 anexo; 0 jobs para reenviar.\n");
