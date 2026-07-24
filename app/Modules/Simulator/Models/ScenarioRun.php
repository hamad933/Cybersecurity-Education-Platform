<?php

namespace App\Modules\Simulator\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class ScenarioRun extends Model
{
    use UsesUuidV7;

    protected $fillable = ['scenario_revision_id', 'rule_set_revision_id', 'enterprise_baseline_revision_id', 'actor_id', 'case_id', 'seed', 'status', 'ordered_actions', 'normalized_input', 'input_digest', 'request_digest', 'baseline_digest_before', 'baseline_digest_after', 'outcome', 'trace_digest', 'idempotency_key', 'completed_at', 'policy_revision_id', 'endpoint_contract_revision_id', 'request_id', 'correlation_id', 'remediation_revision_id', 'verification_of_run_id'];

    protected function casts(): array
    {
        return ['ordered_actions' => 'array', 'normalized_input' => 'array', 'completed_at' => 'immutable_datetime'];
    }

    /** @return list<string> */
    public function orderedActionList(): array
    {
        $actions = $this->getAttribute('ordered_actions');
        if (! is_array($actions) || ! array_is_list($actions)) {
            throw new LogicException('Original ordered-action input is unavailable.');
        }
        foreach ($actions as $action) {
            if (! is_string($action)) {
                throw new LogicException('Original ordered-action input is invalid.');
            }
        }

        return $actions;
    }

    /** @return array<string,mixed> */
    public function normalizedInputPayload(): array
    {
        $input = $this->getAttribute('normalized_input');
        if (! is_array($input)) {
            throw new LogicException('Original normalized input is unavailable.');
        }

        return $input;
    }
}
