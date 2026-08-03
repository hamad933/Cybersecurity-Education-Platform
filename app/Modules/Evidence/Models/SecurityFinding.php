<?php

namespace App\Modules\Evidence\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class SecurityFinding extends Model
{
    use UsesUuidV7;

    protected $fillable = ['id', 'finding_key', 'occurrence_key', 'category', 'scenario_run_id', 'actor_id', 'target_resource_id', 'policy_revision_id', 'decisive_missing_check', 'trace_digest', 'source_claim_ids', 'safe_details', 'status', 'verified_at'];

    protected function casts(): array
    {
        return ['source_claim_ids' => 'array', 'safe_details' => 'array', 'verified_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $finding): void {
            if ($finding->getOriginal('status') === 'verified_fixed') {
                throw new LogicException('Verified findings are immutable.');
            }
            if (array_diff(array_keys($finding->getDirty()), ['status', 'verified_at', 'updated_at']) !== []) {
                throw new LogicException('Finding facts are immutable; only verified closure is allowed.');
            }
        });
    }
}
