<?php

namespace App\Modules\Evidence\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Evidence\Application\ProgressEvidenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use LogicException;
use App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceReviewService;
use App\Modules\Evidence\IntakeReview\Application\ReviewDecisionService;
use App\Modules\Evidence\Application\ProgressEvidence\MasteryPortfolio\MasteryPortfolioService;
use Throwable;

final class ProgressEvidenceController extends Controller
{
    public function __construct(
        private readonly ProgressEvidenceService $service,
        private readonly EvidenceIntakeService $intakeService,
        private readonly EvidenceReviewService $reviewService,
        private readonly ReviewDecisionService $decisionService,
        private readonly MasteryPortfolioService $masteryService,
    ) {}

    public function index(Request $request): Response
    {
        return $this->render($request, 'evidence');
    }

    public function reviews(Request $request): Response
    {
        return $this->render($request, 'reviews');
    }

    public function mastery(Request $request): Response
    {
        return $this->render($request, 'mastery');
    }

    public function portfolio(Request $request): Response
    {
        return $this->render($request, 'portfolio');
    }

    public function intake(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'handoff_receipt_id' => ['required', 'uuid'],
            'source_type' => ['prohibited'],
            'source_id' => ['prohibited'],
            'source_revision' => ['prohibited'],
            'source_digest' => ['prohibited'],
            'selected_material_refs' => ['prohibited'],
            'capability_id' => ['prohibited'],
            'evidence_claim' => ['required', 'string', 'max:4000'],
            'criterion_scope' => ['present', 'array', 'max:50'],
            'criterion_scope.*' => ['string', 'max:120'],
            'governed_purpose' => ['required', 'in:FORMAL_CAPABILITY_EVIDENCE,GOVERNED_PROVENANCE_ATTESTATION'],
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:4000'],
            'facts' => ['prohibited'],
            'metadata' => ['prohibited'],
        ]);

        return $this->workflow(function () use ($request, $data): void {
            $actor = $this->actorId($request);
            $this->intakeService->receive($actor, $actor, $data);
        }, 'تم إنشاء Candidate Evidence في حالة RECEIVED مع تثبيت المصدر والهوية الدلالية؛ لم تُنشأ Evidence بعد.');
    }

    public function transitionCandidate(Request $request, string $candidate): RedirectResponse
    {
        $data = $request->validate([
            'target_state' => ['required', 'in:DRAFT,PREPARED,SUBMITTED_FOR_INTAKE,RETURNED_FOR_CONTEXT,DECLINED,WITHDRAWN'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        return $this->workflow(
            fn () => $this->intakeService->transitionCandidate($candidate, $this->actorId($request), $data['target_state'], $data['reason']),
            'تم تسجيل حدث Lifecycle للمرشح.',
        );
    }

    public function admitCandidate(Request $request, string $candidate): RedirectResponse
    {
        return $this->workflow(
            fn () => $this->intakeService->admitCandidate($candidate, $this->actorId($request)),
            'تم قبول المرشح كـ Canonical Evidence. الدليل الآن ACTIVE وينتظر المراجعة.',
        );
    }

    public function createRevision(Request $request, string $evidence): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:4000'],
            'revision_reason' => ['required', 'string', 'max:1000'],
            'handoff_receipt_id' => ['nullable', 'uuid'],
            'source_type' => ['prohibited'],
            'source_id' => ['prohibited'],
            'source_revision' => ['prohibited'],
            'source_digest' => ['prohibited'],
            'selected_material_refs' => ['prohibited'],
            'criterion_scope' => ['sometimes', 'array', 'max:50'],
            'criterion_scope.*' => ['string', 'max:120'],
            'facts' => ['prohibited'],
            'metadata' => ['prohibited'],
        ]);

        return $this->workflow(
            fn () => $this->service->createRevision($evidence, $this->actorId($request), $data),
            'تم إنشاء Evidence Revision جديد وهو الآن المرجع الحالي لـ Evidence.',
        );
    }

    public function transitionLifecycle(Request $request, string $evidence): RedirectResponse
    {
        $data = $request->validate([
            'target_state' => ['required', 'in:WITHDRAWN,SUPERSEDED'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        return $this->workflow(
            fn () => $this->service->transitionLifecycle($evidence, $this->actorId($request), $data['target_state'], $data['reason']),
            'تم تحديث Evidence Lifecycle بنجاح Review و Decision و Mastery history.',
        );
    }

    public function requestReview(Request $request, string $evidence): RedirectResponse
    {
        $data = $request->validate([
            'review_scope_key' => ['sometimes', 'required', 'string', 'max:160'],
            'criterion_refs' => ['sometimes', 'array', 'min:1'],
            'criterion_refs.*' => ['required', 'string', 'max:120'],
            'purpose' => ['sometimes', 'required', 'string', 'max:180'],
        ]);

        return $this->workflow(
            fn () => $this->reviewService->requestReview($evidence, $this->actorId($request), $data),
            'تم طلب Review Request لـ Evidence Revision الحالي. قيد الانتظار للقبول.',
        );
    }

    public function admitReview(Request $request, string $reviewRequest): RedirectResponse
    {
        return $this->workflow(
            fn () => $this->reviewService->admitReviewRequest($reviewRequest, $this->actorId($request)),
            'تم قبول طلب المراجعة وإنشاء Evidence Review رسمي. يمكن إضافة النتائج.',
        );
    }

    public function addFinding(Request $request, string $review): RedirectResponse
    {
        $data = $request->validate([
            'criterion_key' => ['required', 'string', 'max:120'],
            'finding' => ['required', 'in:SATISFIED,PARTIALLY_SATISFIED,NOT_SATISFIED,NOT_ASSESSABLE'],
            'statement' => ['required', 'string', 'max:4000'],
            'supporting_evidence_revision_ids' => ['sometimes', 'array'],
            'supporting_evidence_revision_ids.*' => ['required', 'string', 'uuid'],
        ]);

        return $this->workflow(
            fn () => $this->reviewService->recordFinding(
                $review,
                $this->actorId($request),
                [
                    'criterion_key' => $data['criterion_key'],
                    'finding' => $data['finding'],
                    'statement' => $data['statement'],
                    'supporting_evidence_revision_ids' => $data['supporting_evidence_revision_ids'] ?? [],
                ]
            ),
            'تم تسجيل Review Finding بنجاح. Evidence Revision مدعوم بالنتائج لإصدار Review Decision.',
        );
    }

    public function decideReview(Request $request, string $review): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:ACCEPT,ACCEPT_WITH_LIMITATIONS,MORE_EVIDENCE_REQUIRED,REJECT'],
            'rationale' => ['required', 'string', 'max:4000'],
        ]);

        return $this->workflow(
            fn () => $this->decisionService->recordDecision(
                $review,
                $this->actorId($request),
                [
                    'decision' => $data['decision'],
                    'rationale' => $data['rationale'],
                ]
            ),
            'تم إصدار Review Decision. قد يؤثر على حالة التقييم لإصدار Superseding Decision لاحقاً.',
        );
    }

    public function evaluateMastery(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'capability_id' => ['required', 'string', 'max:100'],
            'policy_revision_id' => ['required', 'uuid'],
            'judgment' => ['required', 'in:NOT_EVALUATED,INSUFFICIENT_EVIDENCE,INCONCLUSIVE,NOT_MASTERED,MASTERED'],
            'freshness_status' => ['required', 'in:CURRENT,REVALIDATION_REQUIRED'],
            'review_decision_ids' => ['present', 'array'],
            'review_decision_ids.*' => ['string', 'uuid'],
            'supporting_evidence_revision_ids' => ['present', 'array'],
            'supporting_evidence_revision_ids.*' => ['string', 'uuid'],
            'contradicting_evidence_revision_ids' => ['present', 'array'],
            'contradicting_evidence_revision_ids.*' => ['string', 'uuid'],
            'rationale' => ['required', 'string', 'max:4000'],
        ]);

        return $this->workflow(function () use ($request, $data): void {
            $actor = $this->actorId($request);
            $this->masteryService->evaluateMastery(
                $actor,
                $data['capability_id'],
                $data['policy_revision_id'],
                $data['judgment'],
                $data['freshness_status'],
                $data['review_decision_ids'],
                $data['supporting_evidence_revision_ids'],
                $data['contradicting_evidence_revision_ids'],
                $data['rationale'],
            );
        }, 'تم حفظ Mastery State. بناءً على Decision/Evidence Revision provenance تم تحديث الإتقان.');
    }

    public function createPortfolio(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'view_scope' => ['nullable', 'string', 'max:120'],
            'grouping' => ['required', 'in:CAPABILITY,REVIEW_DECISION,MASTERY'],
            'filters' => ['sometimes', 'array:lifecycle_states,review_decisions,capability_ids'],
            'filters.lifecycle_states' => ['sometimes', 'array', 'max:3'],
            'filters.lifecycle_states.*' => ['in:ACTIVE,WITHDRAWN,SUPERSEDED'],
            'filters.review_decisions' => ['sometimes', 'array', 'max:5'],
            'filters.review_decisions.*' => ['in:NONE,ACCEPT,ACCEPT_WITH_LIMITATIONS,MORE_EVIDENCE_REQUIRED,REJECT'],
            'filters.capability_ids' => ['sometimes', 'array', 'max:20'],
            'filters.capability_ids.*' => ['string', 'max:100'],
            'annotations' => ['sometimes', 'array:purpose,audience'],
            'annotations.purpose' => ['sometimes', 'string', 'max:500'],
            'annotations.audience' => ['sometimes', 'string', 'max:500'],
        ]);

        return $this->workflow(
            fn () => $this->masteryService->createPortfolio(
                $this->actorId($request),
                $data['name'],
                $data['view_scope'] ?? null,
                $data['grouping'],
                $data['filters'] ?? [],
                $data['annotations'] ?? [],
            ),
            'تم إنشاء Portfolio View يعكس مجموعة مخصصة لـ Evidence و Mastery للبحث.');
    }

    public function addPortfolioEvidence(Request $request, string $portfolio): RedirectResponse
    {
        $data = $request->validate([
            'evidence_id' => ['required', 'string', 'uuid'],
            'annotation' => ['nullable', 'string', 'max:4000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:100000'],
        ]);

        return $this->workflow(
            fn () => $this->masteryService->addPortfolioEvidence(
                $portfolio,
                $data['evidence_id'],
                $this->actorId($request),
                $data['annotation'] ?? null,
                (int) ($data['sort_order'] ?? 0),
            ),
            'تمت إضافة Canonical Evidence إلى Portfolio View مع نص إيضاحي للموثوقية.',
        );
    }

    public function removePortfolioEvidence(Request $request, string $portfolio, string $evidence): RedirectResponse
    {
        return $this->workflow(
            fn () => $this->masteryService->removePortfolioEvidence($portfolio, $evidence, $this->actorId($request)),
            'تمت إزالة Evidence من Portfolio View بمرجعية قوية لحفظ السجل.',
        );
    }

    private function render(Request $request, string $surface): Response
    {
        return Inertia::render('ProgressEvidence/Workspace', [
            'surface' => $surface,
            ...$this->service->workspace($this->actorId($request)),
        ]);
    }

    private function actorId(Request $request): string
    {
        $user = $request->user();
        if (! $user) {
            throw new LogicException('Authenticated actor is required.');
        }

        return (string) $user->getAuthIdentifier();
    }

    /** @param callable(): mixed $operation */
    private function workflow(callable $operation, string $message): RedirectResponse
    {
        try {
            $operation();

            return back(303)->with('status', $message);
        } catch (InvalidArgumentException|LogicException $exception) {
            return back(303)->withErrors(['workflow' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            throw $exception;
        }
    }
}
