<?php

namespace Tests\Integration\Evidence;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvidenceIntakeMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_migration_lifecycle_determinism_and_preflight()
    {
        $actorId = (string) Str::uuid7();
        $legacyId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        $legacyDate = now()->subDays(5);
        $digest = str_repeat('a', 64);

        $migration = require database_path('migrations/2026_08_29_010500_implement_candidate_revisions_and_n1_admissions.php');
        
        // Assert DOWN rollback
        $migration->down();
        $this->assertFalse(Schema::hasTable('evidence_candidate_revisions'));
        
        // Now insert legacy data
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId,
            'subject_actor_id' => $actorId,
            'registered_by' => $actorId,
            'source_type' => 'test_type',
            'source_id' => 'test_source_id',
            'source_revision' => '1',
            'source_digest' => $digest,
            'selected_material_refs' => '[]',
            'capability_id' => (string) Str::uuid7(),
            'facts' => '{}',
            'metadata' => '{}',
            'receipt_digest' => str_repeat('b', 64),
            'registered_at' => $legacyDate,
            'created_at' => $legacyDate,
            'updated_at' => $legacyDate,
        ]);
        
                DB::statement('ALTER TABLE evidence_candidates DROP CONSTRAINT IF EXISTS evidence_candidates_semantic_identity_unique');
        DB::statement('DROP INDEX IF EXISTS evidence_candidates_semantic_identity_unique');
        
        DB::table('evidence_candidates')->insert([
            'id' => $legacyId, 'handoff_receipt_id' => $receiptId, 'subject_actor_id' => $actorId, 'submitted_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test_source_id', 'source_revision' => '1', 'source_digest' => $digest, 'capability_id' => (string) Str::uuid7(), 'evidence_claim' => 'claim', 'semantic_identity_digest' => 'digest1', 'state' => 'RECEIVED', 'proposed_title' => 'Title', 'proposed_summary' => 'Summary', 'proposed_facts' => '{}', 'metadata' => '{}', 'created_at' => $legacyDate, 'updated_at' => $legacyDate, 'selected_material_refs' => '[]', 'criterion_scope' => '[]', 'governed_purpose' => 'test_purpose'
        ]);

        // Reapply
        $migration->up();
        $this->assertEquals(1, DB::table('evidence_candidate_revisions')->count());
        $revision = DB::table('evidence_candidate_revisions')->where('candidate_id', $legacyId)->first();
        $this->assertNotNull($revision);
        
        // Check DOWN again to prove determinism
        $migration->down();
        $migration->up();
        $revision2 = DB::table('evidence_candidate_revisions')->where('candidate_id', $legacyId)->first();
        
        $this->assertEquals($revision->content_digest, $revision2->content_digest);
    }
    
    public function test_migration_immutability_triggers()
    {
        $actorId = (string) Str::uuid7();
        $legacyId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        $legacyDate = now()->subDays(5);
        $digest = str_repeat('a', 64);
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test_source_id', 'source_revision' => '1', 'source_digest' => $digest, 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => $legacyDate, 'created_at' => $legacyDate, 'updated_at' => $legacyDate,
        ]);
        
                DB::statement('ALTER TABLE evidence_candidates DROP CONSTRAINT IF EXISTS evidence_candidates_semantic_identity_unique');
        DB::statement('DROP INDEX IF EXISTS evidence_candidates_semantic_identity_unique');
        
        DB::table('evidence_candidates')->insert([
            'id' => $legacyId, 'handoff_receipt_id' => $receiptId, 'subject_actor_id' => $actorId, 'submitted_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test_source_id', 'source_revision' => '1', 'source_digest' => $digest, 'capability_id' => (string) Str::uuid7(), 'evidence_claim' => 'claim', 'semantic_identity_digest' => 'digest1', 'state' => 'RECEIVED', 'proposed_title' => 'Title', 'proposed_summary' => 'Summary', 'proposed_facts' => '{}', 'metadata' => '{}', 'created_at' => $legacyDate, 'updated_at' => $legacyDate, 'selected_material_refs' => '[]', 'criterion_scope' => '[]', 'governed_purpose' => 'test_purpose'
        ]);

        $migration = require database_path('migrations/2026_08_29_010500_implement_candidate_revisions_and_n1_admissions.php');
        
        // Assert DOWN rollback to start clean
        $migration->down();
        $migration->up();
        
        // Immutability checks
        $revision = DB::table('evidence_candidate_revisions')->where('candidate_id', $legacyId)->first();
        $this->assertNotNull($revision);
        
        $thrownUpdate = false;
        try {
            DB::table('evidence_candidate_revisions')->where('id', $revision->id)->update(['proposed_title' => 'changed']);
        } catch (\Exception $e) {
            $thrownUpdate = true;
            $this->assertStringContainsString('Updates and Deletes are not allowed', $e->getMessage());
        }
        $this->assertTrue($thrownUpdate, 'Trigger must prevent UPDATE on candidate revisions.');
        
        $thrownDelete = false;
        try {
            DB::table('evidence_candidate_revisions')->where('id', $revision->id)->delete();
        } catch (\Exception $e) {
            $thrownDelete = true;
            $this->assertStringContainsString('Updates and Deletes are not allowed', $e->getMessage());
        }
        $this->assertTrue($thrownDelete, 'Trigger must prevent DELETE on candidate revisions.');
    }

    public function test_migration_up_ambiguous_candidate_preflight()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        
        // Ambiguous candidates (same actor/semantic digest)
                DB::statement('ALTER TABLE evidence_candidates DROP CONSTRAINT IF EXISTS evidence_candidates_semantic_identity_unique');
        DB::statement('DROP INDEX IF EXISTS evidence_candidates_semantic_identity_unique');
        
        DB::table('evidence_candidates')->insert([
            ['id' => (string) Str::uuid7(), 'handoff_receipt_id' => $receiptId, 'subject_actor_id' => $actorId, 'submitted_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'capability_id' => (string) Str::uuid7(), 'evidence_claim' => 'claim', 'semantic_identity_digest' => 'digest1', 'state' => 'RECEIVED', 'proposed_title' => 'T', 'proposed_summary' => 'S', 'proposed_facts' => '{}', 'metadata' => '{}', 'created_at' => now(), 'updated_at' => now(), 'selected_material_refs' => '[]', 'criterion_scope' => '[]', 'governed_purpose' => 'test_purpose'],
            ['id' => (string) Str::uuid7(), 'handoff_receipt_id' => $receiptId, 'subject_actor_id' => $actorId, 'submitted_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'capability_id' => (string) Str::uuid7(), 'evidence_claim' => 'claim', 'semantic_identity_digest' => 'digest1', 'state' => 'RECEIVED', 'proposed_title' => 'T', 'proposed_summary' => 'S', 'proposed_facts' => '{}', 'metadata' => '{}', 'created_at' => now(), 'updated_at' => now(), 'selected_material_refs' => '[]', 'criterion_scope' => '[]', 'governed_purpose' => 'test_purpose'],
        ]);

        $migration = require database_path('migrations/2026_08_29_010500_implement_candidate_revisions_and_n1_admissions.php');
        
        $thrown = false;
        try {
            $migration->up();
        } catch (\Exception $e) {
            $thrown = true;
            $this->assertStringContainsString('ambiguous candidates', $e->getMessage());
        }
        $this->assertTrue($thrown);
    }
    
    public function test_migration_up_missing_admission_preflight()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        
                DB::statement('ALTER TABLE evidence_candidates DROP CONSTRAINT IF EXISTS evidence_candidates_semantic_identity_unique');
        DB::statement('DROP INDEX IF EXISTS evidence_candidates_semantic_identity_unique');
        
        $migration = require database_path('migrations/2026_08_29_010500_implement_candidate_revisions_and_n1_admissions.php');
        $migration->down();

        DB::table('evidence_candidates')->insert([
            'id' => (string) Str::uuid7(), 'handoff_receipt_id' => $receiptId, 'subject_actor_id' => $actorId, 'submitted_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'capability_id' => (string) Str::uuid7(), 'evidence_claim' => 'claim', 'semantic_identity_digest' => 'digest1', 'state' => 'ADMITTED', 'proposed_title' => 'T', 'proposed_summary' => 'S', 'proposed_facts' => '{}', 'metadata' => '{}', 'created_at' => now(), 'updated_at' => now(), 'selected_material_refs' => '[]', 'criterion_scope' => '[]', 'governed_purpose' => 'test_purpose'
        ]);
        
        $thrown = false;
        try {
            $migration->up();
        } catch (\Exception $e) {
            $thrown = true;
            $this->assertStringContainsString('resolves to 0 Admissions', $e->getMessage());
        }
        $this->assertTrue($thrown);
    }
    
    public function test_migration_down_duplicate_admission_preflight()
    {
        $actorId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        $evidenceId = (string) Str::uuid7();
        
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => (string) Str::uuid7(), 'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        
        $cId1 = (string) Str::uuid7();
        $cId2 = (string) Str::uuid7();
        
                DB::statement('ALTER TABLE evidence_candidates DROP CONSTRAINT IF EXISTS evidence_candidates_semantic_identity_unique');
        DB::statement('DROP INDEX IF EXISTS evidence_candidates_semantic_identity_unique');
        
        DB::table('evidence_candidates')->insert([
            ['id' => $cId1, 'handoff_receipt_id' => $receiptId, 'subject_actor_id' => $actorId, 'submitted_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test1', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'capability_id' => (string) Str::uuid7(), 'evidence_claim' => 'claim', 'semantic_identity_digest' => 'd1', 'state' => 'ADMITTED', 'proposed_title' => 'T', 'proposed_summary' => 'S', 'proposed_facts' => '{}', 'metadata' => '{}', 'created_at' => now(), 'updated_at' => now(), 'selected_material_refs' => '[]', 'criterion_scope' => '[]', 'governed_purpose' => 'test'],
            ['id' => $cId2, 'handoff_receipt_id' => $receiptId, 'subject_actor_id' => $actorId, 'submitted_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'test2', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'capability_id' => (string) Str::uuid7(), 'evidence_claim' => 'claim', 'semantic_identity_digest' => 'd2', 'state' => 'ADMITTED', 'proposed_title' => 'T', 'proposed_summary' => 'S', 'proposed_facts' => '{}', 'metadata' => '{}', 'created_at' => now(), 'updated_at' => now(), 'selected_material_refs' => '[]', 'criterion_scope' => '[]', 'governed_purpose' => 'test'],
        ]);
        
        DB::table('governed_evidence')->insert([
            'id' => $evidenceId, 'candidate_id' => $cId1, 'subject_actor_id' => $actorId, 'capability_id' => (string) Str::uuid7(), 'evidence_claim' => 'c', 'governed_purpose' => 'test', 'lifecycle_state' => 'ACTIVE', 'review_status' => 'UNREVIEWED', 'effective_review_decision' => 'NONE', 'current_revision_number' => 2, 'admitted_by' => $actorId, 'admitted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        
        $revId1 = (string) Str::uuid7();
        $revId2 = (string) Str::uuid7();
        
        DB::table('governed_evidence_revisions')->insert([
            ['id' => $revId1, 'evidence_id' => $evidenceId, 'revision' => 1, 'title' => 'T', 'summary' => 'S', 'facts' => '{}', 'selected_material_refs' => '[]', 'criterion_scope' => '[]', 'source_type' => 't', 'source_id' => 'i', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'handoff_receipt_id' => $receiptId, 'revision_reason' => 'INITIAL', 'content_digest' => str_repeat('a', 64), 'sealed_by' => $actorId, 'sealed_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['id' => $revId2, 'evidence_id' => $evidenceId, 'revision' => 2, 'title' => 'T', 'summary' => 'S', 'facts' => '{}', 'selected_material_refs' => '[]', 'criterion_scope' => '[]', 'source_type' => 't', 'source_id' => 'i', 'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'handoff_receipt_id' => $receiptId, 'revision_reason' => 'INITIAL', 'content_digest' => str_repeat('a', 64), 'sealed_by' => $actorId, 'sealed_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);
        
                $migration = require database_path('migrations/2026_08_29_010500_implement_candidate_revisions_and_n1_admissions.php');
        $migration->down();
        
        DB::statement('ALTER TABLE evidence_admission_records DISABLE TRIGGER evidence_admission_records_validate');
        DB::statement('ALTER TABLE evidence_admission_records DROP CONSTRAINT IF EXISTS evidence_admission_records_evidence_id_unique');
        DB::statement('DROP INDEX IF EXISTS evidence_admission_records_evidence_id_unique');
        
        DB::table('evidence_admission_records')->insert([
            ['id' => (string) Str::uuid7(), 'candidate_id' => $cId1, 'evidence_id' => $evidenceId, 'evidence_revision_id' => $revId1, 'admitted_by' => $actorId, 'admitted_at' => now(), 'provenance_digest' => str_repeat('p', 64), 'content_digest' => str_repeat('c', 64), 'created_at' => now()],
            ['id' => (string) Str::uuid7(), 'candidate_id' => $cId2, 'evidence_id' => $evidenceId, 'evidence_revision_id' => $revId2, 'admitted_by' => $actorId, 'admitted_at' => now(), 'provenance_digest' => str_repeat('p', 64), 'content_digest' => str_repeat('c', 64), 'created_at' => now()],
        ]);
        
        $migration->up();
        
        $thrown = false;
        try {
            $migration->down();
        } catch (\Exception $e) {
            $thrown = true;
            $this->assertStringContainsString('multiple admissions for the same evidence', $e->getMessage());
        }
        $this->assertTrue($thrown);
        
        // Assert schema/data is intact
        $this->assertTrue(Schema::hasTable('evidence_candidate_revisions'), 'New schema must remain intact after failed DOWN preflight.');
        
        // Clean up
        DB::statement('ALTER TABLE evidence_admission_candidate_revisions DISABLE TRIGGER trg_evidence_admission_candidate_revisions_immutable');
        DB::table('evidence_admission_candidate_revisions')->delete();
        DB::statement('ALTER TABLE evidence_admission_candidate_revisions ENABLE TRIGGER trg_evidence_admission_candidate_revisions_immutable');
        
        DB::statement('ALTER TABLE evidence_candidate_revisions DISABLE TRIGGER trg_evidence_candidate_revisions_immutable');
        DB::table('evidence_candidate_revisions')->delete();
        DB::statement('ALTER TABLE evidence_candidate_revisions ENABLE TRIGGER trg_evidence_candidate_revisions_immutable');
        
        DB::statement('ALTER TABLE evidence_admission_records DISABLE TRIGGER evidence_admission_records_immutable');
        DB::table('evidence_admission_records')->delete();
        DB::statement('ALTER TABLE evidence_admission_records ENABLE TRIGGER evidence_admission_records_immutable');
    }
}