<?php

namespace App\Http\Controllers;

use App\Application\Vs001\Vs001Lifecycle;
use App\Application\Vs001\Vs001Workspace;
use App\Modules\Knowledge\Publication\LessonRevisionWorkflow;
use App\Modules\Simulator\Application\IdempotencyConflict;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class Vs001Controller
{
    public function sourceReview(Vs001Workspace $workspace): Response
    {
        return Inertia::render('Vs001/SourceReview', [
            'sources' => $workspace->sources(),
            'baseline' => config('vs001.authority_baseline_id'),
        ]);
    }

    public function lessonEditor(Vs001Workspace $workspace): Response
    {
        return Inertia::render('Vs001/LessonEditor', [
            'revisions' => $workspace->revisions(),
            'baseline' => config('vs001.authority_baseline_id'),
        ]);
    }

    public function restoreLesson(Request $request, LessonRevisionWorkflow $workflow, string $revision): RedirectResponse
    {
        try {
            $workflow->restoreAsDraft($revision, (string) $request->user()->id);
        } catch (LogicException $exception) {
            return back()->withErrors(['revision' => $exception->getMessage()]);
        }

        return back()->with('status', 'تم إنشاء مسودة جديدة من النسخة المنشورة دون تعديلها.');
    }

    public function updateLesson(Request $request, LessonRevisionWorkflow $workflow, string $revision): RedirectResponse
    {
        $validated = $request->validate([
            'lock_version' => ['required', 'integer', 'min:1'],
            'blocks' => ['required', 'array', 'min:1', 'max:20'],
            'blocks.*' => ['array:type,body'],
            'blocks.*.type' => ['required', Rule::in(['heading', 'paragraph', 'callout', 'rules', 'boundaries', 'code', 'request', 'response', 'log'])],
            'blocks.*.body' => ['required', 'string', 'max:4000'],
            'citations' => ['required', 'array', 'min:1', 'max:20'],
            'citations.*' => ['required', 'string', 'max:80'],
        ]);
        try {
            $workflow->updateDraft($revision, (int) $validated['lock_version'], $validated['blocks'], $validated['citations'], (string) $request->user()->id);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['revision' => $exception->getMessage()]);
        }

        return back()->with('status', 'حُفظت المسودة باستخدام قفل تفاؤلي.');
    }

    public function submitLesson(Request $request, LessonRevisionWorkflow $workflow, string $revision): RedirectResponse
    {
        try {
            $workflow->submitForReview($revision, (string) $request->user()->id);
        } catch (LogicException $exception) {
            return back()->withErrors(['revision' => $exception->getMessage()]);
        }

        return back()->with('status', 'أُرسلت المسودة للمراجعة الصريحة.');
    }

    public function returnLesson(Request $request, LessonRevisionWorkflow $workflow, string $revision): RedirectResponse
    {
        $validated = $request->validate(['rationale' => ['required', 'string', 'min:12', 'max:1000']]);
        try {
            $workflow->review($revision, 'RETURNED', (string) $request->user()->id, $validated['rationale']);
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['revision' => $exception->getMessage()]);
        }

        return back()->with('status', 'أُعيدت النسخة إلى المسودة مع تعليل محفوظ.');
    }

    public function approveLesson(Request $request, LessonRevisionWorkflow $workflow, string $revision): RedirectResponse
    {
        try {
            $workflow->review($revision, 'APPROVED', (string) $request->user()->id);
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['revision' => $exception->getMessage()]);
        }

        return back()->with('status', 'اعتُمدت النسخة للمسار المنشور دون نشر تلقائي.');
    }

    public function publishLesson(Request $request, LessonRevisionWorkflow $workflow, string $revision): RedirectResponse
    {
        try {
            $workflow->publish($revision, (string) $request->user()->id, config('vs001.required_claim_ids'));
        } catch (LogicException $exception) {
            return back()->withErrors(['revision' => $exception->getMessage()]);
        }

        return back()->with('status', 'نُشرت نسخة غير قابلة للتعديل بعد تحقق السلطة.');
    }

    public function lessonReader(Vs001Workspace $workspace): Response
    {
        return Inertia::render('Vs001/LessonReader', ['lesson' => $workspace->lesson()]);
    }

    public function microPractice(Request $request, Vs001Workspace $workspace): Response
    {
        return Inertia::render('Vs001/MicroPractice', $workspace->practice((string) $request->user()->id));
    }

    public function submitPractice(Request $request, Vs001Lifecycle $lifecycle): RedirectResponse
    {
        $validated = $request->validate([
            'selected_outcome' => ['required', Rule::in(['ALLOW', 'DENY', 'INSUFFICIENT_STATE', 'UNSUPPORTED_STATE'])],
            'decisive_step_id' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'decisive_ace_id' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'relevant_requested_mask' => ['required', 'string', 'max:32', 'regex:/^0x[0-9A-Fa-f]{8}$/'],
            'remaining_mask' => ['required', 'string', 'max:32', 'regex:/^0x[0-9A-Fa-f]{8}$/'],
            'rationale' => ['present', 'nullable', 'string', 'max:1000'],
        ]);
        $validated['rationale'] ??= '';
        $result = $lifecycle->submitPractice((string) $request->user()->id, $validated);

        return back()->with('status', $result['failure_class'] === null ? 'إجابة منظمة صحيحة مقابل مفتاح الإصدار.' : "سُجل فشل فعلي محدد: {$result['failure_class']}.");
    }

    public function guidedLab(Vs001Workspace $workspace): Response
    {
        return Inertia::render('Vs001/GuidedLab', $workspace->lab());
    }

    public function runCase(Request $request, Vs001Lifecycle $lifecycle, Vs001Workspace $workspace): RedirectResponse
    {
        $lab = $workspace->lab();
        $cases = $lab['scenario']['cases'] ?? [];
        $caseIds = [];
        if (is_array($cases)) {
            foreach ($cases as $case) {
                if (is_array($case) && is_string($case['case_id'] ?? null)) {
                    $caseIds[] = $case['case_id'];
                }
            }
        }
        $validated = $request->validate([
            'case_id' => ['required', 'string', Rule::in($caseIds)],
            'seed' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'idempotency_key' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);
        $key = $validated['idempotency_key'] ?: sprintf('ui:%s:%s:%s', $request->user()->id, $validated['case_id'], Str::uuid7());
        try {
            $result = $lifecycle->runCase($validated['case_id'], (int) $validated['seed'], $key, (string) $request->user()->id);
        } catch (IdempotencyConflict $exception) {
            return back()->withErrors(['idempotency_key' => $exception->getMessage()]);
        }

        return back()->with('status', "اكتمل التشغيل بنتيجة {$result['run']['outcome']}. الدليل SIMULATED فقط.");
    }

    public function replay(Request $request, Vs001Lifecycle $lifecycle, string $run): RedirectResponse
    {
        $record = $lifecycle->replay($run, sprintf('ui:replay:%s:%s', $request->user()->id, Str::uuid7()));

        return back()->with('status', $record['digest_match'] ? 'إعادة التشغيل مثبتة بالمراجعات ومطابقة حتميًا.' : 'فشلت مطابقة إعادة التشغيل؛ راجع الأثر.');
    }

    public function evidenceMastery(Request $request, Vs001Workspace $workspace): Response
    {
        return Inertia::render('Vs001/EvidenceMastery', $workspace->evidenceMastery((string) $request->user()->id));
    }

    public function decideEvidence(Request $request, Vs001Lifecycle $lifecycle, string $evidence): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['ACCEPTED', 'REJECTED', 'NEEDS_REVIEW'])],
            'rationale' => ['required', 'string', 'min:12', 'max:1000'],
        ]);
        $lifecycle->decideEvidence($evidence, $validated['decision'], $validated['rationale'], (string) $request->user()->id);

        return back()->with('status', 'سُجل قرار الدليل مع فصل learner actor عن reviewer actor.');
    }

    public function evaluateMastery(Request $request, Vs001Lifecycle $lifecycle): RedirectResponse
    {
        $state = $lifecycle->evaluateMastery((string) $request->user()->id);

        return back()->with('status', "أعيد تقييم الإتقان للممثل الحالي فقط: {$state['status']}.");
    }
}
