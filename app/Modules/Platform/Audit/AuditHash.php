<?php

namespace App\Modules\Platform\Audit;

use App\Modules\Platform\Support\CanonicalJson;

final class AuditHash
{
    /** @param array<string,mixed> $record */
    public static function calculate(array $record): string
    {
        return CanonicalJson::sha256([
            'sequence_no' => (int) $record['sequence_no'],
            'previous_hash' => $record['previous_hash'],
            'actor_identifier' => $record['actor_identifier'],
            'action' => $record['action'],
            'target_type' => $record['target_type'],
            'target_identifier' => $record['target_identifier'],
            'correlation_id' => $record['correlation_id'],
            'outcome' => $record['outcome'],
            'safe_metadata' => $record['safe_metadata'],
            'occurred_at' => $record['occurred_at'],
        ]);
    }
}
