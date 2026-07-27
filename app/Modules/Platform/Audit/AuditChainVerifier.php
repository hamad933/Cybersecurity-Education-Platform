<?php

namespace App\Modules\Platform\Audit;

final class AuditChainVerifier
{
    /** @return array{valid:bool,count:int,first_invalid_sequence:?int} */
    public function verify(): array
    {
        $previous = null;
        $count = 0;
        foreach (AuditRecord::query()->orderBy('sequence_no')->cursor() as $record) {
            $count++;
            $payload = [
                'sequence_no' => (int) $record->sequence_no,
                'previous_hash' => $record->previous_hash,
                'actor_identifier' => $record->actor_identifier,
                'action' => $record->action,
                'target_type' => $record->target_type,
                'target_identifier' => $record->target_identifier,
                'correlation_id' => (string) $record->correlation_id,
                'outcome' => $record->outcome,
                'safe_metadata' => $record->safe_metadata ?? [],
                'occurred_at' => $record->occurred_at?->utc()->format('Y-m-d\\TH:i:s.u\\Z'),
            ];
            $expected = AuditHash::calculate($payload);
            if ($record->previous_hash !== $previous || ! is_string($record->record_hash) || ! hash_equals($expected, $record->record_hash)) {
                return ['valid' => false, 'count' => $count, 'first_invalid_sequence' => (int) $record->sequence_no];
            }
            $previous = $record->record_hash;
        }

        return ['valid' => true, 'count' => $count, 'first_invalid_sequence' => null];
    }
}
