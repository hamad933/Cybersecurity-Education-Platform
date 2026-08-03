<?php

namespace App\Modules\Evidence\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use App\Modules\Platform\Support\ImmutableWhenFinal;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class EvidenceRecord extends Model
{
    use ImmutableWhenFinal, UsesUuidV7;

    protected $fillable = ['origin', 'actor_id', 'capability_id', 'knowledge_unit_id', 'scenario_revision_id', 'rule_set_revision_id', 'enterprise_baseline_revision_id', 'run_id', 'case_id', 'input_digest', 'trace_digest', 'result', 'limitations', 'source_claim_ids', 'content_digest', 'locked', 'policy_revision_id', 'endpoint_contract_revision_id', 'request_case_id', 'finding_ids', 'remediation_revision_id', 'verification_run_id'];

    protected function casts(): array
    {
        return ['limitations' => 'array', 'source_claim_ids' => 'array', 'finding_ids' => 'array', 'locked' => 'boolean'];
    }

    protected function wasFinalBeforeUpdate(): bool
    {
        return (bool) $this->getOriginal('locked');
    }

    /** @return list<string> */
    public function sourceClaimIds(): array
    {
        $value = $this->getAttribute('source_claim_ids');
        if (! is_array($value) || ! array_is_list($value)) {
            throw new UnexpectedValueException('Evidence source claim IDs must be a JSON list.');
        }
        $claims = [];
        foreach ($value as $claim) {
            if (! is_string($claim)) {
                throw new UnexpectedValueException('Evidence source claim ID must be a string.');
            }
            $claims[] = $claim;
        }

        return $claims;
    }
}
