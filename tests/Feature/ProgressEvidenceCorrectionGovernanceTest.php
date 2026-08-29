<?php

namespace Tests\Feature;

use App\Application\ProgressEvidenceBridge\ProgressEvidenceResultIntakePort;
use App\Application\ProgressEvidenceBridge\ResultEvidenceHandoff;
use App\Modules\Evidence\Application\ProgressEvidence\MasteryPortfolio\PortfolioGroupingRegistry;
use App\Modules\Evidence\Application\ProgressEvidenceService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceReviewService;
use App\Modules\Evidence\IntakeReview\Application\ReviewDecisionService;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProgressEvidenceCorrectionGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private int $ownerSequence = 0;

    #[Test]
    public function assigned_reviewer_authority_and_complete_pinned_criterion_findings_are_required(): void
    {
        $subject = $this->owner('subject');
        $reviewerId = (string) Str::uuid7();
        $admitted = $this->admit($subject, 'assignment-completeness', ['CRIT-A', 'CRIT-B']);
        $reviews = app(EvidenceReviewService::class);

        $request = $reviews->requestReview([[
            'evidence_id' => $admitted['evidence']['id'],
            'evidence_revision_id' => $admitted['revision']['id'],
        ]], $subject->id, 'CAPABILITY:ASSIGNMENT', ['CRIT-A', 'CRIT-B'], 'Formal assigned review.', $reviewerId);

        $this->assertSame('ASSIGNED', $request['status']);
        $this->assertSame($reviewerId, $request['assigned_reviewer_id']);
        $this->assertIntakeRejected(
            fn () => $reviews->startReview($request['id'], $subject->id),
            'not the assigned Evidence reviewer',
        );

        $review = $reviews->startReview($request['id'], $reviewerId);
        $this->assertSame($reviewerId, $review['reviewer_id']);
        $this->assertIntakeRejected(
            fn () => $reviews->recordFinding(
                $review['id'],
                $reviewerId,
                'CRIT-OUTSIDE',
                'SATISFIED',
                'This criterion was not pinned by the governed Review Request.',
                [$admitted['revision']['id']],
            ),
            'outside the pinned Review criterion scope',
        );

        $reviews->recordFinding(
            $review['id'],
            $reviewerId,
            'CRIT-A',
            'SATISFIED',
            'The first governed criterion is satisfied.',
            [$admitted['revision']['id']],
        );
        $this->assertDatabaseHas('evidence_reviews', ['id' => $review['id'], 'status' => 'IN_REVIEW']);
        $this->assertIntakeRejected(
            fn () => app(ReviewDecisionService::class)->recordDecision(
                $review['id'],
                $reviewerId,
                'ACCEPT',
                'An incomplete finding set cannot support this Decision.',
            ),
            'must have recorded Findings',
        );

        $reviews->recordFinding(
            $review['id'],
            $reviewerId,
            'CRIT-B',
            'SATISFIED',
            'The second governed criterion is satisfied.',
            [$admitted['revision']['id']],
        );
        $decision = app(ReviewDecisionService::class)->recordDecision(
            $review['id'],
            $reviewerId,
            'ACCEPT',
            'Every pinned criterion now has an attributable formal Finding.',
        );

        $this->assertDatabaseHas('evidence_review_decisions', [
            'id' => $decision['id'],
            'decided_by' => $reviewerId,
        ]);
        $this->assertDatabaseHas('evidence_effective_review_decisions', [
            'evidence_id' => $admitted['evidence']['id'],
            'review_scope_key' => 'CAPABILITY:ASSIGNMENT',
            'decision_id' => $decision['id'],
            'decision' => 'ACCEPT',
        ]);
    }

    #[Test]
    public function effective_decisions_remain_scope_sensitive_and_rereview_supersedes_only_the_pinned_lineage_tip(): void
    {
        $owner = $this->owner('scope');
        $admitted = $this->admit($owner, 'scope-lineage', ['CRIT-SCOPE']);
        $scopeA = $this->review($owner, $owner, $admitted, 'SCOPE:A', 'ACCEPT');
        $scopeB = $this->review($owner, $owner, $admitted, 'SCOPE:B', 'REJECT');

        $this->assertDatabaseCount('evidence_effective_review_decisions', 2);
        $this->assertDatabaseHas('evidence_effective_review_decisions', [
            'evidence_id' => $admitted['evidence']['id'],
            'review_scope_key' => 'SCOPE:A',
            'decision_id' => $scopeA['decision']['id'],
            'decision' => 'ACCEPT',
        ]);
        $this->assertDatabaseHas('evidence_effective_review_decisions', [
            'evidence_id' => $admitted['evidence']['id'],
            'review_scope_key' => 'SCOPE:B',
            'decision_id' => $scopeB['decision']['id'],
            'decision' => 'REJECT',
        ]);

        $reviews = app(EvidenceReviewService::class);
        $request = $reviews->requestReview([[
            'evidence_id' => $admitted['evidence']['id'],
            'evidence_revision_id' => $admitted['revision']['id'],
        ]], $owner->id, 'SCOPE:A', ['CRIT-SCOPE'], 'Re-review exact scope A.', $owner->id);
        $this->assertSame($scopeA['decision']['id'], $request['prior_decision_id']);

        $review = $reviews->startReview($request['id'], $owner->id);
        $reviews->recordFinding(
            $review['id'],
            $owner->id,
            'CRIT-SCOPE',
            'PARTIALLY_SATISFIED',
            'Re-review records the current limitation without rewriting prior truth.',
            [$admitted['revision']['id']],
        );
        $this->assertIntakeRejected(
            fn () => app(ReviewDecisionService::class)->recordDecision(
                $review['id'],
                $owner->id,
                'ACCEPT_WITH_LIMITATIONS',
                'The wrong scope Decision cannot be selected as the supersession parent.',
                $scopeB['decision']['id'],
            ),
            'prior Decision pinned by the Review Request',
        );

        $replacement = app(ReviewDecisionService::class)->recordDecision(
            $review['id'],
            $owner->id,
            'ACCEPT_WITH_LIMITATIONS',
            'The new immutable Decision supersedes only the prior scope A lineage tip.',
        );

        $this->assertSame($scopeA['decision']['id'], $replacement['supersedes_decision_id']);
        $this->assertDatabaseHas('evidence_review_decisions', [
            'id' => $scopeA['decision']['id'],
            'decision' => 'ACCEPT',
            'supersedes_decision_id' => null,
        ]);
        $this->assertDatabaseHas('evidence_effective_review_decisions', [
            'evidence_id' => $admitted['evidence']['id'],
            'review_scope_key' => 'SCOPE:A',
            'decision_id' => $replacement['id'],
            'decision' => 'ACCEPT_WITH_LIMITATIONS',
        ]);
        $this->assertDatabaseHas('evidence_effective_review_decisions', [
            'evidence_id' => $admitted['evidence']['id'],
            'review_scope_key' => 'SCOPE:B',
            'decision_id' => $scopeB['decision']['id'],
            'decision' => 'REJECT',
        ]);
    }

    #[Test]
    public function result_handoff_receipt_is_integrity_checked_idempotent_and_stops_before_evidence_or_mastery(): void
    {
        $owner = $this->owner('bridge');
        $handoff = new ResultEvidenceHandoff(
            subjectActorId: $owner->id,
            deliveredBy: $owner->id,
            sourceResultId: (string) Str::uuid7(),
            sourceResultRevision: 1,
            sourceResultDigest: hash('sha256', 'immutable-result'),
            manifestDigest: hash('sha256', 'immutable-result-manifest'),
            artifactRefs: ['simulation://operations/immutable-result'],
            capabilityId: 'CAP-BRIDGE-AUTHORIZED',
            evidenceClaim: 'The sealed Result supports a governed capability claim.',
            criterionScope: ['CRIT-BRIDGE'],
            title: 'Candidate from sealed Result',
            summary: 'W04 receives immutable primitive Result provenance through its neutral intake port.',
            provenance: 'SIMULATED',
            sourceFixture: true,
            sourceHandoffId: (string) Str::uuid7(),
        );

        $first = app(ProgressEvidenceResultIntakePort::class)->receive($handoff);
        $second = app(ProgressEvidenceResultIntakePort::class)->receive($handoff);

        $this->assertSame($first['receipt']['id'], $second['receipt']['id']);
        $this->assertSame($first['candidate']['id'], $second['candidate']['id']);
        $this->assertSame('RECEIVED', $first['candidate']['state']);
        $this->assertDatabaseCount('evidence_source_handoff_receipts', 1);
        $this->assertDatabaseCount('evidence_candidates', 1);
        $this->assertDatabaseCount('governed_evidence', 0);
        $this->assertDatabaseCount('evidence_review_decisions', 0);
        $this->assertDatabaseCount('evidence_mastery_states', 0);

        $conflict = new ResultEvidenceHandoff(
            subjectActorId: $handoff->subjectActorId,
            deliveredBy: $handoff->deliveredBy,
            sourceResultId: $handoff->sourceResultId,
            sourceResultRevision: $handoff->sourceResultRevision,
            sourceResultDigest: hash('sha256', 'conflicting-result-bytes'),
            manifestDigest: hash('sha256', 'conflicting-manifest'),
            artifactRefs: $handoff->artifactRefs,
            capabilityId: $handoff->capabilityId,
            evidenceClaim: $handoff->evidenceClaim,
            criterionScope: $handoff->criterionScope,
            title: $handoff->title,
            summary: $handoff->summary,
            provenance: $handoff->provenance,
            sourceFixture: $handoff->sourceFixture,
            sourceHandoffId: $handoff->sourceHandoffId,
        );
        $this->assertLogicRejected(
            fn () => app(ProgressEvidenceResultIntakePort::class)->receive($conflict),
            'conflicts with an immutable source integrity digest',
        );
    }

    #[Test]
    public function portfolio_filters_change_only_projection_membership_and_support_a_valid_zero_result_state(): void
    {
        $owner = $this->owner('portfolio-filter');
        $accepted = $this->admit($owner, 'portfolio-accepted', ['CRIT-PORTFOLIO'], 'CAP-PORTFOLIO');
        $this->review($owner, $owner, $accepted, 'PORTFOLIO:ACCEPTANCE', 'ACCEPT');
        $unreviewed = $this->admit($owner, 'portfolio-unreviewed', ['CRIT-PORTFOLIO'], 'CAP-PORTFOLIO');
        $service = app(ProgressEvidenceService::class);

        $zero = $service->createPortfolio(
            $owner->id,
            'Zero-result governed view',
            null,
            'CAPABILITY',
            [
                'lifecycle_states' => ['ACTIVE'],
                'review_decisions' => ['REJECT'],
                'capability_ids' => ['CAP-PORTFOLIO'],
            ],
        );
        $service->addEvidenceToPortfolio($zero['id'], $accepted['evidence']['id'], $owner->id);
        $zeroProjection = $service->portfolioProjection($zero['id'], $owner->id);

        $this->assertSame([], $zeroProjection['items']);
        $this->assertSame([], $zeroProjection['groups']);
        $this->assertDatabaseHas('evidence_portfolio_items', [
            'portfolio_id' => $zero['id'],
            'evidence_id' => $accepted['evidence']['id'],
        ]);
        $this->assertLogicRejected(
            fn () => $service->addEvidenceToPortfolio(
                $zero['id'],
                $unreviewed['evidence']['id'],
                $owner->id,
            ),
            'effective accepted Review Decision',
        );

        $matching = $service->createPortfolio(
            $owner->id,
            'Matching governed view',
            null,
            'CAPABILITY',
            [
                'lifecycle_states' => ['ACTIVE'],
                'review_decisions' => ['ACCEPT'],
                'capability_ids' => ['CAP-PORTFOLIO'],
            ],
        );
        $service->addEvidenceToPortfolio($matching['id'], $accepted['evidence']['id'], $owner->id);
        $projection = $service->portfolioProjection($matching['id'], $owner->id);

        $this->assertCount(1, $projection['items']);
        $this->assertCount(1, $projection['groups']);
        $this->assertSame('CAP-PORTFOLIO', $projection['groups'][0]['key']);
        $this->assertSame('ACCEPT', $projection['items'][0]['effective_review_decision']);

        $service->removeEvidenceFromPortfolio($matching['id'], $accepted['evidence']['id'], $owner->id);
        $this->assertDatabaseMissing('evidence_portfolio_items', [
            'portfolio_id' => $matching['id'],
            'evidence_id' => $accepted['evidence']['id'],
        ]);
        $this->assertDatabaseHas('governed_evidence', ['id' => $accepted['evidence']['id']]);
        $this->assertDatabaseCount('evidence_mastery_states', 0);
    }

    #[Test]
    public function authoritative_portfolio_groupings_round_trip_and_every_exposed_strategy_partitions_its_field(): void
    {
        $registry = app(PortfolioGroupingRegistry::class);
        $this->assertSame([
            'CAPABILITY',
            'REVIEW_DECISION',
            'EVIDENCE_TYPE',
            'TIME',
            'MASTERY_JUDGMENT',
            'FRESHNESS_STATUS',
        ], $registry->keys());

        $items = [[
            'capability_id' => 'CAP-A',
            'effective_review_decision' => 'ACCEPT',
            'source_type' => 'SIMULATION_RUN_RESULT',
            'sealed_at' => '2026-08-28T10:00:00+00:00',
            'mastery_judgment' => 'MASTERED',
            'freshness_status' => 'CURRENT',
        ], [
            'capability_id' => 'CAP-B',
            'effective_review_decision' => 'REJECT',
            'source_type' => 'ASSESSMENT_RESULT',
            'sealed_at' => '2026-08-29T10:00:00+00:00',
            'mastery_judgment' => 'INCONCLUSIVE',
            'freshness_status' => 'REVALIDATION_REQUIRED',
        ]];
        foreach ($registry->keys() as $grouping) {
            $groups = $registry->groups($grouping, $items);
            $this->assertCount(2, $groups, "{$grouping} must create real semantic partitions.");
            $this->assertSame($grouping, $groups[0]['grouping']);
        }

        $owner = $this->owner('portfolio-group');
        $service = app(ProgressEvidenceService::class);
        foreach ($registry->keys() as $grouping) {
            $portfolio = $service->createPortfolio(
                $owner->id,
                "Round-trip {$grouping}",
                null,
                $grouping,
            );
            $projection = $service->portfolioProjection($portfolio['id'], $owner->id);
            $this->assertSame($grouping, $projection['grouping']);
            $this->assertSame([], $projection['groups']);
        }

        $this->assertInvalidArgumentRejected(
            fn () => $service->createPortfolio($owner->id, 'Unsupported Project grouping', null, 'PROJECT'),
            'Unsupported governed Portfolio grouping',
        );
        $this->assertInvalidArgumentRejected(
            fn () => $service->createPortfolio($owner->id, 'Unsupported Objective grouping', null, 'OBJECTIVE'),
            'Unsupported governed Portfolio grouping',
        );
    }

    #[Test]
    public function legacy_evidence_and_mastery_mutation_routes_are_gone_and_leave_all_truth_unchanged(): void
    {
        $owner = $this->owner('legacy');
        $before = $this->legacyTruthCounts();
        $evidenceId = (string) Str::uuid7();

        foreach ([
            "/vs001/evidence/{$evidenceId}/decision",
            '/vs001/mastery/evaluate',
            "/vs002/evidence/{$evidenceId}/decision",
            '/vs002/mastery/evaluate',
            '/vs003/evidence/preserve',
            '/vs003/mastery/evaluate',
        ] as $uri) {
            $this->actingAs($owner)->post($uri)->assertStatus(410);
        }

        $this->assertSame($before, $this->legacyTruthCounts());
    }

    /** @param list<string> $criteria @return array{candidate:array<string,mixed>,evidence:array<string,mixed>,revision:array<string,mixed>,admission:array<string,mixed>} */
    private function admit(
        OwnerAccount $owner,
        string $key,
        array $criteria,
        string $capability = 'CAP-CORRECTION',
    ): array {
        $receipt = app(ProgressEvidenceService::class)->registerSourceHandoffReceipt($owner->id, $owner->id, [
            'source_type' => 'ASSESSMENT_RESULT',
            'source_id' => "fixture:{$key}",
            'source_revision' => '1',
            'source_digest' => hash('sha256', "source:{$key}"),
            'selected_material_refs' => ["artifact:{$key}"],
            'capability_id' => $capability,
            'facts' => [['key' => 'fixture', 'value' => $key]],
            'metadata' => ['synthetic' => true],
        ]);
        $intake = app(EvidenceIntakeService::class);
        $candidate = $intake->receive($owner->id, $owner->id, [
            'handoff_receipt_id' => $receipt['id'],
            'evidence_claim' => "Governed claim {$key}.",
            'criterion_scope' => $criteria,
            'governed_purpose' => 'FORMAL_CAPABILITY_EVIDENCE',
            'title' => "Evidence {$key}",
            'summary' => 'Synthetic governed correction fixture.',
        ]);
        $intake->transitionCandidate($candidate['id'], $owner->id, 'PREPARED');
        $intake->transitionCandidate($candidate['id'], $owner->id, 'SUBMITTED_FOR_INTAKE');

        return $intake->admitCandidate($candidate['id'], $owner->id);
    }

    /**
     * @param  array{evidence:array<string,mixed>,revision:array<string,mixed>}  $admitted
     * @return array{request:array<string,mixed>,review:array<string,mixed>,decision:array<string,mixed>}
     */
    private function review(
        OwnerAccount $subject,
        OwnerAccount $reviewer,
        array $admitted,
        string $scope,
        string $outcome,
    ): array {
        $reviews = app(EvidenceReviewService::class);
        $criterionScope = is_array($admitted['revision']['criterion_scope'])
            ? $admitted['revision']['criterion_scope']
            : json_decode(
                (string) $admitted['revision']['criterion_scope'],
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        $criterion = (string) ($criterionScope[0] ?? '');
        $request = $reviews->requestReview([[
            'evidence_id' => $admitted['evidence']['id'],
            'evidence_revision_id' => $admitted['revision']['id'],
        ]], $subject->id, $scope, [$criterion], 'Formal correction Review.', $reviewer->id);
        $review = $reviews->startReview($request['id'], $reviewer->id);
        $reviews->recordFinding(
            $review['id'],
            $reviewer->id,
            $criterion,
            in_array($outcome, ['ACCEPT', 'ACCEPT_WITH_LIMITATIONS'], true) ? 'SATISFIED' : 'NOT_SATISFIED',
            'The assigned reviewer records the complete exact-scope Finding.',
            [$admitted['revision']['id']],
        );
        $decision = app(ReviewDecisionService::class)->recordDecision(
            $review['id'],
            $reviewer->id,
            $outcome,
            'The immutable Decision applies only to this exact Review scope.',
        );

        return compact('request', 'review', 'decision');
    }

    private function owner(string $key): OwnerAccount
    {
        $this->ownerSequence++;

        return app(CreateOwner::class)->execute(
            "GOV correction {$key}",
            "gov-correction-{$key}-{$this->ownerSequence}@example.test",
            'GOV-Correction!Pass9',
            (string) Str::uuid7(),
        );
    }

    /** @return array<string, int> */
    private function legacyTruthCounts(): array
    {
        return [
            'evidence_records' => DB::table('evidence_records')->count(),
            'evidence_decisions' => DB::table('evidence_decisions')->count(),
            'mastery_states' => DB::table('mastery_states')->count(),
            'vs003_custody_events' => DB::table('vs003_custody_events')->count(),
            'governed_evidence' => DB::table('governed_evidence')->count(),
            'evidence_mastery_states' => DB::table('evidence_mastery_states')->count(),
        ];
    }

    private function assertIntakeRejected(callable $operation, string $message): void
    {
        try {
            $operation();
        } catch (IntakeReviewException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());

            return;
        }

        $this->fail('Expected governed Intake/Review rejection.');
    }

    private function assertLogicRejected(callable $operation, string $message): void
    {
        try {
            $operation();
        } catch (LogicException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());

            return;
        }

        $this->fail('Expected governed logic rejection.');
    }

    private function assertInvalidArgumentRejected(callable $operation, string $message): void
    {
        try {
            $operation();
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());

            return;
        }

        $this->fail('Expected governed input rejection.');
    }
}
