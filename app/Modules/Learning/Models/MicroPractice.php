<?php

namespace App\Modules\Learning\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class MicroPractice extends Model
{
    use UsesUuidV7;

    protected $fillable = ['practice_id', 'revision', 'capability_id', 'knowledge_unit_id', 'definition', 'digest'];

    protected function casts(): array
    {
        return ['definition' => 'array'];
    }

    /** @return array<string, mixed> */
    public function definitionPayload(): array
    {
        $value = $this->getAttribute('definition');
        if (! is_array($value) || array_is_list($value)) {
            throw new UnexpectedValueException('Micro-practice definition must be a JSON object.');
        }

        return $value;
    }
}
