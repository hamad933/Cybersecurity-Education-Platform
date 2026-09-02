<?php

namespace App\Modules\ManualAiBridge\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AiProposalDecision extends Model
{
    use UsesUuidV7;

    public $timestamps = false;

    protected $fillable = ['imported_ai_result_id', 'proposal_id', 'sequence', 'actor_id', 'decision', 'rationale', 'lesson_revision_id', 'decided_at'];

    protected function casts(): array
    {
        return ['decided_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('AI proposal decisions are append-only.'));
        static::deleting(fn () => throw new LogicException('AI proposal decisions are append-only.'));
    }
}
