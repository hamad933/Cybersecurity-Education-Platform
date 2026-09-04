<?php

namespace Tests\Integration\Evidence;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvidenceIntakeReceiveConcurrencyCorrectionTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_identical_receives_both_succeed_and_reconcile_to_one_candidate(): void
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId,
            'source_type' => 'test_type', 'source_id' => 'same-source', 'source_revision' => '1',
            'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]',
            'capability_id' => (string) Str::uuid7(), 'facts' => '{"trusted":true}',
            'metadata' => '{"origin":"receipt"}', 'receipt_digest' => str_repeat('b', 64),
            'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $logic = <<<LOGIC
        \$service = \$app->make(\App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService::class);
        try {
            \$candidate = \$service->receive('$actorId', '$actorId', [
                'handoff_receipt_id' => '$receiptId',
                'evidence_claim' => 'same claim',
                'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
                'title' => 'Title',
                'summary' => 'Summary',
            ]);
            echo json_encode(['pid' => \$pid, 'success' => true, 'candidate_id' => \$candidate['id']]) . "\n";
        } catch (\Throwable \$e) {
            echo json_encode(['pid' => \$pid, 'success' => false, 'error' => \$e->getMessage()]) . "\n";
        }
LOGIC;

        $ready1 = tempnam(sys_get_temp_dir(), 'recv_r1_'); unlink($ready1);
        $ready2 = tempnam(sys_get_temp_dir(), 'recv_r2_'); unlink($ready2);
        $go = tempnam(sys_get_temp_dir(), 'recv_go_'); unlink($go);
        $process1 = ConcurrencyHarness::executeInNewProcess($logic, $ready1, $go);
        $process2 = ConcurrencyHarness::executeInNewProcess($logic, $ready2, $go);

        $waited = 0;
        while (! file_exists($ready1) || ! file_exists($ready2)) {
            usleep(50000);
            $waited += 50000;
            if ($waited > 5000000) {
                $this->fail('Processes did not become ready.');
            }
        }
        $this->assertNotSame(file_get_contents($ready1), file_get_contents($ready2));
        file_put_contents($go, 'GO');
        $process1->wait();
        $process2->wait();

        $result1 = json_decode(trim($process1->getOutput()), true, 512, JSON_THROW_ON_ERROR);
        $result2 = json_decode(trim($process2->getOutput()), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($result1['success'], $result1['error'] ?? $process1->getErrorOutput());
        $this->assertTrue($result2['success'], $result2['error'] ?? $process2->getErrorOutput());
        $this->assertSame($result1['candidate_id'], $result2['candidate_id']);
        $this->assertSame(1, DB::table('evidence_candidates')->count());
        $this->assertSame(1, DB::table('evidence_candidate_revisions')->count());

        @unlink($ready1); @unlink($ready2); @unlink($go);
    }
}
