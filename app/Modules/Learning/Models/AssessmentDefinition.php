<?php

namespace App\Modules\Learning\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use UnexpectedValueException;

class AssessmentDefinition extends Model
{
    use UsesUuidV7;

    protected $fillable = [
        'assessment_id',
        'revision',
        'capability_id',
        'knowledge_unit_id',
        'definition',
    ];

    protected function casts(): array
    {
        return [
            'definition' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Assessment definitions are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new LogicException('Assessment definitions are immutable and cannot be deleted.');
        });
    }

    /** @return array<string, mixed> */
    public function definitionPayload(): array
    {
        $value = $this->getAttribute('definition');
        if (! is_array($value) || array_is_list($value)) {
            throw new UnexpectedValueException('Assessment definition must be a JSON object.');
        }

        return $value;
    }
}
