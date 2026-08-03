<?php

namespace App\Modules\SourceGovernance\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceRecord extends Model
{
    use UsesUuidV7;

    protected $fillable = ['authority_class', 'title', 'exact_url', 'relative_path', 'sha256', 'review_status', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    /** @return HasMany<SourceClaim, $this> */
    public function claims(): HasMany
    {
        return $this->hasMany(SourceClaim::class);
    }
}
