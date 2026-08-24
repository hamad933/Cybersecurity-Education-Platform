<?php

namespace Tests\Feature;

use App\Modules\Evidence\Application\ProgressEvidence\MasteryPortfolio\MasteryPortfolioService;
use App\Modules\Evidence\Application\ProgressEvidenceService;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProgressEvidenceMasteryPortfolioCompletionTest extends TestCase
{
    use RefreshDatabase;

    private OwnerAccount $owner;

    private ProgressEvidenceService $progress;

    private MasteryPortfolioService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = app(CreateOwner::class)->execute(
            'Mastery owner',
            'mastery-owner@example.test',
            'Mastery!Pass9',
            (string) Str::uuid7(),
        );
        $this->progress = app(ProgressEvidenceService::class);
        $this->service = app(MasteryPortfolioService::class);
    }

    #[Test]
    public function mastery_policy_is_versioned_and_only_the_current_revision_can_be_published(): void
    {
        $first = $this->service->createPolicy(
            $this->owner->id,
            'CAP-APPSEC-INPUT-VALIDATION',
            'Application Security Mastery',
            $this->policyRules(),
            'Initial governed policy for application-security capability judgment.',
        );

        $this->assertSame(1, (int) $first['revision']);
        $this->assertNull($first['published_at']);
        $publishedFirst = $this->service->publishPolicyRevision($first['id'], $this->owner->id);
        $this->assertNotNull($publishedFirst['published_at']);

        $second = $this->service->revisePolicy(
            $first['policy_id'],
            $this->owner->id,
            $this->policyRules(['recency_days' => 180]),
            'Shorten the evidence recency window without rewriting Revision 1.',
        );
        $this->assertSame(2, (int) $second['revision']);
        $this->assertSame($first['id'], $second['previous_revision_id']);
        $this->assertNull($second['published_at']);

        $publishedSecond = $this->service->publishPolicyRevision($second['id'], $this->owner->id);
        $this->assertNotNull($publishedSecond['published_at']);
        $this->assertDatabaseHas('evidence_mastery_policies', [
            'id' => $first['policy_id'],
            'current_revision_number' => 2,
            'published_revision_id' => $second['id'],
        ]);
        $this->assertDatabaseCount('evidence_mastery_policy_revisions', 2);
    }

    #[Test]
    public function published_mastery_policy_revision_is_database_immutable(): void
    {
        $policy = $this->publishedPolicy();

        $this->expectException(QueryException::class);
        DB::table('evidence_mastery_policy_revisions')
            ->where('id', $policy['id'])
            ->update(['rationale' => 'destructive published-policy rewrite']);
    }

    #[Test]
    public function mastery_evaluation_requires_published_policy_and_revalidation_preserves_judgment_history(): void
    {
        $reviewed = $this->reviewedEvidence('ACCEPT');
        $draft = $this->service->createPolicy(
            $this->owner->id,
            $reviewed['evidence']['capability_id'],
            'Input Validation Mastery',
            $this->policyRules(),
            'Draft policy cannot govern a Mastery State until publication.',
        );

        $this->assertLogicRejected(
            fn () => $this->service->evaluate(
                $this->owner->id,
                $this->owner->id,
                $draft['id'],
                'MASTERED',
                'CURRENT',
                [$reviewed['decision']['id']],
                [$reviewed['revision']['id']],
                [],
                'Draft policies are not valid provenance.',
            ),
            'published Mastery Policy Revision',
        );

        $published = $this->service->publishPolicyRevision($draft['id'], $this->owner->id);
        $first = $this->service->evaluate(
            $this->owner->id,
            $this->owner->id,
            $published['id'],
            'MASTERED',
            'CURRENT',
            [$reviewed['decision']['id']],
            [$reviewed['revision']['id']],
            [],
            'Current accepted Evidence satisfies the published policy.',
        );

        $revisionTwo = $this->service->revisePolicy(
            $published['policy_id'],
            $this->owner->id,
            $this->policyRules(['recency_days' => 90]),
            'Policy Revision 2 requires explicit revalidation without removing the competency judgment.',
        );
        $revisionTwo = $this->service->publishPolicyRevision($revisionTwo['id'], $this->owner->id);
        $revalidation = $this->service->markRevalidationRequired(
            $this->owner->id,
            $this->owner->id,
            $revisionTwo['id'],
            'Published policy changed; preserve MASTERED and require freshness revalidation.',
        );

        $this->assertSame('MASTERED', $revalidation['judgment']);
        $this->assertSame('REVALIDATION_REQUIRED', $revalidation['freshness_status']);
        $this->assertSame($first['id'], $revalidation['previous_state_id']);
        $this->assertSame($revisionTwo['id'], $revalidation['policy_revision_id']);

        $history = $this->service->masteryHistory($this->owner->id, $reviewed['evidence']['capability_id']);
        $this->assertCount(2, $history);
        $this->assertSame($revalidation['id'], $history[0]['id']);
        $this->assertSame([$reviewed['decision']['id']], $history[0]['review_decision_ids']);
        $this->assertSame([$reviewed['revision']['id']], $history[0]['supporting_evidence_revision_ids']);
    }

    #[Test]
    public function conflicting_evidence_requires_inconclusive_judgment_instead_of_latest_wins(): void
    {
        $supporting = $this->reviewedEvidence('ACCEPT');
        $contradicting = $this->reviewedEvidence('REJECT', [
            'source_id' => 'fixture:contradicting:'.Str::lower(Str::random(10)),
            'source_digest' => hash('sha256', 'contradicting-'.Str::random(32)),
            'selected_material_refs' => ['artifact:contradicting'],
            'evidence_claim' => 'A separate controlled attempt contradicts the claimed input-validation capability.',
            'title' => 'Contradicting governed evidence',
        ]);
        $policy = $this->publishedPolicy();

        $this->assertLogicRejected(
            fn () => $this->service->evaluate(
                $this->owner->id,
                $this->owner->id,
                $policy['id'],
                'MASTERED',
                'CURRENT',
                [$supporting['decision']['id'], $contradicting['decision']['id']],
                [$supporting['revision']['id']],
                [$contradicting['revision']['id']],
                'A conflict cannot be hidden by choosing the newest record.',
            ),
            'INCONCLUSIVE Mastery Judgment',
        );

        $state = $this->service->evaluate(
            $this->owner->id,
            $this->owner->id,
            $policy['id'],
            'INCONCLUSIVE',
            'CURRENT',
            [$supporting['decision']['id'], $contradicting['decision']['id']],
            [$supporting['revision']['id']],
            [$contradicting['revision']['id']],
            'Conflicting effective decisions remain explicit until governed revalidation resolves them.',
        );

        $this->assertSame('INCONCLUSIVE', $state['judgment']);
        $this->assertDatabaseHas('evidence_mastery_state_evidence', [
            'mastery_state_id' => $state['id'],
            'evidence_revision_id' => $contradicting['revision']['id'],
            'contribution' => 'CONTRADICTING',
        ]);
    }

    #[Test]
    public function mastered_judgment_enforces_policy_criteria_decisions_and_evidence_diversity(): void
    {
        $first = $this->reviewedEvidence('ACCEPT');
        $policy = $this->publishedPolicy([
            'evidence_diversity' => ['min_distinct_evidence' => 2],
        ]);

        $this->assertLogicRejected(
            fn () => $this->service->evaluate(
                $this->owner->id,
                $this->owner->id,
                $policy['id'],
                'MASTERED',
                'CURRENT',
                [$first['decision']['id']],
                [$first['revision']['id']],
                [],
                'One Evidence record is below the published diversity threshold.',
            ),
            'evidence-diversity requirement',
        );

        $second = $this->reviewedEvidence('ACCEPT_WITH_LIMITATIONS', [
            'source_id' => 'fixture:diverse:'.Str::lower(Str::random(10)),
            'source_digest' => hash('sha256', 'diverse-'.Str::random(32)),
            'selected_material_refs' => ['artifact:diverse'],
            'evidence_claim' => 'A distinct controlled result independently supports the same capability.',
            'title' => 'Independent governed evidence',
        ]);

        $state = $this->service->evaluate(
            $this->owner->id,
            $this->owner->id,
            $policy['id'],
            'MASTERED',
            'CURRENT',
            [$first['decision']['id'], $second['decision']['id']],
            [$first['revision']['id'], $second['revision']['id']],
            [],
            'Two distinct accepted Evidence records satisfy the published policy.',
        );

        $this->assertSame('MASTERED', $state['judgment']);
        $this->assertSame('CURRENT', $state['freshness_status']);
        $this->assertDatabaseCount('evidence_mastery_state_evidence', 2);
    }

    #[Test]
    public function portfolio_curates_only_accepted_canonical_evidence_and_removal_never_changes_canonical_truth(): void
    {
        $accepted = $this->reviewedEvidence('ACCEPT');
        $policy = $this->publishedPolicy();
        $state = $this->service->evaluate(
            $this->owner->id,
            $this->owner->id,
            $policy['id'],
            'MASTERED',
            'CURRENT',
            [$accepted['decision']['id']],
            [$accepted['revision']['id']],
            [],
            'Accepted Evidence supports the curated Portfolio projection.',
        );
        $unreviewed = $this->admittedEvidence([
            'source_id' => 'fixture:unreviewed:'.Str::lower(Str::random(10)),
            'source_digest' => hash('sha256', 'unreviewed-'.Str::random(32)),
            'title' => 'Unreviewed evidence is not portfolio-ready',
        ]);

        $portfolio = $this->service->createPortfolio(
            $this->owner->id,
            'Application Security Portfolio',
            'Professional evidence',
            'CAPABILITY',
            ['review_decision' => ['ACCEPT', 'ACCEPT_WITH_LIMITATIONS']],
            ['purpose' => 'curated-projection'],
        );
        $this->service->addAcceptedEvidenceToPortfolio(
            $portfolio['id'],
            $accepted['evidence']['id'],
            $this->owner->id,
            'Canonical accepted Evidence reference.',
            10,
        );

        $this->assertLogicRejected(
            fn () => $this->service->addAcceptedEvidenceToPortfolio(
                $portfolio['id'],
                $unreviewed['evidence']['id'],
                $this->owner->id,
            ),
            'effective accepted Review Decision',
        );

        $projection = $this->service->portfolioProjection($portfolio['id'], $this->owner->id);
        $this->assertCount(1, $projection['items']);
        $this->assertSame($accepted['evidence']['id'], $projection['items'][0]['evidence_id']);
        $this->assertSame($accepted['revision']['id'], $projection['items'][0]['evidence_revision_id']);
        $this->assertSame($state['id'], $projection['items'][0]['mastery_state_id']);
        $this->assertArrayNotHasKey('candidate_id', $projection['items'][0]);
        $this->assertFalse(Schema::hasColumn('evidence_portfolio_items', 'facts'));
        $this->assertFalse(Schema::hasColumn('evidence_portfolio_items', 'evidence_claim'));

        $this->service->removeEvidenceFromPortfolio(
            $portfolio['id'],
            $accepted['evidence']['id'],
            $this->owner->id,
        );
        $this->assertDatabaseCount('evidence_portfolio_items', 0);
        $this->assertDatabaseHas('governed_evidence', ['id' => $accepted['evidence']['id']]);
        $this->assertDatabaseHas('evidence_mastery_states', ['id' => $state['id']]);
        $this->assertDatabaseHas('evidence_candidates', ['admitted_evidence_id' => $accepted['evidence']['id']]);
    }

    /** @return array<string, mixed> */
    private function policyRules(array $overrides = []): array
    {
        return [
            'required_criteria' => ['CRIT-INPUT-VALIDATION'],
            'qualifying_review_decisions' => ['ACCEPT', 'ACCEPT_WITH_LIMITATIONS'],
            'evidence_diversity' => ['min_distinct_evidence' => 1],
            'minimum_attribution_confidence' => null,
            'conflict_handling' => 'REQUIRE_INCONCLUSIVE',
            'permitted_limitations' => ['DOCUMENTED_LIMITATION'],
            'recency_days' => 365,
            'freshness_triggers' => ['POLICY_REVISION', 'EVIDENCE_SUPERSESSION'],
            'revalidation_conditions' => ['POLICY_REVISION', 'RECENCY_EXPIRED', 'DECISION_SUPERSEDED'],
            ...$overrides,
        ];
    }

    /** @return array<string, mixed> */
    private function publishedPolicy(array $overrides = []): array
    {
        $draft = $this->service->createPolicy(
            $this->owner->id,
            'CAP-APPSEC-INPUT-VALIDATION',
            'Application Security Mastery '.Str::lower(Str::random(6)),
            $this->policyRules($overrides),
            'Governed Mastery Policy fixture.',
        );

        return $this->service->publishPolicyRevision($draft['id'], $this->owner->id);
    }

    /** @return array<string, mixed> */
    private function handoff(array $overrides = []): array
    {
        $handoff = [
            'source_type' => 'SYNTHETIC_TEST_HANDOFF',
            'source_id' => 'fixture:mastery:'.Str::lower(Str::random(12)),
            'source_revision' => '1',
            'source_digest' => hash('sha256', Str::random(64)),
            'selected_material_refs' => ['artifact:fixture:primary'],
            'capability_id' => 'CAP-APPSEC-INPUT-VALIDATION',
            'facts' => [
                'environment' => 'isolated-test-fixture',
                'attribution_confidence' => 0.95,
            ],
            'metadata' => ['fixture' => true],
            ...$overrides,
        ];
        $ownerId = $this->owner->id;
        $receipt = app(ProgressEvidenceService::class)
            ->registerSourceHandoffReceipt($ownerId, $ownerId, $handoff);

        return [
            'handoff_receipt_id' => $receipt['id'],
            'evidence_claim' => $overrides['evidence_claim'] ?? 'The learner demonstrated governed input-validation capability.',
            'criterion_scope' => $overrides['criterion_scope'] ?? ['CRIT-INPUT-VALIDATION'],
            'governed_purpose' => $overrides['governed_purpose'] ?? 'FORMAL_CAPABILITY_EVIDENCE',
            'title' => $overrides['title'] ?? 'Governed mastery evidence',
            'summary' => $overrides['summary'] ?? 'Synthetic persisted fixture for Mastery and Portfolio completion tests.',
        ];
    }

    /** @return array{evidence: array<string, mixed>, revision: array<string, mixed>} */
    private function admittedEvidence(array $overrides = []): array
    {
        $candidate = $this->progress->intakeCandidate(
            $this->owner->id,
            $this->owner->id,
            $this->handoff($overrides),
        );
        $candidate = $this->progress->transitionCandidate($candidate['id'], $this->owner->id, 'PREPARED');
        $candidate = $this->progress->transitionCandidate($candidate['id'], $this->owner->id, 'SUBMITTED_FOR_INTAKE');

        return $this->progress->admitCandidate($candidate['id'], $this->owner->id);
    }

    /** @return array{evidence: array<string, mixed>, revision: array<string, mixed>, decision: array<string, mixed>} */
    private function reviewedEvidence(string $decision, array $overrides = []): array
    {
        $bundle = $this->admittedEvidence($overrides);
        $request = $this->progress->requestReview($bundle['evidence']['id'], $this->owner->id);
        $review = $this->progress->admitReviewRequest($request['id'], $this->owner->id);
        $finding = in_array($decision, ['ACCEPT', 'ACCEPT_WITH_LIMITATIONS'], true)
            ? 'SATISFIED'
            : 'NOT_SATISFIED';
        $this->progress->recordFinding(
            $review['id'],
            $this->owner->id,
            'CRIT-INPUT-VALIDATION',
            $finding,
            'Synthetic governed finding over the pinned Evidence Revision.',
        );
        $reviewDecision = $this->progress->recordReviewDecision(
            $review['id'],
            $this->owner->id,
            $decision,
            'Synthetic governed Review Decision for Mastery/Portfolio completion.',
        );

        return [
            'evidence' => $bundle['evidence'],
            'revision' => $bundle['revision'],
            'decision' => $reviewDecision,
        ];
    }

    private function assertLogicRejected(callable $callback, string $messageFragment): void
    {
        try {
            $callback();
            $this->fail('Expected a LogicException.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString($messageFragment, $exception->getMessage());
        }
    }
}
