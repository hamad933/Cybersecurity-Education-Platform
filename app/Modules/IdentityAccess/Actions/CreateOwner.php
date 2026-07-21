<?php

namespace App\Modules\IdentityAccess\Actions;

use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Platform\Audit\AuditWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOwner
{
    public function __construct(private readonly AuditWriter $audit) {}

    public function execute(string $displayName, string $email, string $password, string $correlationId): OwnerAccount
    {
        return DB::transaction(function () use ($displayName, $email, $password, $correlationId): OwnerAccount {
            DB::table('owner_accounts')->lockForUpdate()->get();
            if (OwnerAccount::query()->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['owner' => 'An active owner already exists.']);
            }
            $owner = OwnerAccount::query()->create(['display_name' => $displayName, 'email' => mb_strtolower(trim($email)), 'password' => $password, 'is_active' => true]);
            $this->audit->append(['actor_identifier' => $owner->id, 'action' => 'owner.created', 'target_type' => 'owner_account', 'target_identifier' => $owner->id, 'correlation_id' => $correlationId, 'outcome' => 'success', 'safe_metadata' => []]);

            return $owner;
        });
    }
}
