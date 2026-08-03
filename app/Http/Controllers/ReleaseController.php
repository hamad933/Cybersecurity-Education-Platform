<?php

namespace App\Http\Controllers;

use App\Modules\Evidence\Application\ExternalEvidenceImportService;
use App\Modules\Learning\Application\DailyQueueService;
use App\Modules\ManualAiBridge\Application\ManualAiBridgeService;
use App\Modules\Platform\Backup\BackupService;
use App\Modules\Platform\Packages\PackageCatalogService;
use App\Modules\Platform\Release\ReleaseReadiness;
use App\Modules\Platform\Search\SearchService;
use App\Modules\SourceGovernance\Application\SafeSourceImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReleaseController extends Controller
{
    public function index(Request $request, ReleaseReadiness $readiness, DailyQueueService $queue, SearchService $search): Response
    {
        $actorId = (string) $request->user()->getAuthIdentifier();
        $query = trim((string) $request->query('q', ''));
        $results = $query === '' ? [] : $search->search($query)->map(fn ($item) => [
            'type' => $item->document_type,
            'id' => $item->document_identifier,
            'title_ar' => $item->title_ar,
            'title_en' => $item->title_en,
            'facets' => $item->facets,
        ])->values()->all();

        return Inertia::render('Release/Center', [
            'readiness' => $readiness->evaluate(),
            'dailyQueue' => $queue->forActor($actorId),
            'query' => $query,
            'searchResults' => $results,
            'sourceImports' => DB::table('source_imports')->where('actor_id', $actorId)->orderByDesc('created_at')->limit(10)->get(['id', 'original_name', 'status', 'sha256', 'created_at']),
            'aiResults' => DB::table('imported_ai_results')->where('actor_id', $actorId)->orderByDesc('imported_at')->limit(10)->get(['id', 'status', 'result_digest', 'imported_at']),
            'backups' => DB::table('backup_manifests')->where('actor_id', $actorId)->orderByDesc('created_at')->limit(10)->get(['id', 'portable_package_id', 'status', 'content_digest', 'created_at']),
            'manualAiPolicy' => ['execution' => 'MANUAL_ONLY', 'automatic_provider' => false, 'automatic_publish' => false],
        ]);
    }

    public function importSource(Request $request, SafeSourceImportService $sources): RedirectResponse
    {
        $request->validate(['source' => ['required', 'file', 'max:10240']]);
        $sources->import($request->file('source'), (string) $request->user()->getAuthIdentifier());

        return back()->with('success', 'Source import was validated and recorded.');
    }

    public function exportAiPrompt(Request $request, ManualAiBridgeService $bridge): RedirectResponse
    {
        $data = $request->validate([
            'purpose' => ['required', 'string', 'max:120'],
            'knowledge_unit_id' => ['required', 'string', 'max:80'],
            'instruction' => ['required', 'string', 'max:10000'],
        ]);
        $result = $bridge->exportPrompt(
            (string) $request->user()->getAuthIdentifier(),
            $data['purpose'],
            ['knowledge_unit_id' => $data['knowledge_unit_id']],
            ['instruction' => $data['instruction'], 'knowledge_unit_id' => $data['knowledge_unit_id']],
        );

        return back()->with('success', "Manual AI prompt package created: {$result['package_id']}");
    }

    public function importAiResult(Request $request, ManualAiBridgeService $bridge): RedirectResponse
    {
        $request->validate(['package' => ['required', 'file', 'mimes:zip', 'max:51200']]);
        $stream = fopen($request->file('package')->getRealPath(), 'rb');
        try {
            $bridge->importResult($stream, (string) $request->user()->getAuthIdentifier());
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return back()->with('success', 'Manual AI result imported for human review.');
    }

    public function decideAi(Request $request, string $result, ManualAiBridgeService $bridge): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['ACCEPT_AS_DRAFT', 'REJECT'])],
            'rationale' => ['required', 'string', 'max:2000'],
        ]);
        $bridge->decide($result, (string) $request->user()->getAuthIdentifier(), $data['decision'], $data['rationale']);

        return back()->with('success', 'AI proposal decision recorded.');
    }

    public function importEvidence(Request $request, ExternalEvidenceImportService $evidence): RedirectResponse
    {
        $request->validate(['package' => ['required', 'file', 'mimes:zip', 'max:51200']]);
        $stream = fopen($request->file('package')->getRealPath(), 'rb');
        try {
            $evidence->import($stream, (string) $request->user()->getAuthIdentifier());
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return back()->with('success', 'External evidence imported for human review.');
    }

    public function createBackup(Request $request, BackupService $backups): RedirectResponse
    {
        $result = $backups->create((string) $request->user()->getAuthIdentifier());

        return back()->with('success', "Verified backup created: {$result['package_id']}");
    }

    public function stageRestore(Request $request, BackupService $backups): RedirectResponse
    {
        $request->validate(['package' => ['required', 'file', 'mimes:zip', 'max:51200']]);
        $stream = fopen($request->file('package')->getRealPath(), 'rb');
        try {
            $backups->stage($stream, (string) $request->user()->getAuthIdentifier());
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return back()->with('success', 'Restore package staged and verified; activation was not performed.');
    }

    public function downloadPackage(Request $request, string $package, PackageCatalogService $catalog): StreamedResponse
    {
        $download = $catalog->download($package, (string) $request->user()->getAuthIdentifier());

        return ResponseFacade::streamDownload(function () use ($download): void {
            fpassthru($download['stream']);
            fclose($download['stream']);
        }, $download['name'], ['Content-Type' => $download['media_type'], 'X-Content-Type-Options' => 'nosniff']);
    }
}
