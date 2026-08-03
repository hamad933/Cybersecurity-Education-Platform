<?php

namespace App\Modules\Platform\Audit;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditRecord extends Model
{
    use UsesUuidV7;

    public const UPDATED_AT = null;

    public const CREATED_AT = 'occurred_at';

    protected $fillable = [
        'actor_identifier', 'action', 'target_type', 'target_identifier',
        'correlation_id', 'outcome', 'safe_metadata', 'occurred_at',
        'sequence_no', 'previous_hash', 'record_hash',
    ];

    protected function casts(): array
    {
        return [
            'safe_metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
            'sequence_no' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit records are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit records are append-only.'));
    }
}
