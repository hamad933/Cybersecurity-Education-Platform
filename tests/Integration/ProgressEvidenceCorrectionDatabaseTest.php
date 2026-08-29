<?php

namespace Tests\Integration;

use App\Modules\Evidence\Application\ProgressEvidenceService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceReviewService;
use App\Modules\Evidence\IntakeReview\Application\ReviewDecisionService;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
        $this->assertStringContainsString('REVIEW_DECISION', (string) $constraint->definition);
        $this->assertStringNotContainsString('PROJECT', (string) $constraint->definition);
        $this->assertStringNotContainsString('OBJECTIVE', (string) $constraint->definition);
    }

    #[Test]
    public function postgresql_upgrade_preserves_legacy_portfolios_items_and_semantic_groupings(): void
    {
        $owner = $this->owner('portfolio-upgrade');
        $admitted = $this->admit($owner, 'portfolio-upgrade');
        $reviews = app(EvidenceReviewService::class);
        $request = $reviews->requestReview([[
            'evidence_id' => $admitted['evidence']['id'],
            'evidence_revision_id' => $admitted['revision']['id'],
        ]], $owner->id, 'SCOPE:PORTFOLIO-UPGRADE', ['CRIT-DB'], 'Legacy Portfolio upgrade Review.', $owner->id);
        $review = $reviews->startReview($request['id'], $owner->id);
        $reviews->recordFinding(
            $review['id'],
            $owner->id,
            'CRIT-DB',
            'SATISFIED',
            'The accepted Decision supplies a real Review Decision grouping value.',
            [$admitted['revision']['id']],
        );
        app(ReviewDecisionService::class)->recordDecision(
            $review['id'],
            $owner->id,
            'ACCEPT',
            'The exact Review scope is accepted for the migration upgrade fixture.',
        );

        $this->assertSame(0, Artisan::call('migrate:rollback', [
            '--step' => 1,
            '--force' => true,
            '--no-interaction' => true,
        ]));

        $masteryPortfolioId = (string) Str::uuid7();
        $reviewPortfolioId = (string) Str::uuid7();
        $now = now();
        DB::table('evidence_portfolios')->insert([[
            'id' => $masteryPortfolioId,
            'owner_actor_id' => $owner->id,
            'name' => 'Legacy Mastery Portfolio',
            'view_scope' => null,
            'grouping' => 'MASTERY',
            'filters' => json_encode([], JSON_THROW_ON_ERROR),
            'annotations' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            'id' => $reviewPortfolioId,
            'owner_actor_id' => $owner->id,
            'name' => 'Legacy Review Decision Portfolio',
            'view_scope' => null,
            'grouping' => 'REVIEW_DECISION',
            'filters' => json_encode([], JSON_THROW_ON_ERROR),
            'annotations' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ]]);
        DB::table('evidence_portfolio_items')->insert([[
            'id' => (string) Str::uuid7(),
            'portfolio_id' => $masteryPortfolioId,
            'evidence_id' => $admitted['evidence']['id'],
            'mastery_state_id' => null,
            'sort_order' => 0,
            'annotation' => 'Legacy Mastery item.',
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            'id' => (string) Str::uuid7(),
            'portfolio_id' => $reviewPortfolioId,
            'evidence_id' => $admitted['evidence']['id'],
            'mastery_state_id' => null,
            'sort_order' => 0,
            'annotation' => 'Legacy Review Decision item.',
            'created_at' => $now,
            'updated_at' => $now,
        ]]);

        $portfolioCount = DB::table('evidence_portfolios')
            ->whereIn('id', [$masteryPortfolioId, $reviewPortfolioId])
            ->count();
        $itemCount = DB::table('evidence_portfolio_items')
            ->whereIn('portfolio_id', [$masteryPortfolioId, $reviewPortfolioId])
            ->count();

        $this->assertSame(0, Artisan::call('migrate', [
            '--force' => true,
            '--no-interaction' => true,
        ]));

        $this->assertSame($portfolioCount, DB::table('evidence_portfolios')
            ->whereIn('id', [$masteryPortfolioId, $reviewPortfolioId])
            ->count());
        $this->assertSame($itemCount, DB::table('evidence_portfolio_items')
            ->whereIn('portfolio_id', [$masteryPortfolioId, $reviewPortfolioId])
            ->count());
        $this->assertDatabaseHas('evidence_portfolios', [
            'id' => $masteryPortfolioId,
            'grouping' => 'MASTERY_JUDGMENT',
        ]);
        $this->assertDatabaseHas('evidence_portfolios', [
            'id' => $reviewPortfolioId,
            'grouping' => 'REVIEW_DECISION',
        ]);

        $projection = app(ProgressEvidenceService::class)
            ->portfolioProjection($reviewPortfolioId, $owner->id);
        $this->assertSame('REVIEW_DECISION', $projection['grouping']);
        $this->assertCount(1, $projection['items']);
        $this->assertCount(1, $projection['groups']);
        $this->assertSame('ACCEPT', $projection['groups'][0]['key']);

        $constraint = DB::selectOne(<<<'SQL'
SELECT pg_get_constraintdef(oid) AS definition, convalidated
  FROM pg_constraint
 WHERE conname = 'evidence_portfolio_grouping_check'
SQL);
        $this->assertNotNull($constraint);
        $this->assertTrue((bool) $constraint->convalidated);
        foreach ([
            'CAPABILITY',
            'REVIEW_DECISION',
            'EVIDENCE_TYPE',
            'TIME',
            'MASTERY_JUDGMENT',
            'FRESHNESS_STATUS',
        ] as $grouping) {
            $this->assertStringContainsString("'{$grouping}'", (string) $constraint->definition);
        }
        $this->assertStringNotContainsString("'MASTERY'", (string) $constraint->definition);
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
