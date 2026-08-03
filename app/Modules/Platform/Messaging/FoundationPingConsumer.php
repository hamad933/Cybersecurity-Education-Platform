<?php

namespace App\Modules\Platform\Messaging;

use InvalidArgumentException;

class FoundationPingConsumer
{
    public function consume(OutboxMessage $message): bool
    {
        if ($message->type !== 'foundation.ping.v1') {
            throw new InvalidArgumentException('Unsupported foundation message type.');
        }
        if ($message->dispatch_state === 'dispatched') {
            return false;
        }
        $message->forceFill(['dispatch_state' => 'dispatched', 'attempts' => $message->attempts + 1, 'dispatched_at' => now()])->save();

        return true;
    }
}
