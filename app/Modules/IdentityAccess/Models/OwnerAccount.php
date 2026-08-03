<?php

namespace App\Modules\IdentityAccess\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class OwnerAccount extends Authenticatable
{
    use Notifiable, UsesUuidV7;

    protected $fillable = ['display_name', 'email', 'password', 'is_active'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean', 'last_login_at' => 'immutable_datetime'];
    }
}
