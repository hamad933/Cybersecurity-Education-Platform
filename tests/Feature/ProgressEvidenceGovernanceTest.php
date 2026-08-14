<?php

namespace Tests\Feature;

use App\Modules\Evidence\Application\ProgressEvidenceService;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProgressEvidenceGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressEvidenceService $service;

    private OwnerAccount $owner;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Route::has('cep.progress.index')) {
            require base_path('routes/workspaces/progress-evidence.php');
        }
        $this->owner = app(CreateOwner::class)->execute(
            'Progress owner',
            'progress-owner@example.test',
            'Progress!Pass9',
            (string) Str::uuid7(),
        );
        $this->service = app(ProgressEvidenceService::class);
    }

    #[Test]
    public function candidate_follows_the_legal_intake_lifecycle_before_admission_and_admission_stops_before_review_or_mastery(): void
    {
        $candidate = $this->candidate();
        $this->assertSame('RECEIVED', $candidate['state']);
        $this->assertDatabaseCount('governed_evidence', 0);

        $candidate = $this->service->transitionCandidate($candidate['id'], $this->owner->id, 'PREPARED');
        $this->assertSame('PREPARED', $candidate['state']);
        $candidate = $this->service->transitionCandidate($candidate['id'], $this->owner->id, 'SUBMITTED_FOR_INTAKE');
        $this->assertSame('SUBMITTED_FOR_INTAKE', $candidate['state']);

        $admitted = $this->service->admitCandidate($candidate['id'], $this->owner->id);
        $this->assertSame('ACTIVE', $admitted['evidence']['lifecycle_state']);
        $this->assertSame('UNREVIEWED', $admitted['evidence']['review_status']);
        $this->assertSame('NONE', $admitted['evidence']['effective_review_decision']);
        $this->assertSame(1, (int) $admitted['revision']['revision']);
        $this->assertSame('INITIAL_ADMISSION', $admitted['revision']['revision_reason']);
        $this->assertNotNull($admitted['revision']['sealed_at']);
        $this->assertDatabaseHas('evidence_candidates', ['id' => $candidate['id'], 'state' => 'ADMITTED']);
        $this->assertDatabaseCount('governed_evidence', 1);
        $this->assertDatabaseCount('governed_evidence_revisions', 1);
        $this->assertDatabaseCount('evidence_review_requests', 0);
        $this->assertDatabaseCount('evidence_reviews', 0);
        $this->assertDatabaseCount('evidence_review_decisions', 0);
        $this->assertDatabaseCount('evidence_mastery_states', 0);
    }

    #[Test]
    public function illegal_candidate_lifecycle_transitions_and_terminal_reopening_are_rejected(): void
    {
        $candidate = $this->candidate();
        $this->assertLogicRejected(
            fn () => $this->service->admitCandidate($candidate['id'], $this->owner->id),
            'SUBMITTED_FOR_INTAKE',
        );
        $this->assertLogicRejected(
            fn () => $this->service->transitionCandidate($candidate['id'], $this->owner->id, 'SUBMITTED_FOR_INTAKE'),
            'Illegal Candidate Evidence transition',
        );

        $submitted = $this->submitCandidate($candidate);
        $declined = $this->service->transitionCandidate($submitted['id'], $this->owner->id, 'DECLINED');
        $this->assertSame('DECLINED', $declined['state']);
        $this->assertLogicRejected(
            fn () => $this->service->transitionCandidate($declined['id'], $this->owner->id, 'PREPARED'),
            'Illegal Candidate Evidence transition',
        );
        $this->assertDatabaseCount('governed_evidence', 0);
    }

    #[Test]
    public function duplicate_candidate_identity_uses_all_a03_semantic_components_and_detects_integrity_conflicts(): void
    {
        $handoff = $this->handoff([
            'selected_material_refs' => ['artifact:02', 'artifact:01'],
            'criterion_scope' => ['CRIT-B', 'CRIT-A'],
        ]);
        $first = $this->service->intakeCandidate($this->owner->id, $this->owner->id, $handoff);
        $sameSemanticIdentity = [
            ...$handoff,
            'title' => 'Different display title does not create a new Evidence identity',
            'summary' => 'Presentation metadata is not part of A03 duplicate semantic identity.',
            'selected_material_refs' => ['artifact:01', 'artifact:02'],
            'criterion_scope' => ['CRIT-A', 'CRIT-B'],
        ];
        $duplicate = $this->service->intakeCandidate($this->owner->id, $this->owner->id, $sameSemanticIdentity);
        $this->assertSame($first['id'], $duplicate['id']);
        $this->assertDatabaseCount('evidence_candidates', 1);

        $differentClaim = [
            ...$handoff,
            'evidence_claim' => 'A materially different governed claim over the same source revision.',
        ];
        $second = $this->service->intakeCandidate($this->owner->id, $this->owner->id, $differentClaim);
        $this->assertNotSame($first['id'], $second['id']);
        $this->assertDatabaseCount('evidence_candidates', 2);

        $integrityConflict = [
            ...$sameSemanticIdentity,
            'source_digest' => hash('sha256', 'different-bytes-for-same-source-revision'),
        ];
        $this->assertLogicRejected(
            fn () => $this->service->intakeCandidate($this->owner->id, $this->owner->id, $integrityConflict),
            'integrity digest',
        );
    }

    #[Test]
    public function superseding_evidence_revision_preserves_prior_sealed_revision_and_resets_only_current_review_projection(): void
    {
        $reviewed = $this->reviewedEvidence('ACCEPT');
        $revisionOne = $reviewed['revision'];
        $decisionOne = $reviewed['decision'];

        $revisionTwo = $this->service->createRevision($reviewed['evidence']['id'], $this->owner->id, [
            'title' => 'Governed input-validation evidence — corrected',
            'summary' => 'Correction of the same governed Evidence Claim while preserving Revision 1.',
            'facts' => ['claim' => 'Corrected fact set'],
            'selected_material_refs' => ['artifact:fixture:corrected'],
            'criterion_scope' => ['CRIT-INPUT-VALIDATION'],
            'source_revision' => '2',
            'source_digest' => hash('sha256', 'source-revision-two'),
            'revision_reason' => 'Correct the same governed claim after source Revision 2.',
        ]);

        $this->assertSame(2, (int) $revisionTwo['revision']);
        $this->assertSame($revisionOne['id'], $revisionTwo['previous_revision_id']);
        $this->assertDatabaseCount('governed_evidence_revisions', 2);
        $this->assertDatabaseHas('governed_evidence_revisions', [
            'id' => $revisionOne['id'],
            'revision' => 1,
            'content_digest' => $revisionOne['content_digest'],
        ]);
        $this->assertDatabaseHas('evidence_review_decisions', [
            'id' => $decisionOne['id'],
            'evidence_revision_id' => $revisionOne['id'],
            'decision' => 'ACCEPT',
        ]);
        $this->assertDatabaseHas('governed_evidence', [
            'id' => $reviewed['evidence']['id'],
            'current_revision_number' => 2,
            'review_status' => 'UNREVIEWED',
            'effective_review_decision' => 'NONE',
            'effective_review_decision_id' => null,
        ]);
    }

    #[Test]
    public function sealed_evidence_revision_is_database_immutable(): void
    {
        $bundle = $this->admittedBundle();
        $this->expectException(QueryException::class);
        DB::table('governed_evidence_revisions')
            ->where('id', $bundle['revision']['id'])
            ->update(['summary' => 'destructive rewrite attempt']);
    }

    #[Test]
    public function rereview_pins_an_exact_revision_and_supersedes_the_prior_decision_without_mutating_it(): void
    {
        $first = $this->reviewedEvidence('ACCEPT');
        $revisionTwo = $this->service->createRevision($first['evidence']['id'], $this->owner->id, [
            'title' => 'Revision 2 for re-review',
            'summary' => 'Same governed claim, superseding Revision 1.',
            'revision_reason' => 'Material extension of the same governed Evidence Claim.',
        ]);
        $request = $this->service->requestReview($first['evidence']['id'], $this->owner->id);
        $this->assertSame($revisionTwo['id'], $request['evidence_revision_id']);
        $this->assertSame($first['decision']['id'], $request['prior_decision_id']);
        $review = $this->service->admitReviewRequest($request['id'], $this->owner->id);
        $this->service->recordFinding(
            $review['id'],
            $this->owner->id,
            'CRIT-INPUT-VALIDATION',
            'NOT_SATISFIED',
            'The corrected revision no longer satisfies the pinned criterion.',
        );
        $secondDecision = $this->service->recordReviewDecision(
            $review['id'],
            $this->owner->id,
            'REJECT',
            'The re-review rejects Revision 2 in the same capability scope.',
        );

        $this->assertSame($revisionTwo['id'], $secondDecision['evidence_revision_id']);
        $this->assertSame($first['decision']['id'], $secondDecision['supersedes_decision_id']);
        $this->assertDatabaseHas('evidence_review_decisions', [
            'id' => $first['decision']['id'],
            'decision' => 'ACCEPT',
            'supersedes_decision_id' => null,
        ]);
        $this->assertDatabaseHas('governed_evidence', [
            'id' => $first['evidence']['id'],
            'effective_review_decision_id' => $secondDecision['id'],
            'effective_review_decision' => 'REJECT',
        ]);
    }

    #[Test]
    public function review_status_and_effective_decision_remain_distinct_during_rereview(): void
    {
        $first = $this->reviewedEvidence('ACCEPT');
        $request = $this->service->requestReview($first['evidence']['id'], $this->owner->id);
        $this->service->admitReviewRequest($request['id'], $this->owner->id);
        $this->assertDatabaseHas('governed_evidence', [
            'id' => $first['evidence']['id'],
            'lifecycle_state' => 'ACTIVE',
            'review_status' => 'IN_REVIEW',
            'effective_review_decision' => 'ACCEPT',
            'effective_review_decision_id' => $first['decision']['id'],
        ]);
    }

    #[Test]
    public function mastery_appends_history_and_new_state_supersedes_prior_current_state_by_reference(): void
    {
        $reviewed = $this->reviewedEvidence('ACCEPT');
        $first = $this->evaluateFromReviewed($reviewed, 'MASTERED', 'CURRENT', 'Initial governed Mastery judgment.');
        $second = $this->evaluateFromReviewed(
            $reviewed,
            'MASTERED',
            'REVALIDATION_REQUIRED',
            'Freshness policy changed without erasing the historical competency judgment.',
        );

        $this->assertNotSame($first['id'], $second['id']);
        $this->assertSame($first['id'], $second['previous_state_id']);
        $this->assertDatabaseCount('evidence_mastery_states', 2);
        $this->assertDatabaseHas('evidence_mastery_states', [
            'id' => $first['id'],
            'judgment' => 'MASTERED',
            'freshness_status' => 'CURRENT',
            'previous_state_id' => null,
        ]);
        $this->assertDatabaseHas('evidence_mastery_states', [
            'id' => $second['id'],
            'judgment' => 'MASTERED',
            'freshness_status' => 'REVALIDATION_REQUIRED',
            'previous_state_id' => $first['id'],
        ]);

        $workspace = $this->service->workspace($this->owner->id);
        $this->assertSame($second['id'], $workspace['mastery'][0]['id']);
        $this->assertCount(2, $workspace['mastery_history']);
    }

    #[Test]
    public function mastery_state_retains_exact_review_decision_and_evidence_revision_provenance(): void
    {
        $reviewed = $this->reviewedEvidence('ACCEPT_WITH_LIMITATIONS');
        $state = $this->evaluateFromReviewed(
            $reviewed,
            'MASTERED',
            'CURRENT',
            'The exact effective decision and exact sealed Evidence Revision support this judgment.',
        );

        $this->assertSame([$reviewed['decision']['id']], $state['review_decision_ids']);
        $this->assertSame([$reviewed['revision']['id']], $state['supporting_evidence_revision_ids']);
        $this->assertDatabaseHas('evidence_mastery_state_decisions', [
            'mastery_state_id' => $state['id'],
            'review_decision_id' => $reviewed['decision']['id'],
        ]);
        $this->assertDatabaseHas('evidence_mastery_state_evidence', [
            'mastery_state_id' => $state['id'],
            'evidence_revision_id' => $reviewed['revision']['id'],
            'contribution' => 'SUPPORTING',
        ]);
    }

    #[Test]
    public function supporting_and_contradicting_revision_references_are_both_validated_and_persisted(): void
    {
        $supporting = $this->reviewedEvidence('ACCEPT');
        $contradicting = $this->reviewedEvidence('REJECT', [
            'source_id' => 'fixture:result:contradicting-'.Str::lower(Str::random(8)),
            'source_digest' => hash('sha256', 'contradicting-'.Str::random(24)),
            'selected_material_refs' => ['artifact:contradicting'],
            'evidence_claim' => 'Independent evidence indicates the capability was not demonstrated under a different controlled attempt.',
            'title' => 'Contradicting governed evidence',
        ]);

        $state = $this->service->evaluateMastery(
            $this->owner->id,
            $supporting['evidence']['capability_id'],
            $this->owner->id,
            'MP-APPSEC-v4',
            'INCONCLUSIVE',
            'CURRENT',
            [$supporting['decision']['id'], $contradicting['decision']['id']],
            [$supporting['revision']['id']],
            [$contradicting['revision']['id']],
            'Conflicting governed Evidence requires an inconclusive judgment rather than latest-wins.',
        );

        $this->assertSame([$supporting['revision']['id']], $state['supporting_evidence_revision_ids']);
        $this->assertSame([$contradicting['revision']['id']], $state['contradicting_evidence_revision_ids']);
        $this->assertDatabaseHas('evidence_mastery_state_evidence', [
            'mastery_state_id' => $state['id'],
            'evidence_revision_id' => $contradicting['revision']['id'],
            'contribution' => 'CONTRADICTING',
        ]);
    }

    #[Test]
    public function unknown_supporting_contradicting_and_review_decision_references_are_rejected(): void
    {
        $reviewed = $this->reviewedEvidence('ACCEPT');
        $unknownRevision = (string) Str::uuid7();
        $unknownDecision = (string) Str::uuid7();

        $this->assertLogicRejected(
            fn () => $this->service->evaluateMastery(
                $this->owner->id,
                $reviewed['evidence']['capability_id'],
                $this->owner->id,
                'MP-APPSEC-v4',
                'INCONCLUSIVE',
                'CURRENT',
                [],
                [$unknownRevision],
                [],
                'Unknown supporting reference must fail.',
            ),
            'Unknown Evidence Revision reference',
        );
        $this->assertLogicRejected(
            fn () => $this->service->evaluateMastery(
                $this->owner->id,
                $reviewed['evidence']['capability_id'],
                $this->owner->id,
                'MP-APPSEC-v4',
                'INCONCLUSIVE',
                'CURRENT',
                [],
                [],
                [$unknownRevision],
                'Unknown contradicting reference must fail.',
            ),
            'Unknown Evidence Revision reference',
        );
        $this->assertLogicRejected(
            fn () => $this->service->evaluateMastery(
                $this->owner->id,
                $reviewed['evidence']['capability_id'],
                $this->owner->id,
                'MP-APPSEC-v4',
                'INCONCLUSIVE',
                'CURRENT',
                [$unknownDecision],
                [$reviewed['revision']['id']],
                [],
                'Unknown Review Decision must fail.',
            ),
            'Unknown Review Decision reference',
        );
    }

    #[Test]
    public function cross_actor_evidence_and_decision_provenance_is_rejected(): void
    {
        $other = $this->secondOwner();
        $otherReviewed = $this->reviewedEvidenceFor($other, 'ACCEPT', [
            'source_id' => 'fixture:other-owner',
            'source_digest' => hash('sha256', 'other-owner-source'),
        ]);

        $this->assertLogicRejected(
            fn () => $this->service->evaluateMastery(
                $this->owner->id,
                $otherReviewed['evidence']['capability_id'],
                $this->owner->id,
                'MP-APPSEC-v4',
                'MASTERED',
                'CURRENT',
                [$otherReviewed['decision']['id']],
                [$otherReviewed['revision']['id']],
                [],
                'Cross-actor provenance must fail.',
            ),
            'outside the Mastery subject/capability boundary',
        );
    }

    #[Test]
    public function superseded_review_decision_cannot_be_reused_as_effective_mastery_provenance(): void
    {
        $first = $this->reviewedEvidence('ACCEPT');
        $revisionTwo = $this->service->createRevision($first['evidence']['id'], $this->owner->id, [
            'title' => 'Revision 2',
            'summary' => 'Revision used for superseding review.',
            'revision_reason' => 'Extend same claim.',
        ]);
        $request = $this->service->requestReview($first['evidence']['id'], $this->owner->id);
        $review = $this->service->admitReviewRequest($request['id'], $this->owner->id);
        $this->service->recordFinding(
            $review['id'],
            $this->owner->id,
            'CRIT-INPUT-VALIDATION',
            'SATISFIED',
            'Revision 2 satisfies the criterion.',
        );
        $currentDecision = $this->service->recordReviewDecision(
            $review['id'],
            $this->owner->id,
            'ACCEPT',
            'Revision 2 is accepted and supersedes the prior decision.',
        );

        $this->assertLogicRejected(
            fn () => $this->service->evaluateMastery(
                $this->owner->id,
                $first['evidence']['capability_id'],
                $this->owner->id,
                'MP-APPSEC-v4',
                'MASTERED',
                'CURRENT',
                [$first['decision']['id']],
                [$first['revision']['id']],
                [],
                'A superseded decision cannot be represented as the effective basis.',
            ),
            'Superseded Review Decisions',
        );

        $state = $this->service->evaluateMastery(
            $this->owner->id,
            $first['evidence']['capability_id'],
            $this->owner->id,
            'MP-APPSEC-v4',
            'MASTERED',
            'CURRENT',
            [$currentDecision['id']],
            [$revisionTwo['id']],
            [],
            'The current decision and current sealed revision are exact provenance.',
        );
        $this->assertSame([$currentDecision['id']], $state['review_decision_ids']);
    }

    #[Test]
    public function mastery_judgment_and_freshness_are_independent_and_mastered_with_revalidation_required_is_legal(): void
    {
        $reviewed = $this->reviewedEvidence('ACCEPT');
        $result = $this->evaluateFromReviewed(
            $reviewed,
            'MASTERED',
            'REVALIDATION_REQUIRED',
            'The competency judgment remains MASTERED while the policy requires freshness revalidation.',
        );

        $this->assertSame('MASTERED', $result['judgment']);
        $this->assertSame('REVALIDATION_REQUIRED', $result['freshness_status']);
        $this->assertDatabaseHas('evidence_mastery_states', [
            'id' => $result['id'],
            'judgment' => 'MASTERED',
            'freshness_status' => 'REVALIDATION_REQUIRED',
        ]);
    }

    #[Test]
    public function portfolio_is_reference_only_and_removal_never_deletes_canonical_evidence_or_mastery_history(): void
    {
        $reviewed = $this->reviewedEvidence('ACCEPT');
        $state = $this->evaluateFromReviewed($reviewed, 'MASTERED', 'CURRENT', 'Portfolio projection fixture.');
        $portfolio = $this->service->createPortfolio(
            $this->owner->id,
            'Application Security — Professional Evidence',
            'Application Security',
            'CAPABILITY',
            ['lifecycle' => ['ACTIVE']],
            ['purpose' => 'professional'],
        );
        $this->service->addEvidenceToPortfolio(
            $portfolio['id'],
            $reviewed['evidence']['id'],
            $this->owner->id,
            'Reference-only curated item.',
            10,
        );

        $this->assertDatabaseHas('evidence_portfolio_items', [
            'portfolio_id' => $portfolio['id'],
            'evidence_id' => $reviewed['evidence']['id'],
            'mastery_state_id' => $state['id'],
        ]);
        $this->assertFalse(Schema::hasColumn('evidence_portfolio_items', 'facts'));
        $this->assertFalse(Schema::hasColumn('evidence_portfolio_items', 'summary'));
        $this->assertFalse(Schema::hasColumn('evidence_portfolio_items', 'judgment'));

        $this->service->removeEvidenceFromPortfolio($portfolio['id'], $reviewed['evidence']['id'], $this->owner->id);
        $this->assertDatabaseCount('evidence_portfolio_items', 0);
        $this->assertDatabaseHas('governed_evidence', ['id' => $reviewed['evidence']['id']]);
        $this->assertDatabaseHas('evidence_mastery_states', ['id' => $state['id']]);
    }

    #[Test]
    public function postgres_constraints_and_immutable_governance_triggers_are_installed(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $constraintNames = collect(DB::select(
            "SELECT conname FROM pg_constraint WHERE conname IN (
                'evidence_candidate_state_check',
                'governed_evidence_lifecycle_check',
                'governed_evidence_review_status_check',
                'governed_evidence_effective_decision_check',
                'evidence_mastery_state_judgment_check',
                'evidence_mastery_state_freshness_check',
                'evidence_mastery_state_evidence_contribution_check'
            )",
        ))->pluck('conname');
        $this->assertCount(7, $constraintNames);

        $triggerNames = collect(DB::select(
            "SELECT tgname FROM pg_trigger WHERE NOT tgisinternal AND tgname IN (
                'governed_evidence_revisions_immutable',
                'evidence_review_decisions_immutable',
                'evidence_mastery_states_immutable'
            )",
        ))->pluck('tgname');
        $this->assertCount(3, $triggerNames);
    }

    #[Test]
    public function postgres_rejects_illegal_candidate_state_even_if_application_validation_is_bypassed(): void
    {
        $candidate = $this->candidate();
        $this->expectException(QueryException::class);
        DB::table('evidence_candidates')->where('id', $candidate['id'])->update(['state' => 'INVALID_STATE']);
    }

    #[Test]
    public function auth_and_actor_ownership_are_enforced_on_w04_routes_and_services(): void
    {
        $this->get('/progress/reviews')->assertRedirect('/login');
        $this->actingAs($this->owner)
            ->get('/progress/reviews')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('ProgressEvidence/Workspace')
                ->where('surface', 'reviews'));

        $other = $this->secondOwner();
        $otherCandidate = $this->service->intakeCandidate($other->id, $other->id, $this->handoff([
            'source_id' => 'fixture:owned-by-other',
            'source_digest' => hash('sha256', 'owned-by-other'),
        ]));
        $this->assertLogicRejected(
            fn () => $this->service->transitionCandidate($otherCandidate['id'], $this->owner->id, 'PREPARED'),
            'outside actor boundary',
        );
    }

    #[Test]
    public function w04_http_application_flow_exposes_candidate_transitions_before_admission(): void
    {
        $payload = $this->handoff([
            'source_id' => 'fixture:http-flow',
            'source_digest' => hash('sha256', 'http-flow'),
        ]);
        $this->actingAs($this->owner)->post('/progress/intake', $payload)->assertRedirect();
        $candidate = (array) DB::table('evidence_candidates')->where('source_id', 'fixture:http-flow')->firstOrFail();
        $this->assertSame('RECEIVED', $candidate['state']);

        $this->actingAs($this->owner)
            ->post("/progress/candidates/{$candidate['id']}/state", ['state' => 'PREPARED'])
            ->assertRedirect();
        $this->actingAs($this->owner)
            ->post("/progress/candidates/{$candidate['id']}/state", ['state' => 'SUBMITTED_FOR_INTAKE'])
            ->assertRedirect();
        $this->actingAs($this->owner)
            ->post("/progress/candidates/{$candidate['id']}/admit")
            ->assertRedirect();

        $this->assertDatabaseHas('evidence_candidates', ['id' => $candidate['id'], 'state' => 'ADMITTED']);
        $this->assertDatabaseCount('governed_evidence', 1);
        $this->assertDatabaseCount('evidence_review_requests', 0);
        $this->assertDatabaseCount('evidence_mastery_states', 0);
    }

    #[Test]
    public function representative_ui_reads_current_mastery_and_full_mastery_history_from_real_persisted_state(): void
    {
        $reviewed = $this->reviewedEvidence('ACCEPT');
        $first = $this->evaluateFromReviewed($reviewed, 'MASTERED', 'CURRENT', 'Initial state.');
        $second = $this->evaluateFromReviewed($reviewed, 'MASTERED', 'REVALIDATION_REQUIRED', 'Revalidation state.');

        $this->actingAs($this->owner)
            ->get('/progress/mastery')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('ProgressEvidence/Workspace')
                ->where('surface', 'mastery')
                ->has('mastery', 1)
                ->where('mastery.0.id', $second['id'])
                ->where('mastery.0.previous_state_id', $first['id'])
                ->where('mastery.0.judgment', 'MASTERED')
                ->where('mastery.0.freshness_status', 'REVALIDATION_REQUIRED')
                ->has('mastery_history', 2));
    }

    /** @return array<string, mixed> */
    private function handoff(array $overrides = []): array
    {
        return [
            'source_type' => 'SYNTHETIC_TEST_HANDOFF',
            'source_id' => 'fixture:result:'.Str::lower(Str::random(12)),
            'source_revision' => '1',
            'source_digest' => hash('sha256', Str::random(64)),
            'selected_material_refs' => ['artifact:fixture:primary'],
            'capability_id' => 'CAP-APPSEC-INPUT-VALIDATION',
            'evidence_claim' => 'The learner identified and remediated an input-validation weakness.',
            'criterion_scope' => ['CRIT-INPUT-VALIDATION'],
            'governed_purpose' => 'FORMAL_CAPABILITY_EVIDENCE',
            'title' => 'Governed input-validation evidence',
            'summary' => 'Persisted synthetic fixture handed off through the W04 source contract.',
            'facts' => [
                'claim' => 'The learner identified and remediated an input-validation weakness.',
                'environment' => 'isolated-test-fixture',
            ],
            'metadata' => ['fixture' => true],
            ...$overrides,
        ];
    }

    /** @return array<string, mixed> */
    private function candidate(array $overrides = []): array
    {
        return $this->service->intakeCandidate(
            $this->owner->id,
            $this->owner->id,
            $this->handoff($overrides),
        );
    }

    /** @param array<string, mixed> $candidate */
    private function submitCandidate(array $candidate): array
    {
        if (in_array($candidate['state'], ['RECEIVED', 'DRAFT', 'RETURNED_FOR_CONTEXT'], true)) {
            $candidate = $this->service->transitionCandidate($candidate['id'], $candidate['subject_actor_id'], 'PREPARED');
        }
        if ($candidate['state'] === 'PREPARED') {
            $candidate = $this->service->transitionCandidate($candidate['id'], $candidate['subject_actor_id'], 'SUBMITTED_FOR_INTAKE');
        }

        return $candidate;
    }

    /** @return array{evidence: array<string, mixed>, revision: array<string, mixed>} */
    private function admittedBundle(array $overrides = []): array
    {
        $candidate = $this->submitCandidate($this->candidate($overrides));

        return $this->service->admitCandidate($candidate['id'], $this->owner->id);
    }

    /** @return array{evidence: array<string, mixed>, revision: array<string, mixed>, decision: array<string, mixed>} */
    private function reviewedEvidence(string $decision = 'ACCEPT', array $overrides = []): array
    {
        return $this->reviewedEvidenceFor($this->owner, $decision, $overrides);
    }

    /** @return array{evidence: array<string, mixed>, revision: array<string, mixed>, decision: array<string, mixed>} */
    private function reviewedEvidenceFor(OwnerAccount $owner, string $decision, array $overrides = []): array
    {
        $candidate = $this->service->intakeCandidate($owner->id, $owner->id, $this->handoff($overrides));
        $candidate = $this->service->transitionCandidate($candidate['id'], $owner->id, 'PREPARED');
        $candidate = $this->service->transitionCandidate($candidate['id'], $owner->id, 'SUBMITTED_FOR_INTAKE');
        $bundle = $this->service->admitCandidate($candidate['id'], $owner->id);
        $request = $this->service->requestReview($bundle['evidence']['id'], $owner->id);
        $review = $this->service->admitReviewRequest($request['id'], $owner->id);
        $finding = in_array($decision, ['ACCEPT', 'ACCEPT_WITH_LIMITATIONS'], true) ? 'SATISFIED' : 'NOT_SATISFIED';
        $this->service->recordFinding(
            $review['id'],
            $owner->id,
            'CRIT-INPUT-VALIDATION',
            $finding,
            'Synthetic governed finding over the pinned Evidence Revision.',
        );
        $reviewDecision = $this->service->recordReviewDecision(
            $review['id'],
            $owner->id,
            $decision,
            'Synthetic governed Review Decision for W04 A03 behavior.',
        );

        return [
            'evidence' => $bundle['evidence'],
            'revision' => $bundle['revision'],
            'decision' => $reviewDecision,
        ];
    }

    /**
     * @param array{evidence: array<string, mixed>, revision: array<string, mixed>, decision: array<string, mixed>} $reviewed
     */
    private function evaluateFromReviewed(
        array $reviewed,
        string $judgment,
        string $freshness,
        string $rationale,
    ): array {
        return $this->service->evaluateMastery(
            $this->owner->id,
            $reviewed['evidence']['capability_id'],
            $this->owner->id,
            'MP-APPSEC-v4',
            $judgment,
            $freshness,
            [$reviewed['decision']['id']],
            [$reviewed['revision']['id']],
            [],
            $rationale,
        );
    }

    private function secondOwner(): OwnerAccount
    {
        return app(CreateOwner::class)->execute(
            'Other owner',
            'other-'.Str::lower(Str::random(8)).'@example.test',
            'Other!Pass9',
            (string) Str::uuid7(),
        );
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
