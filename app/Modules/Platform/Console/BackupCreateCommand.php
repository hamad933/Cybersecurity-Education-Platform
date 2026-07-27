<?php

namespace App\Modules\Platform\Console;

use App\Modules\Platform\Backup\BackupService;
use Illuminate\Console\Command;

final class BackupCreateCommand extends Command
{
    protected $signature = 'platform:backup-create {actor}';

    protected $description = 'Create a verified local portable backup package.';

    public function handle(BackupService $backups): int
    {
        $result = $backups->create((string) $this->argument('actor'));
        $this->line(json_encode([
            'status' => 'PASS',
            'backup_manifest_id' => (string) $result['manifest']->id,
            'package_id' => $result['package_id'],
            'blob_key' => $result['blob_key'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
