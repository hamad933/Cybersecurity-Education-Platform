<?php

namespace App\Modules\Simulator\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use App\Modules\Platform\Support\ImmutableWhenFinal;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class EndpointContractRevision extends Model
{
    use ImmutableWhenFinal, UsesUuidV7;

    protected $table = 'web_endpoint_contract_revisions';

    protected $fillable = ['contract_id', 'revision', 'state', 'method', 'route_template', 'requested_action', 'allowed_request_fields', 'response_shape_id', 'allowed_response_fields', 'authority_baseline_id', 'digest', 'published_at'];

    protected function casts(): array
    {
        return ['allowed_request_fields' => 'array', 'allowed_response_fields' => 'array', 'published_at' => 'immutable_datetime'];
    }

    protected function wasFinalBeforeUpdate(): bool
    {
        return $this->getOriginal('state') === 'published';
    }

    /** @return list<string> */
    public function allowedResponseFields(): array
    {
        $fields = $this->getAttribute('allowed_response_fields');
        if (! is_array($fields) || ! array_is_list($fields)) {
            throw new UnexpectedValueException('Endpoint response fields must be a list.');
        }
        foreach ($fields as $field) {
            if (! is_string($field)) {
                throw new UnexpectedValueException('Endpoint response field must be a string.');
            }
        }

        return $fields;
    }
}
