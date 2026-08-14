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

    public function index(Request $request): Response { return $this->render($request, 'evidence'); }
    public function reviews(Request $request): Response { return $this->render($request, 'reviews'); }
    public function mastery(Request $request): Response { return $this->render($request, 'mastery'); }
    public function portfolio(Request $request): Response { return $this->render($request, 'portfolio'); }

    public function intake(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'source_type' => ['required', 'string', 'max:64'], 'source_id' => ['required', 'string', 'max:160'],
            'source_revision' => ['nullable', 'string', 'max:80'], 'source_digest' => ['required', 'regex:/^[A-Fa-f0-9]{64}$/'],
            'capability_id' => ['required', 'string', 'max:100'], 'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:4000'], 'facts' => ['sometimes', 'array'], 'metadata' => ['sometimes', 'array'],
        ]);
        return $this->workflow(function () use ($request, $data): void {
            $actor = $this->actorId($request); $this->service->intakeCandidate($actor, $actor, $data);
        }, 'تم إدخال المصدر كـ Candidate Evidence دون تغيير سجل المصدر.');
    }

    public function admitCandidate(Request $request, string $candidate): RedirectResponse
    {
        return $this->workflow(fn () => $this->service->admitCandidate($candidate, $this->actorId($request)), 'تم Admission وإنشاء Evidence Revision 1 مختومة. لم تُنشأ مراجعة تلقائيًا.');
    }

    public function createRevision(Request $request, string $evidence): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:180'], 'summary' => ['required', 'string', 'max:4000'], 'facts' => ['sometimes', 'array']]);
        return $this->workflow(fn () => $this->service->createRevision($evidence, $this->actorId($request), $data), 'تم إنشاء Revision جديدة مختومة مع إبقاء التاريخ السابق دون تعديل.');
    }

    public function transitionLifecycle(Request $request, string $evidence): RedirectResponse
    {
        $data = $request->validate(['lifecycle_state' => ['required', 'in:ACTIVE,WITHDRAWN,SUPERSEDED']]);
        return $this->workflow(fn () => $this->service->transitionLifecycle($evidence, $this->actorId($request), $data['lifecycle_state']), 'تم تحديث Evidence Lifecycle دون تغيير Review Status أو Review Decision.');
    }

    public function requestReview(Request $request, string $evidence): RedirectResponse
    {
        return $this->workflow(fn () => $this->service->requestReview($evidence, $this->actorId($request)), 'تم إنشاء Review Request. ما زال Review Admission خطوة مستقلة.');
    }

    public function admitReview(Request $request, string $reviewRequest): RedirectResponse
    {
        return $this->workflow(fn () => $this->service->admitReviewRequest($reviewRequest, $this->actorId($request)), 'تم قبول Review Request وبدء Evidence Review رسمي.');
    }

    public function addFinding(Request $request, string $review): RedirectResponse
    {
        $data = $request->validate(['criterion_key' => ['required', 'string', 'max:120'], 'finding' => ['required', 'in:SATISFIED,PARTIALLY_SATISFIED,NOT_SATISFIED,NOT_ASSESSABLE'], 'statement' => ['required', 'string', 'max:4000']]);
        return $this->workflow(fn () => $this->service->recordFinding($review, $this->actorId($request), $data['criterion_key'], $data['finding'], $data['statement']), 'تم تسجيل Finding مستقل عن Review Decision.');
    }

    public function decideReview(Request $request, string $review): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:ACCEPT,ACCEPT_WITH_LIMITATIONS,MORE_EVIDENCE_REQUIRED,REJECT'], 'rationale' => ['required', 'string', 'max:4000']]);
        return $this->workflow(fn () => $this->service->recordReviewDecision($review, $this->actorId($request), $data['decision'], $data['rationale']), 'تم تسجيل Review Decision وأصبح هو القرار الفعّال للدليل دون تغيير Lifecycle.');
    }

    public function evaluateMastery(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'capability_id' => ['required', 'string', 'max:100'], 'policy_revision_id' => ['required', 'string', 'max:120'],
            'judgment' => ['required', 'in:NOT_EVALUATED,INSUFFICIENT_EVIDENCE,INCONCLUSIVE,NOT_MASTERED,MASTERED'],
            'freshness_status' => ['required', 'in:CURRENT,REVALIDATION_REQUIRED'], 'supporting_evidence_ids' => ['present', 'array'],
            'supporting_evidence_ids.*' => ['string', 'uuid'], 'contradicting_evidence_ids' => ['sometimes', 'array'],
            'contradicting_evidence_ids.*' => ['string', 'uuid'], 'rationale' => ['required', 'string', 'max:4000'],
        ]);
        return $this->workflow(function () use ($request, $data): void {
            $actor = $this->actorId($request);
            $this->service->evaluateMastery($actor, $data['capability_id'], $actor, $data['policy_revision_id'], $data['judgment'], $data['freshness_status'], $data['supporting_evidence_ids'], $data['contradicting_evidence_ids'] ?? [], $data['rationale']);
        }, 'تم تسجيل Mastery Evaluation مع Judgment وFreshness كبُعدين مستقلين.');
    }

    public function createPortfolio(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:180'], 'view_scope' => ['nullable', 'string', 'max:120'], 'grouping' => ['required', 'string', 'max:80'], 'filters' => ['sometimes', 'array'], 'annotations' => ['sometimes', 'array']]);
        return $this->workflow(fn () => $this->service->createPortfolio($this->actorId($request), $data['name'], $data['view_scope'] ?? null, $data['grouping'], $data['filters'] ?? [], $data['annotations'] ?? []), 'تم إنشاء Portfolio View كإسقاط محفوظ، وليس كمستودع Evidence ثانٍ.');
    }

    public function addPortfolioEvidence(Request $request, string $portfolio): RedirectResponse
    {
        $data = $request->validate(['evidence_id' => ['required', 'string', 'uuid'], 'annotation' => ['nullable', 'string', 'max:4000'], 'sort_order' => ['sometimes', 'integer', 'min:0', 'max:100000']]);
        return $this->workflow(fn () => $this->service->addEvidenceToPortfolio($portfolio, $data['evidence_id'], $this->actorId($request), $data['annotation'] ?? null, (int) ($data['sort_order'] ?? 0)), 'تمت إضافة مرجع Evidence الحاكم إلى Portfolio.');
    }

    public function removePortfolioEvidence(Request $request, string $portfolio, string $evidence): RedirectResponse
    {
        return $this->workflow(fn () => $this->service->removeEvidenceFromPortfolio($portfolio, $evidence, $this->actorId($request)), 'تمت إزالة المرجع من Portfolio فقط؛ Evidence الحاكم ما زال موجودًا.');
    }

    private function render(Request $request, string $surface): Response
    {
        return Inertia::render('ProgressEvidence/Workspace', ['surface' => $surface, ...$this->service->workspace($this->actorId($request))]);
    }

    private function actorId(Request $request): string
    {
        $user = $request->user();
        if (! $user) throw new LogicException('Authenticated actor is required.');
        return (string) $user->getAuthIdentifier();
    }

    /** @param callable():mixed $operation */
    private function workflow(callable $operation, string $message): RedirectResponse
    {
        try { $operation(); return back(303)->with('status', $message); }
        catch (InvalidArgumentException|LogicException $exception) { return back(303)->withErrors(['workflow' => $exception->getMessage()]); }
        catch (Throwable $exception) { report($exception); throw $exception; }
    }
}
