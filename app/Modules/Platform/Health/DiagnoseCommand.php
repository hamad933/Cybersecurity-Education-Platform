<?php

namespace App\Modules\Platform\Health;

use App\Modules\Platform\Audit\AuditWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class DiagnoseCommand extends Command
{
    protected $signature = 'app:diagnose {--no-audit : Skip audit when migrations are unavailable}';

    protected $description = 'Run non-sensitive foundation diagnostics';

    public function handle(FoundationHealth $health, AuditWriter $audit): int
    {
        $checks = $health->diagnosticChecks();
        foreach ($checks as $name => $status) {
            $this->line(sprintf('%-16s %s', $name, strtoupper($status)));
        }
        $success = ! in_array('failed', $checks, true);
        if (! $this->option('no-audit') && $checks['database'] === 'ok') {
            try {
                if (Schema::hasTable('audit_records')) {
                    $audit->append(['actor_identifier' => null, 'action' => 'foundation.diagnose', 'target_type' => 'platform', 'target_identifier' => null, 'correlation_id' => (string) Str::uuid7(), 'outcome' => $success ? 'success' : 'failure', 'safe_metadata' => ['failed_checks' => array_keys(array_filter($checks, fn ($value) => $value === 'failed'))]]);
                }
            } catch (Throwable) {
                $this->line(sprintf('%-16s %s', 'audit', 'FAILED'));

                return self::FAILURE;
            }
        } elseif (! $this->option('no-audit')) {
            $this->line(sprintf('%-16s %s', 'audit', 'SKIPPED_DATABASE_UNAVAILABLE'));
        }

        return $success ? self::SUCCESS : self::FAILURE;
    }
}
