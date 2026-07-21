<?php

namespace App\Modules\Platform\Audit;

use InvalidArgumentException;

class AuditWriter
{
    private const SENSITIVE_KEYS = ['password', 'password_confirmation', 'token', 'secret', 'session', 'content', 'file'];

    /**
     * @param array{actor_identifier?: ?string, action: string, target_type: string, target_identifier?: ?string, correlation_id: string, outcome: string, safe_metadata?: array<string, mixed>, occurred_at?: mixed} $record
     */
    public function append(array $record): AuditRecord
    {
        $metadata = $record['safe_metadata'] ?? [];
        $encoded = json_encode($metadata, JSON_THROW_ON_ERROR);
        if (strlen($encoded) > config('platform.audit_metadata_max_bytes', 4096)) {
            throw new InvalidArgumentException('Audit metadata exceeds the bounded limit.');
        }
        foreach (array_keys($metadata) as $key) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                throw new InvalidArgumentException('Sensitive audit metadata key rejected.');
            }
        }

        return AuditRecord::query()->create([
            ...$record,
            'safe_metadata' => $metadata,
            'occurred_at' => $record['occurred_at'] ?? now(),
        ]);
    }
}
