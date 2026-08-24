<?php

namespace App\Modules\ManualAiBridge\Application;

use App\Modules\ManualAiBridge\Models\ImportedAiResult;
use App\Modules\ManualAiBridge\Models\PromptPackageRevision;
use Illuminate\Database\Eloquent\Builder;

final class ManualAiStateReader
{
    /** @return Builder<PromptPackageRevision> */
    public function promptRevisions(): Builder
    {
        return PromptPackageRevision::query()->from('prompt_package_revisions as revision');
    }

    /** @return Builder<ImportedAiResult> */
    public function importedResults(): Builder
    {
        return ImportedAiResult::query()->from('imported_ai_results as result');
    }
}
