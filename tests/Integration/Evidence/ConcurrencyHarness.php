<?php

namespace Tests\Integration\Evidence;

use Symfony\Component\Process\Process;

final class ConcurrencyHarness
{
    /**
     * Executes logic in a new process, providing a READY and GO synchronization barrier via the filesystem.
     * The process writes to $readyFile when it connects and obtains its PID, then waits until $goFile exists before executing.
     */
    public static function executeInNewProcess(string $scriptContent, string $readyFile, string $goFile): Process
    {
        $encoded = base64_encode(json_encode($scriptContent, JSON_THROW_ON_ERROR));
        $baseDir = realpath(__DIR__.'/../../../');
        
        $wrapper = <<<SCRIPT
<?php
require '$baseDir/vendor/autoload.php';
\$app = require '$baseDir/bootstrap/app.php';
\$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Obtain connection and signal READY
\$pid = \Illuminate\Support\Facades\DB::selectOne('SELECT pg_backend_pid() as pid')->pid;
file_put_contents('$readyFile', \$pid);

// Wait for GO barrier
while (!file_exists('$goFile')) {
    usleep(10000); // 10ms
}

// Execute test logic
\$logic = json_decode(base64_decode('$encoded'), true, 512, JSON_THROW_ON_ERROR);
eval(\$logic);
SCRIPT;
        
        $tmpFile = tempnam(sys_get_temp_dir(), 'concurrency_wrapper_') . '.php';
        file_put_contents($tmpFile, $wrapper);
        
        $process = new Process(['php', $tmpFile]);
        $process->setTimeout(30);
        $process->start();
        
        return $process;
    }
}