<?php

namespace App\Http\Controllers;

use App\Application\Vs002\Vs002Lifecycle;
use App\Application\Vs002\Vs002Workspace;
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

final class Vs002Controller
{
    public function sources(Vs002Workspace $workspace): Response
    {
        return Inertia::render('Vs002/SourceAuthority', ['sources' => $workspace->sources(), 'baseline' => config('vs002.authority_baseline_id')]);
    }

    public function lessonEditor(Vs002Workspace $workspace): Response
    {
        return Inertia::render('Vs002/LessonEditor', ['revisions' => $workspace->revisions(), 'baseline' => config('vs002.authority_baseline_id')]);
    }

    public function restoreLesson(Request $request, LessonRevisionWorkflow $workflow, string $revision): RedirectResponse
    {
        try {
            $workflow->restoreAsDraft($revision, (string) $request->user()->id);
        } catch (LogicException $exception) {
            return back()->withErrors(['revision' => $exception->getMessage()]);
        }

        return back()->with('status', 'أُنشئت مسودة جديدة من النسخة المنشورة مع بقاء المنشور غير قابل للتعديل.');
    }

    public function updateLesson(Request $request, LessonRevisionWorkflow $workflow, string $revision): RedirectResponse
    {
        $validated = $request->validate([
            'lock_version' => ['required', 'integer', 'min:1'],
            'blocks' => ['required', 'array', 'min:1', 'max:24'],
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

        return back()->with('status', 'حُفظت مسودة VS‑002 بالقفل التفاؤلي.');
    }

    public function submitLesson(Request $request, LessonRevisionWorkflow $workflow, string $revision): RedirectResponse
    {
        return $this->lessonTransition(fn () => $workflow->submitForReview($revision, (string) $request->user()->id), 'أُرسلت النسخة إلى المراجعة الصريحة.');
    }

    public function returnLesson(Request $request, LessonRevisionWorkflow $workflow, string $revision): RedirectResponse
    {
        $validated = $request->validate(['rationale' => ['required', 'string', 'min:12', 'max:1000']]);

        return $this->lessonTransition(fn () => $workflow->review($revision, 'RETURNED', (string) $request->user()->id, $validated['rationale']), 'أُعيدت النسخة مع التعليل المحفوظ.');
    }

    public function approveLesson(Request $request, LessonRevisionWorkflow $workflow, string $revision): RedirectResponse
    {
        return $this->lessonTransition(fn () => $workflow->review($revision, 'APPROVED', (string) $request->user()->id), 'اعتُمدت النسخة دون نشر تلقائي.');
    }

    public function publishLesson(Request $request, LessonRevisionWorkflow $workflow, string $revision): RedirectResponse
    {
        return $this->lessonTransition(fn () => $workflow->publish($revision, (string) $request->user()->id, config('vs002.required_claim_ids')), 'نُشرت النسخة بعد تحقق خط أساس السلطة التقنية.');
    }

    public function lesson(Vs002Workspace $workspace): Response
    {
        return Inertia::render('Vs002/LessonReader', ['lesson' => $workspace->lesson()]);
    }

    public function practice(Request $request, Vs002Workspace $workspace): Response
    {
        return Inertia::render('Vs002/MicroPractice', $workspace->practice((string) $request->user()->id));
    }

    public function submitPractice(Request $request, Vs002Lifecycle $lifecycle): RedirectResponse
    {
        $validated = $request->validate([
            'actor' => ['required', Rule::in(['SIM-ALICE', 'SIM-BOB', 'SIM-ADMIN'])],
            'resource_owner' => ['required', Rule::in(['SIM-ALICE', 'SIM-BOB'])],
            'requested_action' => ['required', Rule::in(['case_file.read', 'case_file.update'])],
            'missing_trust_boundary' => ['required', Rule::in(['authentication_context', 'resource_lookup', 'authorization_policy', 'response_serialization'])],
            'expected_policy_decision' => ['required', Rule::in(['ALLOW', 'DENY', 'ALLOW_AUTHENTICATED_ONLY'])],
            'expected_http_response_class' => ['required', Rule::in(['2xx', '4xx', '5xx'])],
            'decisive_rule' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'safe_detection_field' => ['required', Rule::in(['trace_digest', 'password', 'session_token', 'request_body'])],
            'rationale' => ['required', 'string', 'min:12', 'max:1000'],
        ]);
        $result = $lifecycle->submitPractice((string) $request->user()->id, $validated);

        return back()->with('status', $result['failure_class'] === null ? 'الإجابة المنظمة مطابقة لمفتاح الإصدار.' : "سُجل فشل فعلي محدد: {$result['failure_class']}.");
    }

    public function lab(Vs002Workspace $workspace): Response
    {
        return Inertia::render('Vs002/GuidedRequestLab', $workspace->lab());
    }

    public function runCase(Request $request, Vs002Lifecycle $lifecycle, Vs002Workspace $workspace): RedirectResponse
    {
        $caseIds = [];
        $cases = $workspace->lab()['scenario']['cases'] ?? [];
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
        $key = $validated['idempotency_key'] ?: sprintf('vs002:ui:%s:%s:%s', $request->user()->id, $validated['case_id'], Str::uuid7());
        try {
            $result = $lifecycle->runCase($validated['case_id'], (int) $validated['seed'], $key, (string) $request->user()->id);
        } catch (IdempotencyConflict|LogicException $exception) {
            return back()->withErrors(['run' => $exception->getMessage()]);
        }

        return back()->with('status', "اكتمل الطلب الاصطناعي بنتيجة {$result['run']['outcome']}؛ مصدر الدليل SIMULATED فقط.");
    }

    public function replay(Request $request, Vs002Lifecycle $lifecycle, string $run): RedirectResponse
    {
        $record = $lifecycle->replay($run, sprintf('vs002:replay:%s:%s', $request->user()->id, Str::uuid7()));

        return back()->with('status', $record['digest_match'] ? 'إعادة التشغيل المثبتة مطابقة حتميًا.' : 'عدم تطابق إعادة التشغيل سُجل كمحفز مراجعة.');
    }

    public function remediate(Vs002Lifecycle $lifecycle): RedirectResponse
    {
        $policy = $lifecycle->remediate();

        return back()->with('status', "أُنشئت أو استُعيدت سياسة الإصلاح غير القابلة للتعديل: revision {$policy['revision']}.");
    }

    public function verify(Request $request, Vs002Lifecycle $lifecycle, string $finding): RedirectResponse
    {
        $validated = $request->validate([
            'vulnerable_run_id' => ['required', 'uuid'],
            'remediation_policy_revision_id' => ['required', 'uuid'],
            'idempotency_key' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);
        try {
            $result = $lifecycle->verify($finding, $validated['vulnerable_run_id'], $validated['remediation_policy_revision_id'], $validated['idempotency_key'] ?: sprintf('vs002:verify:%s:%s', $request->user()->id, Str::uuid7()), (string) $request->user()->id);
        } catch (LogicException $exception) {
            return back()->withErrors(['verification' => $exception->getMessage()]);
        }

        return back()->with('status', "اكتمل التحقق بنتيجة {$result['run']['outcome']} وربط التشغيلين وسياسة الإصلاح.");
    }

    public function evidence(Request $request, Vs002Workspace $workspace): Response
    {
        return Inertia::render('Vs002/EvidenceMastery', $workspace->evidence((string) $request->user()->id));
    }

    public function decideEvidence(Request $request, Vs002Lifecycle $lifecycle, string $evidence): RedirectResponse
    {
        $validated = $request->validate(['decision' => ['required', Rule::in(['ACCEPTED', 'REJECTED', 'NEEDS_REVIEW'])], 'rationale' => ['required', 'string', 'min:12', 'max:1000']]);
        $lifecycle->decideEvidence($evidence, $validated['decision'], $validated['rationale'], (string) $request->user()->id);

        return back()->with('status', 'سُجل قرار الدليل مع فصل المتعلم عن المراجع.');
    }

    public function evaluateMastery(Request $request, Vs002Lifecycle $lifecycle): RedirectResponse
    {
        $state = $lifecycle->evaluateMastery((string) $request->user()->id);

        return back()->with('status', "أعيد تقييم الإتقان المتوازن للممثل الحالي: {$state['status']}.");
    }

    private function lessonTransition(callable $transition, string $status): RedirectResponse
    {
        try {
            $transition();
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['revision' => $exception->getMessage()]);
        }

        return back()->with('status', $status);
    }
}
