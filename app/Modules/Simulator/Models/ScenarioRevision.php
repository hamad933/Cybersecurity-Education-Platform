<?php

namespace App\Modules\Simulator\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use App\Modules\Platform\Support\ImmutableWhenFinal;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class ScenarioRevision extends Model
{
    use ImmutableWhenFinal, UsesUuidV7;

    protected $fillable = ['scenario_id', 'revision', 'state', 'rule_set_revision_id', 'enterprise_baseline_revision_id', 'cases', 'digest', 'published_at'];

    protected function casts(): array
    {
        return ['cases' => 'array', 'published_at' => 'immutable_datetime'];
    }

    protected function wasFinalBeforeUpdate(): bool
    {
        return $this->getOriginal('state') === 'published';
    }

    /** @return list<array<string, mixed>> */
    public function caseDefinitions(): array
    {
        $value = $this->getAttribute('cases');
        if (! is_array($value) || ! array_is_list($value)) {
            throw new UnexpectedValueException('Scenario cases must be a JSON list.');
        }
        $cases = [];
        foreach ($value as $case) {
            if (! is_array($case)) {
                throw new UnexpectedValueException('Each scenario case must be an object.');
            }
            $cases[] = $case;
        }

        return $cases;
    }
}
