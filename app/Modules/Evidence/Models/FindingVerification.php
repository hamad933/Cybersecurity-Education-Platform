<?php

namespace App\Modules\Evidence\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class FindingVerification extends Model
{
    use UsesUuidV7;

    protected $fillable = ['security_finding_id', 'actor_id', 'vulnerable_run_id', 'vulnerable_trace_digest', 'remediation_policy_revision_id', 'verification_run_id', 'verification_trace_digest', 'status', 'verification_digest', 'verified_at'];

    protected function casts(): array
    {
        return ['verified_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Finding verification links are immutable.'));
    }
}
