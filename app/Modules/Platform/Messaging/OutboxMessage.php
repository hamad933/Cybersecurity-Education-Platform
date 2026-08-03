<?php

namespace App\Modules\Platform\Messaging;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class OutboxMessage extends Model
{
    use UsesUuidV7;

    public $timestamps = false;

    protected $fillable = ['schema_version', 'type', 'producer_module', 'correlation_id', 'causation_id', 'payload', 'idempotency_key', 'occurred_at', 'dispatch_state', 'attempts', 'leased_until', 'next_attempt_at', 'dispatched_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'occurred_at' => 'immutable_datetime', 'leased_until' => 'immutable_datetime', 'next_attempt_at' => 'immutable_datetime', 'dispatched_at' => 'immutable_datetime'];
    }
}
