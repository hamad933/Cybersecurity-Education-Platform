<?php

namespace App\Modules\Platform\Search;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class IndexSearchDocumentJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public int $uniqueFor = 300;

    /** @param array<string,mixed> $document */
    public function __construct(public readonly array $document, public readonly string $idempotencyKey) {}

    public function handle(SearchService $search): void
    {
        $search->index($this->document);
    }

    public function uniqueId(): string
    {
        return $this->idempotencyKey;
    }
}
