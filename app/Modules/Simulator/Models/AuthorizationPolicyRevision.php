<?php

namespace App\Modules\Simulator\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use App\Modules\Platform\Support\ImmutableWhenFinal;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class AuthorizationPolicyRevision extends Model
{
    use ImmutableWhenFinal, UsesUuidV7;

    protected $table = 'web_authorization_policy_revisions';

    protected $fillable = ['policy_id', 'revision', 'state', 'mode', 'rules', 'source_claim_ids', 'digest', 'remediates_revision_id', 'published_at'];

    protected function casts(): array
    {
        return ['rules' => 'array', 'source_claim_ids' => 'array', 'published_at' => 'immutable_datetime'];
    }

    protected function wasFinalBeforeUpdate(): bool
    {
        return $this->getOriginal('state') === 'published';
    }

    /** @return array<string,mixed> */
    public function rulePayload(): array
    {
        $rules = $this->getAttribute('rules');
        if (! is_array($rules)) {
            throw new UnexpectedValueException('Authorization policy rules must be an object.');
        }

        return $rules;
    }
}
