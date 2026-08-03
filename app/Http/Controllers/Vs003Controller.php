<?php

namespace App\Http\Controllers;

use App\Application\Vs003\Vs003Lifecycle;
use App\Modules\Simulator\Application\IdempotencyConflict;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

final class Vs003Controller
{
    public function lab(Request $request, Vs003Lifecycle $lifecycle): Response
    {
        $actorId = (string) $request->user()->id;

        return Inertia::render('Vs003/AuthenticationInvestigation', [
            'cases' => config('vs003.case_ids'),
            'outcomes' => config('vs003.outcomes'),
            'telemetryHealthValues' => config('vs003.telemetry_health_values'),
            'alternativeHypotheses' => config('vs003.alternative_hypotheses'),
            'evidenceOrigin' => 'SIMULATED',
            'baseline' => config('vs003.authority_baseline_id'),
            'requestKeys' => [
                'run' => 'vs003:ui:run:'.Str::uuid7(),
                'verification' => 'vs003:ui:verify:'.Str::uuid7(),
            ],
            'workspace' => $lifecycle->workspace($actorId),
        ]);
    }

    public function run(Request $request, Vs003Lifecycle $lifecycle): RedirectResponse
    {
        $data = $request->validate([
            'case_id' => ['required', Rule::in(config('vs003.case_ids'))],
            'seed' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'idempotency_key' => ['required', 'string', 'min:12', 'max:200', 'regex:/^[A-Za-z0-9:._-]+$/'],
        ]);

        return $this->bounded('run', function () use ($data, $request, $lifecycle): string {
            $result = $lifecycle->runCase(
                $data['case_id'],
                (int) $data['seed'],
                $data['idempotency_key'],
                (string) $request->user()->id,
            );

            return "تم تسجيل دليل SIMULATED بنتيجة {$result['run']['outcome']} دون تنفيذ حي.";
        });
    }

    public function triage(Request $request, Vs003Lifecycle $lifecycle): RedirectResponse
    {
        $data = $request->validate([
            'run_id' => ['required', 'uuid'],
            'outcome' => ['required', Rule::in(config('vs003.outcomes'))],
            'rationale' => ['required', 'string', 'min:12', 'max:1000'],
        ]);

        return $this->bounded('triage', function () use ($data, $request, $lifecycle): string {
            $record = $lifecycle->triage(
                $data['run_id'],
                (string) $request->user()->id,
                $data['outcome'],
                $data['rationale'],
            );

            return "تم تثبيت قرار الفرز {$record['outcome']} للممثل الحالي.";
        });
    }

    public function preserve(Request $request, Vs003Lifecycle $lifecycle): RedirectResponse
    {
        $data = $request->validate(['run_id' => ['required', 'uuid']]);

        return $this->bounded('evidence', function () use ($data, $request, $lifecycle): string {
            $lifecycle->preserveEvidence($data['run_id'], (string) $request->user()->id);

            return 'تم حفظ نسخة SIMULATED أصلية مع سلسلة حيازة وحدود معلنة.';
        });
    }

    public function proposeContainment(Request $request, Vs003Lifecycle $lifecycle): RedirectResponse
    {
        $data = $request->validate([
            'run_id' => ['required', 'uuid'],
            'expected_effect' => ['required', 'string', 'min:12', 'max:500'],
            'risk' => ['required', 'string', 'min:12', 'max:500'],
            'rollback_condition' => ['required', 'string', 'min:12', 'max:500'],
        ]);

        return $this->bounded('containment', function () use ($data, $request, $lifecycle): string {
            $lifecycle->proposeContainment(
                $data['run_id'],
                (string) $request->user()->id,
                $data['expected_effect'],
                $data['risk'],
                $data['rollback_condition'],
            );

            return 'تم إنشاء مقترح احتواء غير تنفيذي؛ لا يوجد إجراء حي أو تلقائي.';
        });
    }

    public function approveContainment(
        string $proposal,
        Request $request,
        Vs003Lifecycle $lifecycle,
    ): RedirectResponse {
        return $this->bounded('containment', function () use ($proposal, $request, $lifecycle): string {
            $lifecycle->approveContainment($proposal, (string) $request->user()->id);

            return 'تمت الموافقة الصريحة على المقترح الاصطناعي فقط.';
        });
    }

    public function verifyContainment(
        string $proposal,
        Request $request,
        Vs003Lifecycle $lifecycle,
    ): RedirectResponse {
        $data = $request->validate([
            'original_run_id' => ['required', 'uuid'],
            'idempotency_key' => ['required', 'string', 'min:12', 'max:200', 'regex:/^[A-Za-z0-9:._-]+$/'],
        ]);

        return $this->bounded('verification', function () use (
            $proposal,
            $data,
            $request,
            $lifecycle,
        ): string {
            $result = $lifecycle->verifyApprovedControl(
                $proposal,
                $data['original_run_id'],
                (string) $request->user()->id,
                $data['idempotency_key'],
            );

            $status = $result['replay']['passed'] ? 'PASS' : 'FAIL';

            return "اكتملت إعادة التحقق الاصطناعية المثبتة على المراجعات: {$status}.";
        });
    }

    public function practice(Request $request, Vs003Lifecycle $lifecycle): RedirectResponse
    {
        $data = $request->validate([
            'outcome' => ['required', Rule::in(config('vs003.outcomes'))],
            'telemetry_health' => ['required', Rule::in(config('vs003.telemetry_health_values'))],
            'alternative_hypothesis' => ['required', Rule::in(config('vs003.alternative_hypotheses'))],
        ]);

        return $this->bounded('practice', function () use ($data, $request, $lifecycle): string {
            $result = $lifecycle->submitPractice((string) $request->user()->id, $data);

            return $result['failure_class'] === null
                ? 'تم قبول الإجابة المنظمة.'
                : "تم تسجيل فشل تعلم فعلي: {$result['failure_class']}.";
        });
    }

    public function mastery(Request $request, Vs003Lifecycle $lifecycle): RedirectResponse
    {
        return $this->bounded('mastery', function () use ($request, $lifecycle): string {
            $state = $lifecycle->evaluateMastery((string) $request->user()->id);

            return "حالة الإتقان الحالية: {$state['status']}.";
        });
    }

    /** @param callable():string $operation */
    private function bounded(string $errorKey, callable $operation): RedirectResponse
    {
        try {
            return back()->with('status', $operation());
        } catch (IdempotencyConflict|LogicException $exception) {
            return back()->withErrors([$errorKey => $exception->getMessage()]);
        }
    }
}
