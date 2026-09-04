<?php

namespace Tests\Feature\IntakeReview;

use App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService;
use App\Modules\Evidence\IntakeReview\Domain\CandidateEvidenceState;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvidenceIntakeFeatureTest extends TestCase
{
    use DatabaseMigrations;

    public function test_bounds_and_validations()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test_source_id', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = $this->app->make(EvidenceIntakeService::class);
        
        $json = str_pad('{"a":"', 65534, 'a') . '"}';
        $candidate = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId,
            'evidence_claim' => 'concurrent claim',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Title',
            'summary' => 'Summary',
            'facts' => $json,
        ]);
        
        $this->assertNotNull($candidate['id']);
        
        $json2 = str_pad('{"a":"', 65535, 'a') . '"}';
        $thrown = false;
        try {
            $service->receive($actorId, $actorId, [
                'handoff_receipt_id' => $receiptId,
                'evidence_claim' => 'claim 2',
                'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
                'title' => 'Title',
                'summary' => 'Summary',
                'facts' => $json2,
            ]);
        } catch (\Exception $e) {
            if ($e instanceof IntakeReviewException && strpos($e->getMessage(), '64KiB') !== false) {
                $thrown = true;
            }
        }
        $this->assertTrue($thrown, 'Must reject facts > 65536 bytes.');
    }
    
    public function test_same_content_noop_on_amend()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test_source_id', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = $this->app->make(EvidenceIntakeService::class);
        $candidate = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId,
            'evidence_claim' => 'claim',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Title',
            'summary' => 'Summary',
            'facts' => '{"some":"fact"}',
            'metadata' => '{}',
        ]);
        
        $cId = $candidate['id'];
        $revs1 = DB::table('evidence_candidate_revisions')->where('candidate_id', $cId)->count();
        $this->assertEquals(1, $revs1);
        
        $service->amendCandidate($cId, $actorId, [
            'title' => 'Title',
            'summary' => 'Summary',
            'facts' => '{"some": "fact"}',
            'metadata' => '{}',
        ]);
        
        $revs2 = DB::table('evidence_candidate_revisions')->where('candidate_id', $cId)->count();
        $this->assertEquals(1, $revs2, 'Identical content amend must be a no-op.');
        
        $service->amendCandidate($cId, $actorId, [
            'title' => 'Title',
            'summary' => 'Summary Changed',
            'facts' => '{"some": "fact"}',
            'metadata' => '{}',
        ]);
        
        $revs3 = DB::table('evidence_candidate_revisions')->where('candidate_id', $cId)->count();
        $this->assertEquals(2, $revs3, 'Different content amend must create a new revision.');
    }
    
    public function test_invalid_json_types_fail()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'type', 'source_id' => 'id', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = $this->app->make(EvidenceIntakeService::class);
        $thrown = false;
        try {
            $service->receive($actorId, $actorId, [
                'handoff_receipt_id' => $receiptId,
                'evidence_claim' => 'claim',
                'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
                'title' => 'Title',
                'summary' => 'Summary',
                'facts' => '"invalid_scalar"',
            ]);
        } catch (\Exception $e) {
            if ($e instanceof IntakeReviewException && strpos($e->getMessage(), 'Invalid or non-associative JSON.') !== false) {
                $thrown = true;
            }
        }
        $this->assertTrue($thrown, 'Must fail on invalid JSON.');
    }

    public function test_target_pair_requires_both()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test', 'source_id' => 'test', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = $this->app->make(EvidenceIntakeService::class);
        $thrown = false;
        try {
            $service->receive($actorId, $actorId, [
                'handoff_receipt_id' => $receiptId,
                'evidence_claim' => 'claim',
                'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
                'title' => 'Title',
                'summary' => 'Summary',
                'target_evidence_id' => (string) Str::uuid7(),
            ]);
        } catch (IntakeReviewException $e) {
            $thrown = true;
            $this->assertStringContainsString('Target evidence ID and revision ID must be provided together.', $e->getMessage());
        }
        $this->assertTrue($thrown);
    }
    
    public function test_lifecycle_transition_noop()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test', 'source_id' => 'test', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = $this->app->make(EvidenceIntakeService::class);
        $candidate = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId,
            'evidence_claim' => 'claim',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Title',
            'summary' => 'Summary',
        ]);
        
        $cId = $candidate['id'];
        
        $events = DB::table('evidence_candidate_intake_events')->where('candidate_id', $cId)->count();
        $this->assertEquals(1, $events);
        
        $service->transitionCandidate($cId, $actorId, CandidateEvidenceState::RECEIVED->value);
        
        $events2 = DB::table('evidence_candidate_intake_events')->where('candidate_id', $cId)->count();
        $this->assertEquals(1, $events2, 'Same state transition must be a no-op.');
        
        $thrown = false;
        try {
            $service->transitionCandidate($cId, $actorId, CandidateEvidenceState::ADMITTED->value);
        } catch (IntakeReviewException $e) {
            $thrown = true;
            $this->assertStringContainsString('Admission must use the governed admitCandidate operation', $e->getMessage());
        }
        $this->assertTrue($thrown);
    }

    public function test_bounds_metadata()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test', 'source_id' => 'test', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = $this->app->make(EvidenceIntakeService::class);
        
        $json = str_pad('{"a":"', 65534, 'a') . '"}';
        $candidate = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId,
            'evidence_claim' => 'claim',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Title',
            'summary' => 'Summary',
            'metadata' => $json,
        ]);
        
        $this->assertNotNull($candidate['id']);
        
        $json2 = str_pad('{"a":"', 65535, 'a') . '"}';
        $thrown = false;
        try {
            $service->receive($actorId, $actorId, [
                'handoff_receipt_id' => $receiptId,
                'evidence_claim' => 'claim 2',
                'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
                'title' => 'Title',
                'summary' => 'Summary',
                'metadata' => $json2,
            ]);
        } catch (IntakeReviewException $e) {
            $thrown = true;
            $this->assertStringContainsString('64KiB', $e->getMessage());
        }
        $this->assertTrue($thrown);
    }

    public function test_target_pair_valid_append()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test', 'source_id' => 'test', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = $this->app->make(EvidenceIntakeService::class);
        $base = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId,
            'evidence_claim' => 'base',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Title',
            'summary' => 'Summary',
        ]);
        
        $service->transitionCandidate($base['id'], $actorId, CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($base['id'], $actorId, CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        $admittedBase = $service->admitCandidate($base['id'], $actorId);
        
        $canonicalEvidenceId = $admittedBase['evidence']['id'];
        $canonicalEvidenceRevisionId = $admittedBase['revision']['id'];
        
        $n1 = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId,
            'evidence_claim' => 'n1',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Title N1',
            'summary' => 'Summary N1',
            'target_evidence_id' => $canonicalEvidenceId,
            'target_evidence_revision_id' => $canonicalEvidenceRevisionId,
        ]);
        
        $this->assertEquals($canonicalEvidenceId, $n1['target_evidence_id']);
        $this->assertEquals($canonicalEvidenceRevisionId, $n1['target_evidence_revision_id']);
        
        $service->transitionCandidate($n1['id'], $actorId, CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($n1['id'], $actorId, CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        $admittedN1 = $service->admitCandidate($n1['id'], $actorId);
        
        $this->assertEquals(2, $admittedN1['evidence']['current_revision_number']);
        $this->assertEquals($canonicalEvidenceRevisionId, $admittedN1['revision']['previous_revision_id']);
        
        // Clean up N+1 admission to allow rollback
        DB::statement('ALTER TABLE evidence_admission_records DISABLE TRIGGER evidence_admission_records_immutable');
        DB::statement('ALTER TABLE evidence_admission_candidate_revisions DISABLE TRIGGER trg_evidence_admission_candidate_revisions_immutable');
        DB::table('evidence_admission_candidate_revisions')->where('admission_id', $admittedN1['admission']['id'])->delete();
        DB::table('evidence_admission_records')->where('id', $admittedN1['admission']['id'])->delete();
        DB::statement('ALTER TABLE evidence_admission_candidate_revisions ENABLE TRIGGER trg_evidence_admission_candidate_revisions_immutable');
        DB::statement('ALTER TABLE evidence_admission_records ENABLE TRIGGER evidence_admission_records_immutable');
    }

    public function test_bounds_amend()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test', 'source_id' => 'test', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = $this->app->make(EvidenceIntakeService::class);
        $candidate = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId,
            'evidence_claim' => 'claim',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Title',
            'summary' => 'Summary',
        ]);
        
        $json = str_pad('{"a":"', 65534, 'a') . '"}';
        $amended = $service->amendCandidate($candidate['id'], $actorId, [
            'title' => 'Title',
            'summary' => 'Summary',
            'facts' => $json,
        ]);
        $this->assertNotNull($amended['id']);
        
        $json2 = str_pad('{"a":"', 65535, 'a') . '"}';
        $thrown = false;
        try {
            $service->amendCandidate($candidate['id'], $actorId, [
                'title' => 'Title',
                'summary' => 'Summary',
                'metadata' => $json2,
            ]);
        } catch (IntakeReviewException $e) {
            $thrown = true;
            $this->assertStringContainsString('64KiB', $e->getMessage());
        }
        $this->assertTrue($thrown);
    }

    public function test_target_pair_stale_rejection()
    {
        $actorId = (string) Str::uuid7();
        $receiptId1 = (string) Str::uuid7();
        $receiptId2 = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            ['id' => $receiptId1, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test', 'source_id' => 's1', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['id' => $receiptId2, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test', 'source_id' => 's2', 'source_revision' => '1', 'source_digest' => str_repeat('c', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('d', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $service = $this->app->make(EvidenceIntakeService::class);
        $base = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId1,
            'evidence_claim' => 'base',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Title',
            'summary' => 'Summary',
        ]);
        
        $service->transitionCandidate($base['id'], $actorId, CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($base['id'], $actorId, CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        $admittedBase = $service->admitCandidate($base['id'], $actorId);
        
        $canonicalEvidenceId = $admittedBase['evidence']['id'];
        $canonicalEvidenceRevisionId = $admittedBase['revision']['id'];
        
        $n1 = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId2,
            'evidence_claim' => 'n1',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Title N1',
            'summary' => 'Summary N1',
            'target_evidence_id' => $canonicalEvidenceId,
            'target_evidence_revision_id' => $canonicalEvidenceRevisionId,
        ]);
        
        $service->transitionCandidate($n1['id'], $actorId, CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($n1['id'], $actorId, CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        $admittedN1 = $service->admitCandidate($n1['id'], $actorId);
        
        $n2 = $service->receive($actorId, $actorId, [
            'handoff_receipt_id' => $receiptId2,
            'evidence_claim' => 'n2 stale',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Title N2',
            'summary' => 'Summary N2',
            'target_evidence_id' => $canonicalEvidenceId,
            'target_evidence_revision_id' => $canonicalEvidenceRevisionId,
        ]);
        
        $service->transitionCandidate($n2['id'], $actorId, CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($n2['id'], $actorId, CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        
        $thrown = false;
        try {
            $service->admitCandidate($n2['id'], $actorId);
        } catch (IntakeReviewException $e) {
            $thrown = true;
            $this->assertStringContainsString('does not match candidate target revision', $e->getMessage());
        }
        $this->assertTrue($thrown);
        
        DB::statement('ALTER TABLE evidence_admission_records DISABLE TRIGGER evidence_admission_records_immutable');
        DB::statement('ALTER TABLE evidence_admission_candidate_revisions DISABLE TRIGGER trg_evidence_admission_candidate_revisions_immutable');
        DB::table('evidence_admission_candidate_revisions')->where('admission_id', $admittedN1['admission']['id'])->delete();
        DB::table('evidence_admission_records')->where('id', $admittedN1['admission']['id'])->delete();
        DB::statement('ALTER TABLE evidence_admission_candidate_revisions ENABLE TRIGGER trg_evidence_admission_candidate_revisions_immutable');
        DB::statement('ALTER TABLE evidence_admission_records ENABLE TRIGGER evidence_admission_records_immutable');
    }
}