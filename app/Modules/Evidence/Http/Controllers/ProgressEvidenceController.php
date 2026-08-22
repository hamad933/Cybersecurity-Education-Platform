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
use Throwable;

final class ProgressEvidenceController extends Controller
{
    public function __construct(private readonly ProgressEvidenceService $service) {}

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
            'source_type' => ['required', 'string', 'max:64'],
            'source_id' => ['required', 'string', 'max:160'],
            'source_revision' => ['required', 'string', 'max:80'],
            'source_digest' => ['required', 'regex:/^[A-Fa-f0-9]{64}$/'],
            'selected_material_refs' => ['required', 'array', 'min:1'],
            'selected_material_refs.*' => ['required', 'string', 'max:240'],
            'capability_id' => ['required', 'string', 'max:100'],
            'evidence_claim' => ['required', 'string', 'max:4000'],
            'criterion_scope' => ['present', 'array'],
            'criterion_scope.*' => ['string', 'max:240'],
            'governed_purpose' => ['required', 'string', 'max:180'],
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:4000'],
            'facts' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
        ]);

        return $this->workflow(function () use ($request, $data): void {
            $actor = $this->actorId($request);
            $this->service->intakeCandidate($actor, $actor, $data);
        }, 'تم إنشاء Candidate Evidence في حالة RECEIVED مع تثبيت المصدر والهوية الدلالية؛ لم تُنشأ Evidence بعد.');
    }

    public function transitionCandidate(Request $request, string $candidate): RedirectResponse
    {
        $data = $request->validate([
            'state' => ['required', 'in:DRAFT,PREPARED,SUBMITTED_FOR_INTAKE,RETURNED_FOR_CONTEXT,DECLINED,WITHDRAWN'],
        ]);

        return $this->workflow(
            fn () => $this->service->transitionCandidate($candidate, $this->actorId($request), $data['state']),
            'تم تنفيذ انتقال Candidate Evidence المسموح به دون تجاوز Admission.',
        );
    }

    public function admitCandidate(Request $request, string $candidate): RedirectResponse
    {
        return $this->workflow(
            fn () => $this->service->admitCandidate($candidate, $this->actorId($request)),
            'تم Admission وإنشاء Evidence Revision 1 مختومة. لم تُنشأ Review أو Mastery تلقائيًا.',
        );
    }

    public function createRevision(Request $request, string $evidence): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:4000'],
            'revision_reason' => ['required', 'string', 'max:1000'],
            'source_revision' => ['sometimes', 'required', 'string', 'max:80'],
            'source_digest' => ['sometimes', 'required', 'regex:/^[A-Fa-f0-9]{64}$/'],
            'selected_material_refs' => ['sometimes', 'array', 'min:1'],
            'selected_material_refs.*' => ['required', 'string', 'max:240'],
            'criterion_scope' => ['sometimes', 'array'],
            'criterion_scope.*' => ['string', 'max:240'],
            'facts' => ['sometimes', 'array'],
        ]);

        return $this->workflow(
            fn () => $this->service->createRevision($evidence, $this->actorId($request), $data),
            'تم إنشاء Superseding Evidence Revision مختومة مع إبقاء كل Revision سابقة دون تعديل.',
        );
    }

    public function transitionLifecycle(Request $request, string $evidence): RedirectResponse
    {
        $data = $request->validate([
            'lifecycle_state' => ['required', 'in:ACTIVE,WITHDRAWN,SUPERSEDED'],
        ]);

        return $this->workflow(
            fn () => $this->service->transitionLifecycle($evidence, $this->actorId($request), $data['lifecycle_state']),
            'تم تحديث Evidence Lifecycle دون حذف Review أو Decision أو Mastery history.',
        );
    }

    public function requestReview(Request $request, string $evidence): RedirectResponse
    {
        $data = $request->validate([
            'review_scope_key' => ['sometimes', 'required', 'string', 'max:160'],
            'criterion_refs' => ['sometimes', 'array', 'min:1'],
            'criterion_refs.*' => ['required', 'string', 'max:240'],
            'purpose' => ['sometimes', 'required', 'string', 'max:180'],
        ]);

        return $this->workflow(
            fn () => $this->service->requestReview($evidence, $this->actorId($request), $data),
            'تم إنشاء Review Request وتثبيت Evidence Revision ونطاق المراجعة. لم يبدأ الحكم بعد.',
        );
    }

    public function admitReview(Request $request, string $reviewRequest): RedirectResponse
    {
        return $this->workflow(
            fn () => $this->service->admitReviewRequest($reviewRequest, $this->actorId($request)),
            'تم تعيين المراجع وبدء Evidence Review الرسمي ضمن حدود المالك الحالي.',
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
            fn () => $this->service->recordFinding(
                $review,
                $this->actorId($request),
                $data['criterion_key'],
                $data['finding'],
                $data['statement'],
                $data['supporting_evidence_revision_ids'] ?? [],
            ),
            'تم تسجيل Review Finding بمراجع Evidence Revision محكومة، مستقلًا عن Review Decision.',
        );
    }

    public function decideReview(Request $request, string $review): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:ACCEPT,ACCEPT_WITH_LIMITATIONS,MORE_EVIDENCE_REQUIRED,REJECT'],
            'rationale' => ['required', 'string', 'max:4000'],
        ]);

        return $this->workflow(
            fn () => $this->service->recordReviewDecision(
                $review,
                $this->actorId($request),
                $data['decision'],
                $data['rationale'],
            ),
            'تم إصدار Review Decision مختوم؛ أي قرار لاحق يجب أن يكون Superseding Decision جديدًا.',
        );
    }

    public function evaluateMastery(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'capability_id' => ['required', 'string', 'max:100'],
            'policy_revision_id' => ['required', 'string', 'max:120'],
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
            $this->service->evaluateMastery(
                $actor,
                $data['capability_id'],
                $actor,
                $data['policy_revision_id'],
                $data['judgment'],
                $data['freshness_status'],
                $data['review_decision_ids'],
                $data['supporting_evidence_revision_ids'],
                $data['contradicting_evidence_revision_ids'],
                $data['rationale'],
            );
        }, 'تم إلحاق Mastery State جديدة مع Decision/Evidence Revision provenance دقيقة؛ لم تُعدّل الحالة التاريخية السابقة.');
    }

    public function createPortfolio(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'view_scope' => ['nullable', 'string', 'max:120'],
            'grouping' => ['required', 'string', 'max:80'],
            'filters' => ['sometimes', 'array'],
            'annotations' => ['sometimes', 'array'],
        ]);

        return $this->workflow(
            fn () => $this->service->createPortfolio(
                $this->actorId($request),
                $data['name'],
                $data['view_scope'] ?? null,
                $data['grouping'],
                $data['filters'] ?? [],
                $data['annotations'] ?? [],
            ),
            'تم إنشاء Portfolio View كإسقاط محفوظ، وليس كمستودع Evidence أو Mastery ثانٍ.',
        );
    }

    public function addPortfolioEvidence(Request $request, string $portfolio): RedirectResponse
    {
        $data = $request->validate([
            'evidence_id' => ['required', 'string', 'uuid'],
            'annotation' => ['nullable', 'string', 'max:4000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:100000'],
        ]);

        return $this->workflow(
            fn () => $this->service->addEvidenceToPortfolio(
                $portfolio,
                $data['evidence_id'],
                $this->actorId($request),
                $data['annotation'] ?? null,
                (int) ($data['sort_order'] ?? 0),
            ),
            'تمت إضافة مرجع Canonical Evidence إلى Portfolio View دون نسخ الحقيقة القانونية.',
        );
    }

    public function removePortfolioEvidence(Request $request, string $portfolio, string $evidence): RedirectResponse
    {
        return $this->workflow(
            fn () => $this->service->removeEvidenceFromPortfolio($portfolio, $evidence, $this->actorId($request)),
            'تمت إزالة المرجع من Portfolio View فقط؛ بقيت Evidence وMastery history كما هي.',
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
