<?php

namespace App\Modules\Platform\Messaging;

use InvalidArgumentException;

class OutboxPublisher
{
    public function foundationPing(string $idempotencyKey, string $correlationId, string $message): OutboxMessage
    {
        return $this->publish('foundation.ping.v1', 'MOD-PLT', $idempotencyKey, $correlationId, ['message' => mb_substr($message, 0, 200)]);
    }

    /** @param array<string, mixed> $payload */
    public function publish(string $type, string $producerModule, string $idempotencyKey, string $correlationId, array $payload): OutboxMessage
    {
        if (preg_match('/^[a-z0-9.-]+\.v\d+$/', $type) !== 1 || preg_match('/^MOD-[A-Z]{3}$/', $producerModule) !== 1) {
            throw new InvalidArgumentException('Outbox type or producer is invalid.');
        }
        if (strlen(json_encode($payload, JSON_THROW_ON_ERROR)) > config('platform.outbox_payload_max_bytes', 16384)) {
            throw new InvalidArgumentException('Outbox payload exceeds limit.');
        }

        return OutboxMessage::query()->firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            ['schema_version' => 1, 'type' => $type, 'producer_module' => $producerModule, 'correlation_id' => $correlationId, 'payload' => $payload, 'occurred_at' => now(), 'dispatch_state' => 'pending'],
        );
    }
}
