<?php

namespace Tests\Feature;

use App\Modules\Evidence\Application\ProgressEvidenceService;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
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
        $this->owner = app(CreateOwner::class)->execute('Progress owner', 'progress-owner@example.test', 'Progress!Pass9', (string) Str::uuid7());
        $this->service = app(ProgressEvidenceService::class);
    }

    #[Test]
    public function candidate_does_not_become_evidence_without_admission_and_admission_does_not_create_review(): void
    {
        $candidate = $this->candidate();
        $this->assertSame('CANDIDATE', $candidate['state']);
        $this->assertDatabaseCount('evidence_candidates', 1);
        $this->assertDatabaseCount('governed_evidence', 0);
        $this->assertDatabaseCount('evidence_review_requests', 0);
        $this->assertDatabaseCount('evidence_reviews', 0);

        $admitted = $this->service->admitCandidate($candidate['id'], $this->owner->id);
        $this->assertSame('ACTIVE', $admitted['evidence']['lifecycle_state']);
        $this->assertSame('UNREVIEWED', $admitted['evidence']['review_status']);
        $this->assertSame('NONE', $admitted['evidence']['effective_review_decision']);
        $this->assertSame(1, (int) $admitted['revision']['revision']);
        $this->assertNotNull($admitted['revision']['sealed_at']);
        $this->assertDatabaseCount('governed_evidence', 1);
        $this->assertDatabaseCount('governed_evidence_revisions', 1);
        $this->assertDatabaseCount('evidence_review_requests', 0);
        $this->assertDatabaseCount('evidence_reviews', 0);
    }

    #[Test]
    public function evidence_lifecycle_review_status_and_effective_decision_are_independent_dimensions(): void
    {
        $evidence = $this->admittedEvidence();
        $request = $this->service->requestReview($evidence['id'], $this->owner->id);
        $this->assertDatabaseHas('governed_evidence', ['id' => $evidence['id'], 'lifecycle_state' => 'ACTIVE', 'review_status' => 'UNREVIEWED', 'effective_review_decision' => 'NONE']);

        $review = $this->service->admitReviewRequest($request['id'], $this->owner->id);
        $this->assertDatabaseHas('governed_evidence', ['id' => $evidence['id'], 'lifecycle_state' => 'ACTIVE', 'review_status' => 'IN_REVIEW', 'effective_review_decision' => 'NONE']);

        $this->service->recordFinding($review['id'], $this->owner->id, 'criterion-1', 'SATISFIED', 'المعيار مستوفى وفق الدليل المختوم.');
        $this->service->recordReviewDecision($review['id'], $this->owner->id, 'ACCEPT', 'النتيجة مقبولة ضمن حدود الدليل المثبت.');
        $this->service->transitionLifecycle($evidence['id'], $this->owner->id, 'WITHDRAWN');
        $this->assertDatabaseHas('governed_evidence', ['id' => $evidence['id'], 'lifecycle_state' => 'WITHDRAWN', 'review_status' => 'REVIEWED', 'effective_review_decision' => 'ACCEPT']);
    }

    #[Test]
    public function review_findings_and_review_decision_are_separate_persisted_facts(): void
    {
        $evidence = $this->admittedEvidence();
        $request = $this->service->requestReview($evidence['id'], $this->owner->id);
        $review = $this->service->admitReviewRequest($request['id'], $this->owner->id);
        $finding = $this->service->recordFinding($review['id'], $this->owner->id, 'provenance-pinned', 'PARTIALLY_SATISFIED', 'المصدر مثبت لكن يحتاج شرحًا إضافيًا للحدود.');
        $this->assertSame('PARTIALLY_SATISFIED', $finding['finding']);
        $this->assertDatabaseCount('evidence_review_findings', 1);
        $this->assertDatabaseCount('evidence_review_decisions', 0);
        $this->assertDatabaseHas('governed_evidence', ['id' => $evidence['id'], 'review_status' => 'IN_REVIEW', 'effective_review_decision' => 'NONE']);

        $decision = $this->service->recordReviewDecision($review['id'], $this->owner->id, 'ACCEPT_WITH_LIMITATIONS', 'مقبول مع إبقاء حدود المصدر جزءًا من قرار المراجعة.');
        $this->assertSame('ACCEPT_WITH_LIMITATIONS', $decision['decision']);
        $this->assertDatabaseCount('evidence_review_findings', 1);
        $this->assertDatabaseCount('evidence_review_decisions', 1);
    }

    #[Test]
    public function sealed_evidence_revisions_are_historical_and_not_destructively_overwritten(): void
    {
        $admitted = $this->service->admitCandidate($this->candidate()['id'], $this->owner->id);
        $evidenceId = $admitted['evidence']['id'];
        $revisionOne = $admitted['revision'];
        $revisionTwo = $this->service->createRevision($evidenceId, $this->owner->id, ['title' => 'Revision 2 title', 'summary' => 'Revision 2 summary keeps the prior sealed revision intact.', 'facts' => ['finding' => 'updated-governed-claim']]);
        $this->assertSame(2, (int) $revisionTwo['revision']);
        $this->assertDatabaseCount('governed_evidence_revisions', 2);
        $this->assertDatabaseHas('governed_evidence_revisions', ['id' => $revisionOne['id'], 'revision' => 1, 'title' => $revisionOne['title'], 'content_digest' => $revisionOne['content_digest']]);
        $this->assertDatabaseHas('governed_evidence', ['id' => $evidenceId, 'current_revision_number' => 2]);
    }

    #[Test]
    public function mastery_judgment_and_freshness_are_independent_and_mastered_can_require_revalidation(): void
    {
        $evidence = $this->reviewedAcceptedEvidence();
        $result = $this->service->evaluateMastery($this->owner->id, $evidence['capability_id'], $this->owner->id, 'MP-APPSEC-v4', 'MASTERED', 'REVALIDATION_REQUIRED', [$evidence['id']], [], 'الدليل مقبول، لكن سياسة أحدث تتطلب إعادة تحقق من حداثته.');
        $this->assertSame('MASTERED', $result['judgment']);
        $this->assertSame('REVALIDATION_REQUIRED', $result['freshness_status']);
        $this->assertDatabaseHas('evidence_mastery_states', ['subject_actor_id' => $this->owner->id, 'target_type' => 'CAPABILITY', 'target_id' => $evidence['capability_id'], 'judgment' => 'MASTERED', 'freshness_status' => 'REVALIDATION_REQUIRED']);
    }

    #[Test]
    public function portfolio_references_canonical_evidence_and_removing_it_does_not_delete_evidence(): void
    {
        $evidence = $this->reviewedAcceptedEvidence();
        $portfolio = $this->service->createPortfolio($this->owner->id, 'Application Security — Professional Evidence', 'Application Security', 'CAPABILITY', ['lifecycle' => ['ACTIVE']], ['purpose' => 'professional']);
        $this->service->addEvidenceToPortfolio($portfolio['id'], $evidence['id'], $this->owner->id, 'Evidence curated for presentation.', 10);
        $this->assertDatabaseHas('evidence_portfolio_items', ['portfolio_id' => $portfolio['id'], 'evidence_id' => $evidence['id']]);
        $this->assertFalse(Schema::hasColumn('evidence_portfolio_items', 'facts'));
        $this->assertFalse(Schema::hasColumn('evidence_portfolio_items', 'summary'));
        $this->assertDatabaseCount('governed_evidence', 1);
        $this->service->removeEvidenceFromPortfolio($portfolio['id'], $evidence['id'], $this->owner->id);
        $this->assertDatabaseCount('evidence_portfolio_items', 0);
        $this->assertDatabaseHas('governed_evidence', ['id' => $evidence['id']]);
    }

    #[Test]
    public function formal_review_surface_is_authenticated(): void
    {
        $this->get('/progress/reviews')->assertRedirect('/login');
        $this->actingAs($this->owner)->get('/progress/reviews')->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('ProgressEvidence/Workspace')->where('surface', 'reviews'));
    }

    #[Test]
    public function representative_ui_reads_real_persisted_governed_state(): void
    {
        $evidence = $this->reviewedAcceptedEvidence();
        $this->service->evaluateMastery($this->owner->id, $evidence['capability_id'], $this->owner->id, 'MP-APPSEC-v4', 'MASTERED', 'CURRENT', [$evidence['id']], [], 'القرار الرسمي والدليل المختوم يدعمان الحكم الحالي.');
        $this->actingAs($this->owner)->get('/progress')->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->component('ProgressEvidence/Workspace')->where('surface', 'evidence')->where('summary.evidence_count', 1)->has('evidence', 1)
            ->where('evidence.0.id', $evidence['id'])->where('evidence.0.lifecycle_state', 'ACTIVE')->where('evidence.0.review_status', 'REVIEWED')
            ->where('evidence.0.effective_review_decision', 'ACCEPT')->has('mastery', 1)->where('mastery.0.judgment', 'MASTERED'));
    }

    /** @return array<string,mixed> */
    private function candidate(): array
    {
        return $this->service->intakeCandidate($this->owner->id, $this->owner->id, [
            'source_type' => 'SYNTHETIC_TEST_HANDOFF', 'source_id' => 'fixture:result:'.Str::lower(Str::random(12)), 'source_revision' => '1',
            'source_digest' => hash('sha256', Str::random(64)), 'capability_id' => 'CAP-APPSEC-INPUT-VALIDATION',
            'title' => 'Governed input-validation evidence', 'summary' => 'Persisted synthetic fixture handed off through the W04 source contract.',
            'facts' => ['claim' => 'The learner identified and remediated an input-validation weakness.', 'environment' => 'isolated-test-fixture'],
            'metadata' => ['fixture' => true],
        ]);
    }

    /** @return array<string,mixed> */
    private function admittedEvidence(): array { return $this->service->admitCandidate($this->candidate()['id'], $this->owner->id)['evidence']; }

    /** @return array<string,mixed> */
    private function reviewedAcceptedEvidence(): array
    {
        $evidence = $this->admittedEvidence();
        $request = $this->service->requestReview($evidence['id'], $this->owner->id);
        $review = $this->service->admitReviewRequest($request['id'], $this->owner->id);
        $this->service->recordFinding($review['id'], $this->owner->id, 'governed-evidence-quality', 'SATISFIED', 'الدليل المختوم يحقق معيار الجودة المطلوب.');
        $this->service->recordReviewDecision($review['id'], $this->owner->id, 'ACCEPT', 'المراجعة الرسمية تقبل الدليل ضمن النطاق المحدد.');
        return (array) DB::table('governed_evidence')->where('id', $evidence['id'])->firstOrFail();
    }
}
