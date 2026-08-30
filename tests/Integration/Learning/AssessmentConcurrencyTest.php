<?php

namespace Tests\Integration\Learning;

use App\Modules\Learning\Application\AssessmentService;
use App\Modules\Learning\Models\AssessmentAttempt;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

class AssessmentConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_evaluation_is_serialized_and_idempotent(): void
    {
        if (!function_exists('pcntl_fork') || !function_exists('stream_socket_pair') || !function_exists('posix_kill') || getenv('DB_CONNECTION') === 'sqlite' || DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('NOT_RUN_DIRECT_EVIDENCE_UNAVAILABLE: True concurrency test requires pcntl_fork, posix_kill, socket pairs, and PostgreSQL.');
        }

        $service = new AssessmentService();
        $def = $service->createDefinition('ASSESS-CONC', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        
        $actorId = (string) Str::uuid7();
        $attempt = $service->startAttempt((string)$def->id, $actorId);
        
        // Directly submit via DB to avoid calling evaluateAttempt internally
        DB::table('assessment_attempts')->where('id', $attempt->id)->update([
            'status' => 'submitted',
            'answers' => json_encode(['q1' => 'A']),
            'submitted_at' => now(),
            'updated_at' => now(),
        ]);

        // Precondition: No pre-existing result
        $this->assertEquals(0, DB::table('assessment_results')->where('assessment_attempt_id', $attempt->id)->count());

        // Create IPC socket pairs
        $sockets1 = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $sockets2 = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        
        if (!$sockets1 || !$sockets2) {
            $this->fail('Could not create socket pairs for IPC.');
        }

        // Disconnect Laravel connection before fork
        DB::disconnect();

        // 1. Parent establishes a raw PDO connection to hold the authoritative row lock
        $dsn = config('database.connections.pgsql.url') ?? 'pgsql:host='.config('database.connections.pgsql.host').';port='.config('database.connections.pgsql.port').';dbname='.config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');
        
        $parentPdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $parentPdo->beginTransaction();
        
        // Hold the row lock for the specific attempt
        $stmt = $parentPdo->prepare("SELECT id FROM assessment_attempts WHERE id = :id FOR UPDATE");
        $stmt->execute(['id' => $attempt->id]);
        
        if (!$stmt->fetch()) {
            $this->fail('Parent could not acquire row lock.');
        }

        $pids = [];

        try {
            // Child 1
            $pid1 = pcntl_fork();
            if ($pid1 === -1) {
                $this->fail('Could not fork child 1.');
            }
            if ($pid1 === 0) {
                fclose($sockets1[0]); // Close parent end
                fclose($sockets2[0]);
                fclose($sockets2[1]);
                
                try {
                    DB::reconnect();
                    $childPdo = DB::connection()->getPdo();
                    $childPid = $childPdo->query("SELECT pg_backend_pid()")->fetchColumn();
                    
                    // Report ready
                    fwrite($sockets1[1], "READY {$childPid}\n");
                    
                    // Wait for GO with explicit timeout handling on child side (optional but safe)
                    stream_set_timeout($sockets1[1], 10);
                    $msg = trim((string) fgets($sockets1[1]));
                    if ($msg !== 'GO') {
                        exit(1);
                    }
                    
                    // Invoke production evaluation - this WILL BLOCK on the lockForUpdate
                    $svc = new AssessmentService();
                    $att = AssessmentAttempt::query()->find($attempt->id);
                    $result = $svc->evaluateAttempt($att);
                    
                    // Report success
                    fwrite($sockets1[1], "RESULT {$result->id}\n");
                    exit(0);
                } catch (\Throwable $e) {
                    fwrite($sockets1[1], "ERROR " . $e->getMessage() . "\n");
                    exit(1);
                }
            }
            $pids[] = $pid1;

            // Child 2
            $pid2 = pcntl_fork();
            if ($pid2 === -1) {
                $this->fail('Could not fork child 2.');
            }
            if ($pid2 === 0) {
                fclose($sockets2[0]); // Close parent end
                fclose($sockets1[0]);
                fclose($sockets1[1]);
                
                try {
                    DB::reconnect();
                    $childPdo = DB::connection()->getPdo();
                    $childPid = $childPdo->query("SELECT pg_backend_pid()")->fetchColumn();
                    
                    // Report ready
                    fwrite($sockets2[1], "READY {$childPid}\n");
                    
                    // Wait for GO
                    stream_set_timeout($sockets2[1], 10);
                    $msg = trim((string) fgets($sockets2[1]));
                    if ($msg !== 'GO') {
                        exit(1);
                    }
                    
                    // Invoke production evaluation - this WILL BLOCK on the lockForUpdate
                    $svc = new AssessmentService();
                    $att = AssessmentAttempt::query()->find($attempt->id);
                    $result = $svc->evaluateAttempt($att);
                    
                    // Report success
                    fwrite($sockets2[1], "RESULT {$result->id}\n");
                    exit(0);
                } catch (\Throwable $e) {
                    fwrite($sockets2[1], "ERROR " . $e->getMessage() . "\n");
                    exit(1);
                }
            }
            $pids[] = $pid2;

            // Parent process
            fclose($sockets1[1]); // Close child ends
            fclose($sockets2[1]);

            // Setup bounded IPC reads
            stream_set_timeout($sockets1[0], 5);
            stream_set_timeout($sockets2[0], 5);

            // Read READY signals and extract PIDs
            $ready1 = trim((string) fgets($sockets1[0]));
            if (!$ready1) {
                $this->fail("Child 1 failed to send READY within timeout.");
            }
            $ready2 = trim((string) fgets($sockets2[0]));
            if (!$ready2) {
                $this->fail("Child 2 failed to send READY within timeout.");
            }
            
            if (!str_starts_with($ready1, 'READY') || !str_starts_with($ready2, 'READY')) {
                $this->fail("Children failed to report valid ready state: {$ready1}, {$ready2}");
            }
            
            $child1DbPid = (int) explode(' ', $ready1)[1];
            $child2DbPid = (int) explode(' ', $ready2)[1];

            // Send GO signal
            fwrite($sockets1[0], "GO\n");
            fwrite($sockets2[0], "GO\n");

            // Open observer connection to verify both children are waiting on the lock
            $observerPdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $waitersConfirmed = false;
            $attempts = 0;
            
            while ($attempts < 50) { // Max 5 seconds (50 * 100ms)
                $stmt = $observerPdo->prepare("SELECT COUNT(*) FROM pg_stat_activity WHERE pid IN (?, ?) AND wait_event_type = 'Lock'");
                $stmt->execute([$child1DbPid, $child2DbPid]);
                $count = (int) $stmt->fetchColumn();
                
                if ($count === 2) {
                    $waitersConfirmed = true;
                    break;
                }
                
                usleep(100000);
                $attempts++;
            }
            $observerPdo = null;

            if (!$waitersConfirmed) {
                $this->fail("Failed to deterministically observe both child backends waiting on the PostgreSQL row lock.");
            }

            // Both children are definitively blocked on evaluateAttempt() due to our row lock.
            // Release the lock, allowing serialization to resolve.
            $parentPdo->commit();
            $parentPdo = null;

            // Bounded wait for results
            stream_set_timeout($sockets1[0], 5);
            $res1 = trim((string) fgets($sockets1[0]));
            if (!$res1) {
                $this->fail("Child 1 failed to send RESULT within timeout.");
            }

            stream_set_timeout($sockets2[0], 5);
            $res2 = trim((string) fgets($sockets2[0]));
            if (!$res2) {
                $this->fail("Child 2 failed to send RESULT within timeout.");
            }

            $this->assertStringStartsWith('RESULT ', $res1);
            $this->assertStringStartsWith('RESULT ', $res2);
            
            $resultId1 = explode(' ', $res1)[1];
            $resultId2 = explode(' ', $res2)[1];

            $this->assertEquals($resultId1, $resultId2, 'Concurrent evaluations returned divergent result identities.');

            // Verify final exact database state
            DB::reconnect();
            $results = DB::table('assessment_results')->where('assessment_attempt_id', $attempt->id)->get();
            $this->assertCount(1, $results, 'Expected exactly one AssessmentResult to exist for the attempt.');
            $this->assertEquals($resultId1, $results->first()->id, 'The persisted AssessmentResult ID must exactly match the returned result ID from children.');

        } finally {
            if ($parentPdo instanceof PDO && $parentPdo->inTransaction()) {
                $parentPdo->rollBack();
            }
            
            @fclose($sockets1[0]);
            @fclose($sockets2[0]);

            // Safely reap and cleanup children with bounded wait
            foreach ($pids as $pid) {
                $waitAttempts = 0;
                $exited = false;
                while ($waitAttempts < 50) { // 5 seconds grace period
                    $res = pcntl_waitpid($pid, $status, WNOHANG);
                    if ($res === -1 || $res > 0) {
                        $exited = true;
                        break;
                    }
                    usleep(100000);
                    $waitAttempts++;
                }

                if (!$exited) {
                    posix_kill($pid, SIGKILL);
                    pcntl_waitpid($pid, $status); // Block until definitely reaped
                }
            }
        }
    }
}
