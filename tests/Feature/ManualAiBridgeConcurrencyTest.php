<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;
use App\Modules\ManualAiBridge\Application\ManualAiBridgeService;
use App\Modules\ManualAiBridge\Models\ImportedAiResult;
use App\Modules\ManualAiBridge\Models\PromptPackage;
use App\Modules\ManualAiBridge\Models\PromptPackageRevision;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use Illuminate\Support\Facades\DB;
use App\Modules\Platform\Packages\PortablePackageRecord;

final class ManualAiBridgeConcurrencyTest extends TestCase
{
    // Deliberately DO NOT use RefreshDatabase or DatabaseTransactions trait
    // to ensure seeded data is actually committed and visible across forked pg backend pids.

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Ensure a clean database that is committed and accessible by other processes.
        $this->artisan('migrate:fresh', ['--seed' => true]);
    }

    private function owner()
    {
        return app(CreateOwner::class)->execute(
            'Manual AI Owner',
            'manual-ai-owner-'.Str::lower(Str::random(8)).'@example.test',
            'VeryStrong!Pass9',
            (string) Str::uuid7(),
        );
    }

    public function test_real_pg_concurrent_duplicate_import_idempotency_via_db_advisory_locks(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('NOT_EXECUTED_ENVIRONMENT_LIMITATION: pcntl_fork is required for real concurrency testing.');
        }

        $owner = $this->owner();
        $actorId = (string) $owner->getAuthIdentifier();

        // 1. Export prompt (committed instantly because no RefreshDatabase transaction is holding it)
        $this->actingAs($owner)->post('/system/ai-bridge/prompts/export', [
            'purpose' => 'Correct KU-SEC-10',
            'knowledge_unit_id' => 'KU-SEC-10',
            'instruction' => 'Refine paragraph on access control.',
        ]);

        $prompt = PromptPackage::query()->where('purpose', 'Correct KU-SEC-10')->firstOrFail();
        $revision = PromptPackageRevision::query()->where('prompt_package_id', $prompt->id)->firstOrFail();

        // 2. Prepare SafePackage ZIP
        $resultPayload = [
            'prompt_package_id' => (string) $prompt->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $revision->input_digest,
            'knowledge_unit_id' => 'KU-SEC-10',
            'proposed_blocks' => [
                ['proposal_id' => 'prop_1', 'type' => 'paragraph', 'body' => 'مقترح مسودة معتمد بعد مراجعة دقيقة.']
            ],
            'citation_claim_ids' => ['WIN-AUTH-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['human review required'],
            'confidence' => 'high',
        ];

        $created = app(\App\Modules\Platform\Packages\SafePackageService::class)->create(
            'manual-ai-result',
            1,
            $actorId,
            [
                'prompt_package_id' => (string) $prompt->id,
                'prompt_revision' => 1,
                'input_digest' => (string) $revision->input_digest,
            ],
            ['result.json' => json_encode($resultPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)],
            ownerModule: 'MOD-AIB',
        );

        $blobStream = app(\App\Modules\Platform\Blobs\BlobStore::class)->readStream($created['blob_key']);

        $zipPath = tempnam(sys_get_temp_dir(), 'aib_pkg_') . '.zip';
        file_put_contents($zipPath, stream_get_contents($blobStream));
        fclose($blobStream);

        $packagesBefore = PortablePackageRecord::query()->count();
        $resultsBefore = ImportedAiResult::query()->count();
        $auditBefore = DB::table('audit_records')->where('action', 'manual_ai.result.imported')->count();

        // Setup deterministic concurrency barrier
        $barrierDir = sys_get_temp_dir() . '/aib_concurrency_' . uniqid();
        mkdir($barrierDir);
        $goFile = $barrierDir . '/GO';

        // We will fork 3 child processes to race the import.
        $children = 3;
        $pids = [];

        for ($i = 0; $i < $children; $i++) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                $this->fail('Could not fork');
            } elseif ($pid === 0) {
                // CHILD PROCESS
                $app = require __DIR__.'/../../bootstrap/app.php';
                $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

                // 1. Purge/reconnect DB through the same bootstrapped app container
                \Illuminate\Support\Facades\DB::purge();
                \Illuminate\Support\Facades\DB::reconnect();

                $pgPid = \Illuminate\Support\Facades\DB::selectOne('SELECT pg_backend_pid() as pid')->pid;
                if (!$pgPid) {
                    exit(1);
                }

                // Resolve bridge from same app container AFTER db bind
                $bridge = $app->make(ManualAiBridgeService::class);

                // Signal ready by writing backend PID
                file_put_contents($barrierDir . '/ready_' . getmypid(), (string) $pgPid);

                // Wait for parent GO signal (with 10-second timeout)
                $waits = 0;
                while (!file_exists($goFile)) {
                    usleep(10000); // 10ms
                    if (++$waits > 1000) exit(1);
                }

                try {
                    $fp = fopen($zipPath, 'rb');
                    $imported = $bridge->importResult($fp, $actorId);
                    fclose($fp);

                    // 2. Persist the returned ImportedAiResult ID into a per-child file
                    file_put_contents($barrierDir . '/result_' . getmypid(), $imported->id);
                    exit(0);
                } catch (\Throwable $e) {
                    exit(1);
                }
            } else {
                // PARENT PROCESS
                $pids[] = $pid;
            }
        }

        // Parent waits for all children to signal ready
        $waits = 0;
        $readyFiles = [];
        while (count($readyFiles) < $children) {
            usleep(10000); // 10ms
            $readyFiles = glob($barrierDir . '/ready_*');
            if (++$waits > 1000) {
                $this->fail('Timeout waiting for children to become ready.');
            }
        }

        // Collect PIDs to assert distinct connections
        $backendPids = [];
        foreach ($readyFiles as $f) {
            $backendPids[] = file_get_contents($f);
        }

        // Release the barrier
        touch($goFile);

        // Parent waits for all children
        $successes = 0;
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            if (pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0) {
                $successes++;
            }
        }

        // Ensure child processes complete filesystem writes
        usleep(50000); // 50ms

        // Cleanup moved after result ID collection
        @unlink($zipPath);

        // F2 Assertion: Prove distinct PostgreSQL backend connections
        $this->assertSame($children, count($backendPids), 'Expected to collect PIDs for all children.');
        $this->assertSame($children, count(array_unique($backendPids)), 'Each child process must have a distinctly verified pg_backend_pid.');

        $this->assertSame($children, $successes, 'All concurrent child processes should successfully return the canonical result without throwing errors.');

        // 2. Caller returned IDs
        $resultFiles = glob($barrierDir . '/result_*');
        $this->assertSame($children, count($resultFiles), 'Each child must persist a result ID.');

        $returnedIds = [];
        foreach ($resultFiles as $rf) {
            $id = file_get_contents($rf);
            $this->assertNotEmpty($id, 'Returned ID cannot be empty');
            $returnedIds[] = $id;
        }
        $this->assertSame(1, count(array_unique($returnedIds)), 'All concurrent children must return the exact same canonical ID.');
        $uniqueReturnedId = $returnedIds[0];

        // Reconnect parent to ensure fresh data state
        DB::purge();
        DB::reconnect();

        $packagesAfter = PortablePackageRecord::query()->count();
        $resultsAfter = ImportedAiResult::query()->count();
        $auditAfter = DB::table('audit_records')->where('action', 'manual_ai.result.imported')->count();

        // 4. RETAIN DELTAS
        // Exactly one canonical ImportedAiResult created.
        $this->assertSame($resultsBefore + 1, $resultsAfter, 'Exactly 1 canonical ImportedAiResult should be created despite concurrent duplicate imports.');

        // Exactly one durable side-effect (PortablePackageRecord import mirror).
        $this->assertSame($packagesBefore + 1, $packagesAfter, 'Exactly 1 import mirror package should be created; no orphan side-effects permitted.');

        // Exactly one success-side audit record for the imported result.
        $this->assertSame($auditBefore + 1, $auditAfter, 'Exactly 1 manual_ai.result.imported success audit should be appended (delta).');

        // 3. EXACT CANONICAL ORACLE
        $expectedResultDigest = \App\Modules\Platform\Support\CanonicalJson::sha256($resultPayload);
        $canonicalResult = ImportedAiResult::query()
            ->where('prompt_package_revision_id', $revision->id)
            ->where('result_digest', $expectedResultDigest)
            ->firstOrFail();

        $this->assertSame($canonicalResult->id, $uniqueReturnedId, 'The single unique child-returned ID must equal the exact canonical row ID.');
        $this->assertNotNull($canonicalResult->portable_package_id);

        array_map('unlink', glob($barrierDir . '/*'));
        rmdir($barrierDir);
    }
}
