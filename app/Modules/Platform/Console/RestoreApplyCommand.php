<?php

namespace App\Modules\Platform\Console;

use App\Modules\Platform\Backup\BackupService;
use Illuminate\Console\Command;
use RuntimeException;

final class RestoreApplyCommand extends Command
{
    protected $signature = 'platform:restore-apply {archive} {actor}';

    protected $description = 'Apply a verified backup only inside an isolated _restore_drill database.';

    public function handle(BackupService $backups): int
    {
        $path = (string) $this->argument('archive');
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Restore archive cannot be opened.');
        }
        try {
            $package = $backups->inspect($stream);
        } finally {
            fclose($stream);
        }
        $run = $backups->applyToIsolatedDatabase($package, (string) $this->argument('actor'));
        $this->line(json_encode(['status' => $run->status, 'restore_run_id' => (string) $run->id, 'verification' => $run->verification], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return $run->status === 'verified' ? self::SUCCESS : self::FAILURE;
    }
}
