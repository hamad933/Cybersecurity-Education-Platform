<?php

namespace App\Modules\Platform\SystemOperations;

use App\Http\Controllers\Controller;
use App\Modules\Platform\Audit\AuditWriter;
use App\Modules\Platform\Processing\ProcessingRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SystemOperationsController extends Controller
{
    public function __construct(private readonly SystemOperationsState $state) {}

    public function health(Request $request): Response
    {
        return $this->render($request, 'health');
    }

    public function processing(Request $request): Response
    {
        return $this->render($request, 'processing');
    }

    public function validation(Request $request): Response
    {
        return $this->render($request, 'validation');
    }

    public function aiBridge(Request $request): Response
    {
        return $this->render($request, 'ai-bridge');
    }

    public function backups(Request $request): Response
    {
        return $this->render($request, 'backups');
    }

    public function audit(Request $request): Response
    {
        return $this->render($request, 'audit');
    }

    public function releases(Request $request): Response
    {
        return $this->render($request, 'releases');
    }

    public function configuration(Request $request): Response
    {
        return $this->render($request, 'configuration');
    }

    public function cancelProcessingRun(Request $request, string $run, AuditWriter $audit): RedirectResponse
    {
        $processingRun = ProcessingRun::query()->findOrFail($run);
        $previousStatus = (string) $processingRun->status;

        if (! in_array($previousStatus, ['pending', 'running'], true)) {
            return back()->withErrors([
                'processing' => "Processing run cannot be cancelled from state {$previousStatus}.",
            ]);
        }

        $processingRun->transitionTo('cancelled');
        $actorId = (string) $request->user()->getAuthIdentifier();

        $audit->append([
            'actor_identifier' => $actorId,
            'action' => 'processing.run.cancelled',
            'target_type' => 'processing_run',
            'target_identifier' => (string) $processingRun->id,
            'correlation_id' => (string) $processingRun->id,
            'outcome' => 'success',
            'safe_metadata' => [
                'previous_status' => $previousStatus,
                'processing_type' => (string) $processingRun->type,
                'input_digest' => (string) $processingRun->input_digest,
            ],
        ]);

        return back()->with('success', 'Processing run cancellation recorded.');
    }

    private function render(Request $request, string $surface): Response
    {
        $actorId = (string) $request->user()->getAuthIdentifier();

        return Inertia::render('SystemOperations/Workspace', [
            'surface' => $surface,
            'state' => $this->state->forSurface($surface, $actorId),
        ]);
    }
}
