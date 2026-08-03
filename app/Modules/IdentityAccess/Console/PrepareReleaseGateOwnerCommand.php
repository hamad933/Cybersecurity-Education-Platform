<?php

namespace App\Modules\IdentityAccess\Console;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use LogicException;

final class PrepareReleaseGateOwnerCommand extends Command
{
    protected $signature = 'platform:release-gate-owner';

    protected $description = 'Create or report the deterministic local owner used only by the Task-010 release browser gate.';

    public function handle(CreateOwner $owners): int
    {
        $database = (string) config('database.connections.pgsql.database');
        if ((string) config('platform.profile') !== 'release' || $database !== 'cyber_platform') {
            throw new LogicException('Release gate owner preparation is restricted to the local release profile and cyber_platform database.');
        }

        $password = (string) (getenv('TASK010_BROWSER_PASSWORD') ?: '');
        if (mb_strlen($password) < 20) {
            throw new LogicException('TASK010_BROWSER_PASSWORD must contain at least 20 characters.');
        }

        $email = 'task010-browser@example.test';
        $owner = OwnerAccount::query()->where('email', $email)->where('is_active', true)->first();
        if ($owner === null) {
            if (OwnerAccount::query()->where('is_active', true)->exists()) {
                throw new LogicException('A different active owner already exists in the release database.');
            }

            $owner = $owners->execute(
                'Task-010 Browser Gate Owner',
                $email,
                $password,
                (string) Str::uuid7(),
            );
        }

        $this->line(json_encode([
            'status' => 'PASS',
            'owner_id' => (string) $owner->id,
            'email' => $email,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
