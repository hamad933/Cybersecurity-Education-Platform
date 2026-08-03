<?php

namespace App\Modules\Platform\Console;

use App\Modules\Platform\Release\ReleaseReadiness;
use Illuminate\Console\Command;

final class ReleaseCheckCommand extends Command
{
    protected $signature = 'platform:release-check {--json}';

    protected $description = 'Evaluate bounded V1 release-readiness checks.';

    public function handle(ReleaseReadiness $readiness): int
    {
        $result = $readiness->evaluate();
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            foreach ($result['checks'] as $name => $check) {
                $this->line("[{$check['status']}] {$name}: {$check['detail']}");
            }
        }

        return $result['ready'] ? self::SUCCESS : self::FAILURE;
    }
}
