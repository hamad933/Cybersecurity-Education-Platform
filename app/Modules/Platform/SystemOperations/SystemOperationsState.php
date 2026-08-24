<?php

namespace App\Modules\Platform\SystemOperations;

use App\Modules\Platform\Audit\AuditChainVerifier;
use App\Modules\Platform\Health\FoundationHealth;
use App\Modules\Platform\Release\ReleaseReadiness;
use App\Modules\Platform\SystemOperations\Contracts\ManualAiStateProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class SystemOperationsState
{
    public function __construct(
        private readonly FoundationHealth $health,
        private readonly ReleaseReadiness $releaseReadiness,
        private readonly AuditChainVerifier $auditChain,
        private readonly ManualAiStateProvider $aiState,
    ) {
        // Constructor uses property promotion only.
    }

    /** @return array<string, mixed> */
    public function forSurface(string $surface, string $actorId): array
    {
        return match ($surface) {
            'health' => $this->healthState($actorId),
            'processing' => $this->processingState(),
            'validation' => $this->validationState($actorId),
            'ai-bridge' => $this->aiBridgeState($actorId),
            'backups' => $this->backupState($actorId),
            'audit' => $this->auditState(),
            'releases' => $this->releaseState($actorId),
            'configuration' => $this->configurationState(),
            default => throw new \InvalidArgumentException("Unknown System & Operations surface: {$surface}"),
        };
    }

    /** @return array<string, mixed> */
    private function healthState(string $actorId): array
    {
        $checks = $this->health->summaryChecks();
        $failedChecks = collect($checks)->filter(fn (string $status): bool => $status !== 'ok')->keys()->values()->all();

        return [
            'foundation' => [
                'checks' => $checks,
                'healthy' => $failedChecks === [],
                'failed_checks' => $failedChecks,
            ],
            'processing' => ['counts' => $this->statusCounts('processing_runs', 'status')],
            'outbox' => ['counts' => $this->statusCounts('outbox_messages', 'dispatch_state')],
            'packages' => ['counts' => $this->actorStatusCounts('portable_packages', 'status', $actorId)],
            'release_gate' => $this->safe(
                fn (): array => $this->releaseReadiness->evaluate(),
                ['ready' => false, 'checks' => []],
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function processingState(): array
    {
        return [
            'processing' => [
                'counts' => $this->statusCounts('processing_runs', 'status'),
                'runs' => $this->rows('processing_runs', [
                    'id', 'type', 'input_digest', 'status', 'attempt_count', 'max_attempts', 'worker_identifier',
                    'started_at', 'completed_at', 'cancelled_at', 'error_category',
                    'safe_error_message', 'created_at', 'updated_at',
                ], 'created_at', 30),
            ],
            'outbox' => [
                'counts' => $this->statusCounts('outbox_messages', 'dispatch_state'),
                'messages' => $this->rows('outbox_messages', [
                    'id', 'type', 'producer_module', 'correlation_id', 'dispatch_state', 'attempts',
                    'occurred_at', 'next_attempt_at', 'dispatched_at',
                ], 'occurred_at', 30),
            ],
            'policy' => [
                'cancellation' => 'PENDING_OR_RUNNING_ONLY',
                'retry_route_available' => false,
                'knowledge_decisions' => false,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function validationState(string $actorId): array
    {
        return [
            'packages' => [
                'counts' => $this->actorStatusCounts('portable_packages', 'status', $actorId),
                'records' => $this->actorRows('portable_packages', $actorId, [
                    'id', 'package_type', 'schema_version', 'owner_module', 'scope', 'manifest',
                    'package_digest', 'status', 'created_at',
                ], 'created_at', 30, ['scope', 'manifest']),
            ],
            'source_imports' => [
                'counts' => $this->actorStatusCounts('source_imports', 'status', $actorId),
                'records' => $this->actorRows('source_imports', $actorId, [
                    'id', 'original_name', 'detected_media_type', 'extension', 'size_bytes', 'sha256',
                    'status', 'rejection_code', 'created_at',
                ], 'created_at', 30),
            ],
            'scope' => [
                'technical_validation_only' => true,
                'knowledge_quality_decisions' => false,
                'canonical_knowledge_decisions' => false,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function aiBridgeState(string $actorId): array
    {
        return [
            'policy' => [
                'execution' => 'MANUAL_ONLY',
                'automatic_provider_enabled' => (bool) config('platform.ai_network_provider_enabled', false),
                'automatic_publish' => false,
                'polling' => false,
                'embeddings' => false,
            ],
            'prompts' => $this->actorRows('prompt_packages', $actorId, [
                'id', 'purpose', 'status', 'current_revision', 'created_at', 'updated_at',
            ], 'created_at', 20),
            'prompt_revisions' => $this->promptRevisionRows($actorId),
            'results' => $this->aiResultRows($actorId),
            'decisions' => $this->actorRows('ai_proposal_decisions', $actorId, [
                'id', 'imported_ai_result_id', 'decision', 'rationale', 'lesson_revision_id', 'decided_at',
            ], 'decided_at', 20),
        ];
    }

    /** @return array<string, mixed> */
    private function backupState(string $actorId): array
    {
        return [
            'backups' => $this->actorRows('backup_manifests', $actorId, [
                'id', 'portable_package_id', 'status', 'database_driver', 'content_digest', 'created_at',
            ], 'created_at', 20),
            'restores' => $this->actorRows('restore_runs', $actorId, [
                'id', 'backup_manifest_id', 'target_database', 'status', 'verification', 'started_at', 'completed_at',
            ], 'started_at', 20, ['verification']),
            'safety' => [
                'web_restore_mode' => 'STAGE_AND_VERIFY_ONLY',
                'activation_route_available' => false,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function auditState(): array
    {
        return [
            'chain' => $this->safe(
                fn (): array => $this->auditChain->verify(),
                ['valid' => false, 'count' => 0],
            ),
            'records' => $this->rows('audit_records', [
                'id', 'sequence_no', 'actor_identifier', 'action', 'target_type', 'target_identifier',
                'correlation_id', 'outcome', 'safe_metadata', 'occurred_at', 'previous_hash', 'record_hash',
            ], 'sequence_no', 50, ['safe_metadata']),
            'policy' => ['append_only' => true, 'destructive_http_actions' => false],
        ];
    }

    /** @return array<string, mixed> */
    private function releaseState(string $actorId): array
    {
        return [
            'readiness' => $this->safe(
                fn (): array => $this->releaseReadiness->evaluate(),
                ['ready' => false, 'checks' => []],
            ),
            'packages' => $this->actorRows('portable_packages', $actorId, [
                'id', 'package_type', 'schema_version', 'owner_module', 'scope', 'manifest',
                'package_digest', 'status', 'created_at',
            ], 'created_at', 30, ['scope', 'manifest']),
            'authorization' => [
                'deployment_authorized' => false,
                'deployment_workflow_available' => false,
                'scope' => 'PACKAGE_AND_RELEASE_VALIDATION_ONLY',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function configurationState(): array
    {
        return [
            'profile' => (string) config('platform.profile'),
            'auth_bypass' => (bool) config('platform.auth_bypass'),
            'force_https' => (bool) config('platform.force_https'),
            'blob_disk' => (string) config('platform.blob_disk'),
            'queue_connection' => (string) config('queue.default'),
            'release_loopback_only' => (bool) config('platform.release_loopback_only'),
            'ai_network_provider_enabled' => (bool) config('platform.ai_network_provider_enabled', false),
            'limits' => [
                'source_import_max_bytes' => (int) config('platform.source_import_max_bytes'),
                'manual_ai_result_max_bytes' => (int) config('platform.manual_ai_result_max_bytes'),
                'audit_metadata_max_bytes' => (int) config('platform.audit_metadata_max_bytes'),
                'outbox_payload_max_bytes' => (int) config('platform.outbox_payload_max_bytes'),
            ],
            'configuration_policy' => [
                'mode' => 'READ_ONLY_WHITELIST',
                'runtime_mutation_available' => false,
                'secrets_exposed' => false,
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function promptRevisionRows(string $actorId): array
    {
        if (! $this->tablesAvailable(['prompt_packages', 'portable_packages'])) {
            return [];
        }

        return $this->aiState->promptRevisionsForActor($actorId);
    }

    /** @return list<array<string, mixed>> */
    private function aiResultRows(string $actorId): array
    {
        if (! $this->tablesAvailable([
            'imported_ai_results', 'prompt_packages', 'portable_packages',
        ])) {
            return [];
        }

        return $this->aiState->importedResultsForActor($actorId);
    }

    /** @return array<string, int> */
    private function statusCounts(string $table, string $column): array
    {
        if ($this->tableAvailable($table) === false) {
            return [];
        }

        return $this->safe(function () use ($table, $column): array {
            return DB::table($table)
                ->select($column, DB::raw('count(*) as aggregate'))
                ->groupBy($column)
                ->pluck('aggregate', $column)
                ->map(fn ($count): int => (int) $count)
                ->all();
        }, []);
    }

    /** @return array<string, int> */
    private function actorStatusCounts(string $table, string $column, string $actorId): array
    {
        if ($this->tableAvailable($table) === false) {
            return [];
        }

        return $this->safe(function () use ($table, $column, $actorId): array {
            return DB::table($table)
                ->where('actor_id', $actorId)
                ->select($column, DB::raw('count(*) as aggregate'))
                ->groupBy($column)
                ->pluck('aggregate', $column)
                ->map(fn ($count): int => (int) $count)
                ->all();
        }, []);
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $jsonColumns
     * @return list<array<string, mixed>>
     */
    private function rows(
        string $table,
        array $columns,
        string $orderColumn,
        int $limit,
        array $jsonColumns = [],
    ): array {
        if ($this->tableAvailable($table) === false) {
            return [];
        }

        return $this->safe(fn (): array => DB::table($table)
            ->orderByDesc($orderColumn)
            ->limit($limit)
            ->get($columns)
            ->map(fn (object $row): array => $this->decodeJsonColumns((array) $row, $jsonColumns))
            ->all(), []);
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $jsonColumns
     * @return list<array<string, mixed>>
     */
    private function actorRows(
        string $table,
        string $actorId,
        array $columns,
        string $orderColumn,
        int $limit,
        array $jsonColumns = [],
    ): array {
        if ($this->tableAvailable($table) === false) {
            return [];
        }

        return $this->safe(fn (): array => DB::table($table)
            ->where('actor_id', $actorId)
            ->orderByDesc($orderColumn)
            ->limit($limit)
            ->get($columns)
            ->map(fn (object $row): array => $this->decodeJsonColumns((array) $row, $jsonColumns))
            ->all(), []);
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

            $row[$column] = $this->safe(
                fn (): mixed => json_decode($value, true, 64, JSON_THROW_ON_ERROR),
                $value,
            );
        }

        return $row;
    }

    /** @param  list<string>  $tables */
    private function tablesAvailable(array $tables): bool
    {
        foreach ($tables as $table) {
            if ($this->tableAvailable($table) === false) {
                return false;
            }
        }

        return true;
    }

    private function tableAvailable(string $table): bool
    {
        return $this->safe(fn (): bool => Schema::hasTable($table), false);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $operation
     * @param  T  $fallback
     * @return T
     */
    private function safe(callable $operation, mixed $fallback): mixed
    {
        try {
            return $operation();
        } catch (Throwable) {
            return $fallback;
        }
    }
}
