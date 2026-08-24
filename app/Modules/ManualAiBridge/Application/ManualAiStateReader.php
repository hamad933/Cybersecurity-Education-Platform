<?php

namespace App\Modules\ManualAiBridge\Application;

use App\Modules\Platform\SystemOperations\Contracts\ManualAiStateProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ManualAiStateReader implements ManualAiStateProvider
{
    /** @return list<array<string, mixed>> */
    public function promptRevisionsForActor(string $actorId): array
    {
        if (! $this->tablesAvailable(['prompt_package_revisions', 'prompt_packages', 'portable_packages'])) {
            return [];
        }

        try {
            return DB::table('prompt_package_revisions as revision')
                ->join('prompt_packages as prompt', 'prompt.id', '=', 'revision.prompt_package_id')
                ->join('portable_packages as package', 'package.id', '=', 'revision.portable_package_id')
                ->where('prompt.actor_id', $actorId)
                ->orderByDesc('revision.exported_at')
                ->limit(20)
                ->get([
                    'revision.id',
                    'revision.prompt_package_id',
                    'revision.revision',
                    'revision.portable_package_id',
                    'revision.input_digest',
                    'revision.declared_scope',
                    'revision.exported_at',
                    'prompt.purpose as prompt_purpose',
                    'prompt.status as prompt_status',
                    'prompt.current_revision as prompt_current_revision',
                    'package.package_type as package_type',
                    'package.package_digest as package_digest',
                    'package.scope as package_scope',
                    'package.manifest as package_manifest',
                    'package.status as package_status',
                ])
                ->map(fn ($row): array => $this->decodeJsonColumns((array) $row, [
                    'declared_scope', 'package_scope', 'package_manifest',
                ]))
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return list<array<string, mixed>> */
    public function importedResultsForActor(string $actorId): array
    {
        if (! $this->tablesAvailable(['imported_ai_results', 'prompt_package_revisions', 'prompt_packages', 'portable_packages'])) {
            return [];
        }

        try {
            return DB::table('imported_ai_results as result')
                ->join('prompt_package_revisions as revision', 'revision.id', '=', 'result.prompt_package_revision_id')
                ->join('prompt_packages as prompt', 'prompt.id', '=', 'revision.prompt_package_id')
                ->join('portable_packages as returned_package', 'returned_package.id', '=', 'result.portable_package_id')
                ->where('result.actor_id', $actorId)
                ->where('prompt.actor_id', $actorId)
                ->orderByDesc('result.imported_at')
                ->limit(20)
                ->get([
                    'result.id',
                    'result.prompt_package_revision_id',
                    'result.portable_package_id',
                    'result.result_digest',
                    'result.structured_result',
                    'result.status',
                    'result.imported_at',
                    'revision.prompt_package_id',
                    'revision.revision as prompt_revision',
                    'revision.input_digest as prompt_input_digest',
                    'revision.declared_scope',
                    'revision.portable_package_id as prompt_portable_package_id',
                    'prompt.purpose as prompt_purpose',
                    'prompt.status as prompt_status',
                    'returned_package.package_type as returned_package_type',
                    'returned_package.package_digest as returned_package_digest',
                    'returned_package.scope as returned_package_scope',
                    'returned_package.manifest as returned_package_manifest',
                    'returned_package.status as returned_package_status',
                ])
                ->map(fn ($row): array => $this->decodeJsonColumns((array) $row, [
                    'structured_result', 'declared_scope', 'returned_package_scope', 'returned_package_manifest',
                ]))
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $columns
     * @return array<string, mixed>
     */
    private function decodeJsonColumns(array $row, array $columns): array
    {
        foreach ($columns as $column) {
            $value = $row[$column] ?? null;
            if (! is_string($value)) {
                continue;
            }

            try {
                $row[$column] = json_decode($value, true, 64, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                // Keep original value on error.
            }
        }

        return $row;
    }

    /** @param list<string> $tables */
    private function tablesAvailable(array $tables): bool
    {
        foreach ($tables as $table) {
            try {
                if (! Schema::hasTable($table)) {
                    return false;
                }
            } catch (\Throwable) {
                return false;
            }
        }

        return true;
    }
}
