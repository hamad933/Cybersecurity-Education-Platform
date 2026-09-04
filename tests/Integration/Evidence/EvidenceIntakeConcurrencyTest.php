<?php

namespace Tests\Integration\Evidence;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvidenceIntakeConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_idempotent_receive_asserts_distinct_pids()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test_source_id', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $logic = <<<LOGIC
        \$service = \$app->make(\App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService::class);
        try {
            \$service->receive('$actorId', '$actorId', [
                'handoff_receipt_id' => '$receiptId',
                'evidence_claim' => 'concurrent claim',
                'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
                'title' => 'Title',
                'summary' => 'Summary',
            ]);
            echo json_encode(['pid' => \$pid, 'success' => true]) . "\n";
        } catch (\Exception \$e) {
            echo json_encode(['pid' => \$pid, 'error' => \$e->getMessage()]) . "\n";
        }
LOGIC;

        $r1 = tempnam(sys_get_temp_dir(), 'r1_'); unlink($r1);
        $r2 = tempnam(sys_get_temp_dir(), 'r2_'); unlink($r2);
        $go = tempnam(sys_get_temp_dir(), 'go_'); unlink($go);

        $p1 = ConcurrencyHarness::executeInNewProcess($logic, $r1, $go);
        $p2 = ConcurrencyHarness::executeInNewProcess($logic, $r2, $go);
        
        // Wait for both to be ready
        $waited = 0;
        while (!file_exists($r1) || !file_exists($r2)) {
            usleep(50000);
            $waited += 50000;
            if ($waited > 5000000) {
                $this->fail("Processes did not become ready.");
            }
        }
        
        $pid1 = file_get_contents($r1);
        $pid2 = file_get_contents($r2);
        $this->assertNotEquals($pid1, $pid2, 'Concurrent processes must use distinct database backend PIDs.');
        
        // GO!
        file_put_contents($go, 'GO');
        
        $p1->wait();
        $p2->wait();

        $out1 = $p1->getOutput();
        $out2 = $p2->getOutput();

        $res1 = json_decode(trim($out1), true);
        $res2 = json_decode(trim($out2), true);
        
        $this->assertNotNull($res1['pid'] ?? null, "Process 1 failed: " . $p1->getErrorOutput());
        $this->assertNotNull($res2['pid'] ?? null, "Process 2 failed: " . $p2->getErrorOutput());
        
        $this->assertEquals(1, DB::table('evidence_candidates')->count(), 'Receives must be idempotent and produce exactly one candidate.');
        
        @unlink($r1); @unlink($r2); @unlink($go);
    }
    
    public function test_concurrent_amend_prep_race()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test_source_id', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = $this->app->make(\App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService::class);
        $candidate = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId,
            'evidence_claim' => 'concurrent claim',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Title',
            'summary' => 'Summary',
        ]);
        $cId = $candidate['id'];

        $logic = <<<LOGIC
        \$service = \$app->make(\App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService::class);
        try {
            // Using different summary to bypass same-content no-op
            \$rand = bin2hex(random_bytes(4));
            \$service->amendCandidate('$cId', '$actorId', [
                'title' => 'Title',
                'summary' => 'Summary ' . \$rand,
            ]);
            echo json_encode(['pid' => \$pid, 'success' => true]) . "\n";
        } catch (\Exception \$e) {
            echo json_encode(['pid' => \$pid, 'error' => \$e->getMessage()]) . "\n";
        }
