<?php

namespace Tests\Integration;

use App\Modules\Evidence\Application\ProgressEvidenceService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceReviewService;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProgressEvidenceCorrectionDatabaseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function postgresql_installs_review_authority_scope_lineage_and_effective_projection_guards(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());

        $names = collect(DB::select(<<<'SQL'
SELECT tgname
  FROM pg_trigger
 WHERE NOT tgisinternal
   AND tgname IN (
       'evidence_reviews_assignment_validate',
       'evidence_review_requests_governance_validate',
       'evidence_review_findings_scope_validate',
       'evidence_review_decisions_authority_validate',
       'evidence_review_decisions_lineage_validate',
       'evidence_effective_decisions_validate'
   )
SQL))->pluck('tgname')->sort()->values()->all();

        $this->assertSame([
            'evidence_effective_decisions_validate',
            'evidence_review_decisions_authority_validate',
            'evidence_review_decisions_lineage_validate',
            'evidence_review_findings_scope_validate',
            'evidence_review_requests_governance_validate',
            'evidence_reviews_assignment_validate',
        ], $names);

        $constraint = DB::selectOne(<<<'SQL'
SELECT pg_get_constraintdef(oid) AS definition, convalidated
  FROM pg_constraint
 WHERE conname = 'evidence_portfolio_grouping_check'
SQL);
        $this->assertNotNull($constraint);
        $this->assertTrue((bool) $constraint->convalidated);
        $this->assertStringContainsString('CAPABILITY', (string) $constraint->definition);
        $this->assertStringNotContainsString('PROJECT', (string) $constraint->definition);
        $this->assertStringNotContainsString('OBJECTIVE', (string) $constraint->definition);
    }

    #[Test]
    public function postgresql_rejects_a_review_created_by_an_actor_other_than_the_assigned_reviewer(): void
    {
        $subject = $this->owner('db-assignment-subject');
        $reviewerId = (string) Str::uuid7();
        $admitted = $this->admit($subject, 'db-assignment');
        $request = app(EvidenceReviewService::class)->requestReview([[
            'evidence_id' => $admitted['evidence']['id'],
            'evidence_revision_id' => $admitted['revision']['id'],
        ]], $subject->id, 'SCOPE:DB-ASSIGNMENT', ['CRIT-DB'], 'Database reviewer authority.', $reviewerId);

        $this->expectException(QueryException::class);
        DB::table('evidence_reviews')->insert([
            'id' => (string) Str::uuid7(),
            'review_request_id' => $request['id'],
            'evidence_id' => $admitted['evidence']['id'],
            'evidence_revision_id' => $admitted['revision']['id'],
            'reviewer_id' => $subject->id,
            'review_scope_key' => 'SCOPE:DB-ASSIGNMENT',
            'criterion_refs' => json_encode(['CRIT-DB'], JSON_THROW_ON_ERROR),
            'status' => 'IN_REVIEW',
            'started_at' => now(),
            'completed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function postgresql_rejects_a_finding_outside_the_pinned_review_criterion_scope(): void
    {
        $owner = $this->owner('db-criterion');
        $admitted = $this->admit($owner, 'db-criterion');
        $reviews = app(EvidenceReviewService::class);
        $request = $reviews->requestReview([[
            'evidence_id' => $admitted['evidence']['id'],
            'evidence_revision_id' => $admitted['revision']['id'],
        ]], $owner->id, 'SCOPE:DB-CRITERION', ['CRIT-DB'], 'Database criterion scope.', $owner->id);
        $review = $reviews->startReview($request['id'], $owner->id);

        $this->expectException(QueryException::class);
        DB::table('evidence_review_findings')->insert([
            'id' => (string) Str::uuid7(),
            'review_id' => $review['id'],
            'criterion_key' => 'CRIT-OUTSIDE',
            'finding' => 'SATISFIED',
            'statement' => 'Direct storage bypass must still fail closed.',
            'supporting_evidence_revision_ids' => json_encode(
                [$admitted['revision']['id']],
                JSON_THROW_ON_ERROR,
            ),
            'recorded_by' => $owner->id,
            'recorded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array{candidate:array<string,mixed>,evidence:array<string,mixed>,revision:array<string,mixed>,admission:array<string,mixed>} */
    private function admit(OwnerAccount $owner, string $key): array
    {
        $receipt = app(ProgressEvidenceService::class)->registerSourceHandoffReceipt($owner->id, $owner->id, [
            'source_type' => 'ASSESSMENT_RESULT',
            'source_id' => "fixture:{$key}",
            'source_revision' => '1',
            'source_digest' => hash('sha256', "source:{$key}"),
            'selected_material_refs' => ["artifact:{$key}"],
            'capability_id' => 'CAP-DB-CORRECTION',
            'facts' => [['key' => 'fixture', 'value' => $key]],
            'metadata' => ['synthetic' => true],
        ]);
        $intake = app(EvidenceIntakeService::class);
        $candidate = $intake->receive($owner->id, $owner->id, [
            'handoff_receipt_id' => $receipt['id'],
            'evidence_claim' => "Database governed claim {$key}.",
            'criterion_scope' => ['CRIT-DB'],
            'governed_purpose' => 'FORMAL_CAPABILITY_EVIDENCE',
            'title' => "Database Evidence {$key}",
            'summary' => 'Synthetic PostgreSQL governance fixture.',
        ]);
        $intake->transitionCandidate($candidate['id'], $owner->id, 'PREPARED');
        $intake->transitionCandidate($candidate['id'], $owner->id, 'SUBMITTED_FOR_INTAKE');

        return $intake->admitCandidate($candidate['id'], $owner->id);
    }

    private function owner(string $key): OwnerAccount
    {
        return app(CreateOwner::class)->execute(
            "GOV database {$key}",
            "gov-database-{$key}@example.test",
            'GOV-Database!Pass9',
            (string) Str::uuid7(),
        );
    }
}
