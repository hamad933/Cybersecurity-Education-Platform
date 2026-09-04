<?php

namespace Tests\Integration\Evidence;

use App\Modules\Evidence\IntakeReview\Application\ProvenanceDigest;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvidenceIntakeCanonicalMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_legacy_backfill_uses_runtime_canonical_json_and_digest_contract(): void
    {
        $actorId = (string) Str::uuid7();
        $candidateId = (string) Str::uuid7();
        $receiptId = (string) Str::uuid7();
        $capabilityId = (string) Str::uuid7();
        $createdAt = now()->subDay();
        $migration = require database_path('migrations/2026_08_29_010500_implement_candidate_revisions_and_n1_admissions.php');
        $migration->down();

        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId,
            'source_type' => 'test_type', 'source_id' => 'legacy-source', 'source_revision' => '1',
            'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]', 'capability_id' => $capabilityId,
            'facts' => '{}', 'metadata' => '{}', 'receipt_digest' => str_repeat('b', 64),
            'registered_at' => $createdAt, 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
        DB::table('evidence_candidates')->insert([
            'id' => $candidateId, 'handoff_receipt_id' => $receiptId, 'subject_actor_id' => $actorId,
            'submitted_by' => $actorId, 'source_type' => 'test_type', 'source_id' => 'legacy-source',
            'source_revision' => '1', 'source_digest' => str_repeat('a', 64), 'selected_material_refs' => '[]',
            'capability_id' => $capabilityId, 'evidence_claim' => 'legacy claim', 'criterion_scope' => '[]',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION', 'semantic_identity_digest' => str_repeat('c', 64),
            'proposed_title' => 'Title', 'proposed_summary' => 'Summary',
            'proposed_facts' => '{"z":1,"a":{"b":2,"a":1},"list":[3,2,1]}', 'metadata' => '{"z":9,"a":1}',
            'state' => 'RECEIVED', 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);

        $migration->up();
        $revision = DB::table('evidence_candidate_revisions')->where('candidate_id', $candidateId)->firstOrFail();
        $digest = new ProvenanceDigest();
        $factsJson = $digest->canonicalJson(['z' => 1, 'a' => ['b' => 2, 'a' => 1], 'list' => [3, 2, 1]]);
        $metadataJson = $digest->canonicalJson(['z' => 9, 'a' => 1]);
        $expectedDigest = $digest->digest([
            'candidate_id' => $candidateId,
            'proposed_title' => 'Title',
            'proposed_summary' => 'Summary',
            'proposed_facts' => $factsJson,
            'metadata' => $metadataJson,
        ]);

        $this->assertSame('{"a":{"a":1,"b":2},"list":[3,2,1],"z":1}', $revision->proposed_facts);
        $this->assertSame('{"a":1,"z":9}', $revision->metadata);
        $this->assertSame($expectedDigest, $revision->content_digest);
    }
}
