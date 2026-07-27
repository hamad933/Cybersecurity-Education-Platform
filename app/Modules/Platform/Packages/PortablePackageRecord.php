<?php

namespace App\Modules\Platform\Packages;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use App\Modules\Platform\Support\ImmutableWhenFinal;
use Illuminate\Database\Eloquent\Model;

class PortablePackageRecord extends Model
{
    use ImmutableWhenFinal, UsesUuidV7;

    protected $table = 'portable_packages';

    public $timestamps = false;

    protected $fillable = [
        'package_type', 'schema_version', 'owner_module', 'actor_id', 'scope',
        'manifest', 'package_digest', 'blob_object_id', 'status', 'created_at',
    ];

    protected function casts(): array
    {
        return ['scope' => 'array', 'manifest' => 'array', 'created_at' => 'immutable_datetime'];
    }

    protected function wasFinalBeforeUpdate(): bool
    {
        return in_array($this->getOriginal('status'), ['verified', 'exported', 'rejected'], true);
    }
}