LOGIC;

        $r1 = tempnam(sys_get_temp_dir(), 'r1_'); unlink($r1);
        $r2 = tempnam(sys_get_temp_dir(), 'r2_'); unlink($r2);
        $go = tempnam(sys_get_temp_dir(), 'go_'); unlink($go);

        $p1 = ConcurrencyHarness::executeInNewProcess($logic, $r1, $go);
        $p2 = ConcurrencyHarness::executeInNewProcess($logic, $r2, $go);
        
        $waited = 0;
        while (!file_exists($r1) || !file_exists($r2)) {
            usleep(50000);
            $waited += 50000;
            if ($waited > 5000000) {
                $this->fail("Processes did not become ready.");
            }
        }
        
        $pid1 = file_get_contents($r1);
        $pid2 = file_get_contents($r2);
        $this->assertNotEquals($pid1, $pid2, 'Concurrent processes must use distinct database backend PIDs.');
        
        file_put_contents($go, 'GO');
        
        $p1->wait();
        $p2->wait();

        $out1 = $p1->getOutput();
        $out2 = $p2->getOutput();

        $res1 = json_decode(trim($out1), true);
        $res2 = json_decode(trim($out2), true);
        
        $this->assertNotNull($res1['pid'] ?? null, "Process 1 failed: " . $p1->getErrorOutput());
        $this->assertNotNull($res2['pid'] ?? null, "Process 2 failed: " . $p2->getErrorOutput());
        
        // Original + 2 amends = 3 revisions.
        $this->assertEquals(3, DB::table('evidence_candidate_revisions')->where('candidate_id', $cId)->count(), 'Concurrent amends must be serialized and produce multiple revisions.');
        
        @unlink($r1); @unlink($r2); @unlink($go);
    }
    
    public function test_concurrent_admission_race()
    {
        $actorId = (string) Str::uuid7();
        $receiptId1 = (string) Str::uuid7();
        $receiptId2 = (string) Str::uuid7();
        $receiptId3 = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            ['id' => $receiptId1, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 's1', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['id' => $receiptId2, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 's2', 'source_revision' => '1', 'source_digest' => str_repeat('c', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('d', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['id' => $receiptId3, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 's3', 'source_revision' => '1', 'source_digest' => str_repeat('e', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('f', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $service = $this->app->make(\App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService::class);
        
        // 1. Create a base candidate and admit it to create the canonical evidence.
        $baseCandidate = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId1,
            'evidence_claim' => 'base claim',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Base',
            'summary' => 'Base Summary',
        ]);
        
        $baseId = $baseCandidate['id'];
        $service->transitionCandidate($baseId, $actorId, \App\Modules\Evidence\IntakeReview\Domain\CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($baseId, $actorId, \App\Modules\Evidence\IntakeReview\Domain\CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        $admittedBase = $service->admitCandidate($baseId, $actorId);
        
        $canonicalEvidenceId = $admittedBase['evidence']['id'];
        $canonicalEvidenceRevisionId = $admittedBase['revision']['id'];
        
        // 2. Create Candidate A targeting the canonical tip
        $candidateA = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId2,
            'evidence_claim' => 'n1 claim a',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'N+1 Title A',
            'summary' => 'N+1 Summary A',
            'target_evidence_id' => $canonicalEvidenceId,
            'target_evidence_revision_id' => $canonicalEvidenceRevisionId,
        ]);
        $idA = $candidateA['id'];
        $service->transitionCandidate($idA, $actorId, \App\Modules\Evidence\IntakeReview\Domain\CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($idA, $actorId, \App\Modules\Evidence\IntakeReview\Domain\CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        
        // 3. Create Candidate B targeting the SAME canonical tip
        $candidateB = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId3,
            'evidence_claim' => 'n1 claim b',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'N+1 Title B',
            'summary' => 'N+1 Summary B',
            'target_evidence_id' => $canonicalEvidenceId,
            'target_evidence_revision_id' => $canonicalEvidenceRevisionId,
        ]);
        $idB = $candidateB['id'];
        $service->transitionCandidate($idB, $actorId, \App\Modules\Evidence\IntakeReview\Domain\CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($idB, $actorId, \App\Modules\Evidence\IntakeReview\Domain\CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        
        $logicA = <<<LOGIC
        \$service = \$app->make(\App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService::class);
        try {
            \$service->admitCandidate('$idA', '$actorId');
            echo json_encode(['pid' => \$pid, 'success' => true]) . "\n";
        } catch (\Exception \$e) {
            echo json_encode(['pid' => \$pid, 'error' => \$e->getMessage()]) . "\n";
        }
LOGIC;

        $logicB = <<<LOGIC
        \$service = \$app->make(\App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService::class);
        try {
            \$service->admitCandidate('$idB', '$actorId');
            echo json_encode(['pid' => \$pid, 'success' => true]) . "\n";
        } catch (\Exception \$e) {
            echo json_encode(['pid' => \$pid, 'error' => \$e->getMessage()]) . "\n";
        }
LOGIC;

        $r1 = tempnam(sys_get_temp_dir(), 'r1_'); unlink($r1);
        $r2 = tempnam(sys_get_temp_dir(), 'r2_'); unlink($r2);
        $go = tempnam(sys_get_temp_dir(), 'go_'); unlink($go);

        $p1 = ConcurrencyHarness::executeInNewProcess($logicA, $r1, $go);
        $p2 = ConcurrencyHarness::executeInNewProcess($logicB, $r2, $go);
        
        $waited = 0;
        while (!file_exists($r1) || !file_exists($r2)) {
            usleep(50000);
            $waited += 50000;
            if ($waited > 5000000) {
                $this->fail("Processes did not become ready.");
            }
        }
        
        $pid1 = file_get_contents($r1);
        $pid2 = file_get_contents($r2);
        $this->assertNotEquals($pid1, $pid2, 'Concurrent processes must use distinct database backend PIDs.');
        
        file_put_contents($go, 'GO');
        
        $p1->wait();
        $p2->wait();

        $out1 = $p1->getOutput();
        $out2 = $p2->getOutput();

        $res1 = json_decode(trim($out1), true);
        $res2 = json_decode(trim($out2), true);
        
        $this->assertNotNull($res1['pid'] ?? null, "Process 1 failed: " . $p1->getErrorOutput());
        $this->assertNotNull($res2['pid'] ?? null, "Process 2 failed: " . $p2->getErrorOutput());
        
        // One must succeed, one must fail with staleness/conflict
        $successCount = (isset($res1['success']) ? 1 : 0) + (isset($res2['success']) ? 1 : 0);
        $errorCount = (isset($res1['error']) ? 1 : 0) + (isset($res2['error']) ? 1 : 0);
        
        $this->assertEquals(1, $successCount, 'Exactly one N+1 admission should succeed against the current tip.');
        $this->assertEquals(1, $errorCount, 'Exactly one N+1 admission should fail closed due to staleness/conflict.');
        
        $currentTip = DB::table('governed_evidence')->where('id', $canonicalEvidenceId)->value('current_revision_number');
        $this->assertEquals(2, $currentTip, 'Current revision number must advance exactly once.');
        
        $this->assertEquals(1, DB::table('governed_evidence_revisions')->where('evidence_id', $canonicalEvidenceId)->where('revision', 2)->count(), 'Must produce exactly one revision 2.');
        $this->assertEquals(2, DB::table('evidence_admission_records')->where('evidence_id', $canonicalEvidenceId)->count(), 'Must be exactly two admissions (base + N+1 winner) associated with the canonical evidence.');
        
        @unlink($r1); @unlink($r2); @unlink($go);
        
        // Clean up N+1 admission to allow rollback
        DB::statement('ALTER TABLE evidence_admission_records DISABLE TRIGGER evidence_admission_records_immutable');
        DB::statement('ALTER TABLE evidence_admission_candidate_revisions DISABLE TRIGGER trg_evidence_admission_candidate_revisions_immutable');
        $keep = DB::table('evidence_admission_records')
            ->where('evidence_id', $canonicalEvidenceId)
            ->orderBy('created_at', 'asc')
            ->value('id');
            
        DB::table('evidence_admission_candidate_revisions')
            ->whereNotIn('admission_id', [$keep])
            ->delete();
            
        DB::table('evidence_admission_records')
            ->where('evidence_id', $canonicalEvidenceId)
            ->where('id', '!=', $keep)
            ->delete();
        DB::statement('ALTER TABLE evidence_admission_candidate_revisions ENABLE TRIGGER trg_evidence_admission_candidate_revisions_immutable');
        DB::statement('ALTER TABLE evidence_admission_records ENABLE TRIGGER evidence_admission_records_immutable');
    }
}
