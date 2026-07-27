<?php

namespace App\Modules\ManualAiBridge\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class PromptPackage extends Model
{
    use UsesUuidV7;

    protected $fillable = ['actor_id', 'purpose', 'status', 'current_revision'];

    protected function casts(): array
    {
        return ['current_revision' => 'integer'];
    }
}
