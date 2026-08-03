<?php

namespace App\Modules\Simulator\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class DecisionTrace extends Model
{
    use UsesUuidV7;

    protected $fillable = ['scenario_run_id', 'trace', 'output_digest'];

    protected function casts(): array
    {
        return ['trace' => 'array'];
    }

    /** @return array<string,mixed> */
    public function tracePayload(): array
    {
        $payload = $this->getAttribute('trace');
        if (! is_array($payload)) {
            throw new LogicException('Persisted decision trace is unavailable.');
        }

        return $payload;
    }
}
