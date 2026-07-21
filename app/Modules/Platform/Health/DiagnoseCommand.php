<?php

namespace App\Modules\Platform\Health;

use App\Modules\Platform\Audit\AuditWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DiagnoseCommand extends Command
{
    protected $signature = 'app:diagnose {--no-audit : Skip audit when migrations are unavailable}';

    protected $description = 'Run non-sensitive foundation diagnostics';

    public function handle(FoundationHealth $health, AuditWriter $audit): int
    {
        $checks = $health->checks();
        foreach ($checks as $name => $status) {
            $this->line(sprintf('%-16s %s', $name, strtoupper($status)));
        }
        $success = ! in_array('failed', $checks, true);
        if (! $this->option('no-audit') && Schema::hasTable('audit_records')) {
            $audit->append(['actor_identifier' => null, 'action' => 'foundation.diagnose', 'target_type' => 'platform', 'target_identifier' => null, 'correlation_id' => (string) Str::uuid7(), 'outcome' => $success ? 'success' : 'failure', 'safe_metadata' => ['failed_checks' => array_keys(array_filter($checks, fn ($value) => $value === 'failed'))]]);
        }

        return $success ? self::SUCCESS : self::FAILURE;
    }
}
