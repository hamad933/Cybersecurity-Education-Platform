<?php

namespace App\Modules\Platform\SystemOperations\Contracts;

interface ManualAiStateProvider
{
    /** @return list<array<string, mixed>> */
    public function promptRevisionsForActor(string $actorId): array;

    /** @return list<array<string, mixed>> */
    public function importedResultsForActor(string $actorId): array;
}
