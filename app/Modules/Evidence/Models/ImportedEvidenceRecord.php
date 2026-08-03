<?php

namespace App\Modules\Evidence\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use App\Modules\Platform\Support\ImmutableWhenFinal;
use Illuminate\Database\Eloquent\Model;

class ImportedEvidenceRecord extends Model
{
    use ImmutableWhenFinal, UsesUuidV7;

    protected $fillable = [
        'actor_id', 'portable_package_id', 'origin', 'capability_id', 'knowledge_unit_id',
        'status', 'claims', 'limitations', 'content_digest', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['claims' => 'array', 'limitations' => 'array', 'reviewed_at' => 'immutable_datetime'];
    }

    protected function wasFinalBeforeUpdate(): bool
    {
        return in_array($this->getOriginal('status'), ['accepted', 'rejected'], true);
    }
}
