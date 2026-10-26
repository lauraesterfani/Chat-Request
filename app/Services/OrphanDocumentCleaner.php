<?php

namespace App\Services;

use App\Models\Document;
use App\Models\RequestDraft;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrphanDocumentCleaner
{
    /** @return Collection<int, Document> */
    public function candidates(CarbonInterface $olderThan, int $limit): Collection
    {
        $candidates = collect();

        foreach (Document::where('created_at', '<', $olderThan)->whereDoesntHave('requests')->lazyById(100) as $document) {
            if ($this->isEligible($document, $olderThan)) {
                $candidates->push($document);
                if ($candidates->count() >= $limit) {
                    break;
                }
            }
        }

        return $candidates;
    }

    public function remove(Document $candidate, CarbonInterface $olderThan): bool
    {
        return DB::transaction(function () use ($candidate, $olderThan): bool {
            $document = Document::whereKey($candidate->getKey())->lockForUpdate()->first();
            if (! $document || ! $this->isEligible($document, $olderThan)) {
                return false;
            }

            // A aplicação e os workers devem estar parados durante a exclusão. A
            // referência JSON do rascunho não possui FK capaz de bloquear novas gravações.
            if (! Storage::disk('local')->delete($document->path)) {
                return false;
            }

            $document->delete();

            return true;
        });
    }

    private function isEligible(Document $document, CarbonInterface $olderThan): bool
    {
        $path = $document->path;

        return $document->created_at?->lessThan($olderThan)
            && preg_match('~\Adocuments/[A-Za-z0-9_-]+\.(?:pdf|jpe?g|png)\z~i', $path) === 1
            && ! $document->requests()->exists()
            // Inclui rascunhos descartados e enviados: sem política de retenção,
            // nenhum vínculo histórico é tratado como descartável.
            && ! RequestDraft::whereJsonContains('document_ids', $document->id)->exists()
            && ! Document::where('path', $path)->whereKeyNot($document->id)->exists()
            && Storage::disk('local')->exists($path)
            && ! Storage::disk('public')->exists($path);
    }
}
