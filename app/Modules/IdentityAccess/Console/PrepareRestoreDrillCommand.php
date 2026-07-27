<?php

namespace App\Modules\IdentityAccess\Console;

use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Platform\Backup\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use LogicException;

final class PrepareRestoreDrillCommand extends Command
{
    protected $signature = 'platform:restore-drill-prepare';

    protected $description = 'Prepare a deterministic backup input in the isolated Task-010 test database.';

    public function handle(BackupService $backups): int
    {
        $database = (string) config('database.connections.pgsql.database');
        if (! app()->environment('testing') || ! str_ends_with($database, '_test')) {
            throw new LogicException('Restore drill preparation is restricted to a testing database ending in _test.');
        }
        $owner = OwnerAccount::query()->where('is_active', true)->first();
        if ($owner === null) {
            $owner = OwnerAccount::query()->create([
                'display_name' => 'Task-010 Restore Drill Owner',
                'email' => 'task010-restore-drill@example.test',
                'password' => Str::password(32),
                'is_active' => true,
            ]);
        }
        $result = $backups->create((string) $owner->id);
        $absolute = storage_path('app/private/'.$result['blob_key']);
        $this->line(json_encode([
            'status' => 'PASS',
            'actor_id' => (string) $owner->id,
            'archive_path' => $absolute,
            'backup_manifest_id' => (string) $result['manifest']->id,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
