<?php

namespace App\Modules\Platform\Audit;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AuditWriter
{
    private const SENSITIVE_KEYS = ['password', 'password_confirmation', 'token', 'secret', 'session', 'content', 'file', 'app_key', 'db_password'];

    /** @param array{actor_identifier?:?string,action:string,target_type:string,target_identifier?:?string,correlation_id:string,outcome:string,safe_metadata?:array<string,mixed>,occurred_at?:mixed} $record */
    public function append(array $record): AuditRecord
    {
        $metadata = $record['safe_metadata'] ?? [];
        $this->validateMetadata($metadata);

        return DB::transaction(function () use ($record, $metadata): AuditRecord {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('LOCK TABLE audit_records IN SHARE ROW EXCLUSIVE MODE');
            }
            $last = AuditRecord::query()->orderByDesc('sequence_no')->first();
            $sequence = $last === null ? 1 : ((int) $last->sequence_no) + 1;
            $occurred = (isset($record['occurred_at'])
                ? CarbonImmutable::parse($record['occurred_at'])->utc()
                : CarbonImmutable::now('UTC'))
                ->startOfSecond();
            $payload = [
                'sequence_no' => $sequence,
                'previous_hash' => $last?->record_hash,
                'actor_identifier' => $record['actor_identifier'] ?? null,
                'action' => $record['action'],
                'target_type' => $record['target_type'],
                'target_identifier' => $record['target_identifier'] ?? null,
                'correlation_id' => $record['correlation_id'],
                'outcome' => $record['outcome'],
                'safe_metadata' => $metadata,
                'occurred_at' => $occurred->format('Y-m-d\\TH:i:s.u\\Z'),
            ];

            return AuditRecord::query()->create([
                ...$record,
                'safe_metadata' => $metadata,
                'occurred_at' => $occurred,
                'sequence_no' => $sequence,
                'previous_hash' => $last?->record_hash,
                'record_hash' => AuditHash::calculate($payload),
            ]);
        }, 3);
    }

    /** @param array<string,mixed> $metadata */
    private function validateMetadata(array $metadata): void
    {
        $encoded = json_encode($metadata, JSON_THROW_ON_ERROR);
        if (strlen($encoded) > (int) config('platform.audit_metadata_max_bytes', 4096)) {
            throw new InvalidArgumentException('Audit metadata exceeds the bounded limit.');
        }
        $walk = function (array $value) use (&$walk): void {
            foreach ($value as $key => $item) {
                if (in_array(mb_strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                    throw new InvalidArgumentException('Sensitive audit metadata key rejected.');
                }
                if (is_array($item)) {
                    $walk($item);
                }
            }
        };
        $walk($metadata);
    }
}
