<?php

namespace App\Modules\Platform\Messaging;

use InvalidArgumentException;

class OutboxPublisher
{
    public function foundationPing(string $idempotencyKey, string $correlationId, string $message): OutboxMessage
    {
        $payload = ['message' => mb_substr($message, 0, 200)];
        if (strlen(json_encode($payload, JSON_THROW_ON_ERROR)) > config('platform.outbox_payload_max_bytes', 16384)) {
            throw new InvalidArgumentException('Outbox payload exceeds limit.');
        }

        return OutboxMessage::query()->firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            ['schema_version' => 1, 'type' => 'foundation.ping.v1', 'producer_module' => 'MOD-PLT', 'correlation_id' => $correlationId, 'payload' => $payload, 'occurred_at' => now(), 'dispatch_state' => 'pending'],
        );
    }
}
