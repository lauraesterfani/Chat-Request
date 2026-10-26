<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MigratePublicDocumentsToPrivate extends Command
{
    protected $signature = 'documents:migrate-to-private {--apply : Copy and verify legacy files, then remove public copies}';

    protected $description = 'Moves legacy document files from the public disk to private storage; dry-run by default.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $local = Storage::disk('local');
        $public = Storage::disk('public');
        $candidates = 0;
        $migrated = 0;
        $missing = 0;
        $conflicts = 0;

        Document::query()->select(['id', 'path'])->orderBy('id')->chunk(200, function ($documents) use ($apply, $local, $public, &$candidates, &$migrated, &$missing, &$conflicts): void {
            foreach ($documents as $document) {
                $path = (string) $document->path;
                if (! str_starts_with($path, 'documents/') || str_contains($path, '..') || str_contains($path, "\0")) {
                    $conflicts++;

                    continue;
                }

                $hasPrivate = $local->exists($path);
                $hasPublic = $public->exists($path);
                if (! $hasPrivate && ! $hasPublic) {
                    $missing++;

                    continue;
                }

                if (! $hasPublic) {
                    continue;
                }

                $candidates++;
                if (! $apply) {
                    continue;
                }

                if (! $hasPrivate) {
                    $stream = $public->readStream($path);
                    if (! is_resource($stream)) {
                        throw new RuntimeException('Could not read a legacy document from the public disk.');
                    }

                    try {
                        if (! $local->put($path, $stream)) {
                            throw new RuntimeException('Could not write a legacy document to private storage.');
                        }
                    } finally {
                        fclose($stream);
                    }
                }

                if (! hash_equals($this->checksum($local->path($path)), $this->checksum($public->path($path)))) {
                    $conflicts++;

                    continue;
                }

                if (! $public->delete($path)) {
                    throw new RuntimeException('Could not remove the verified public document copy.');
                }

                $migrated++;
            }
        });

        $this->info($apply ? 'Migração verificada concluída.' : 'Simulação concluída; nenhum arquivo foi alterado.');
        $this->line("Cópias públicas candidatas: {$candidates}; migradas e verificadas: {$migrated}; ausentes: {$missing}; caminhos/conteúdos conflitantes: {$conflicts}.");

        return $conflicts > 0 || $missing > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function checksum(string $path): string
    {
        $checksum = hash_file('sha256', $path);
        if (! is_string($checksum)) {
            throw new RuntimeException('Could not verify a document checksum.');
        }

        return $checksum;
    }
}
