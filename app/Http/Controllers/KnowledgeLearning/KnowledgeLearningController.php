<?php

namespace App\Http\Controllers\KnowledgeLearning;

use App\Application\KnowledgeLearning\KnowledgeLearningWorkspace;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

final class KnowledgeLearningController extends Controller
{
    public function library(Request $request, KnowledgeLearningWorkspace $workspace): Response
    {
        return Inertia::render('KnowledgeLearning/Library', $workspace->library(
            $this->queryValue($request, 'object'),
            $this->queryValue($request, 'revision'),
        ));
    }

    public function learn(Request $request, KnowledgeLearningWorkspace $workspace): Response
    {
        return Inertia::render('KnowledgeLearning/Learn', $workspace->learn(
            $this->queryValue($request, 'object'),
            (string) $request->user()->id,
        ));
    }

    public function visualize(Request $request, KnowledgeLearningWorkspace $workspace): Response
    {
        return Inertia::render('KnowledgeLearning/Visualize', $workspace->visualize(
            $this->queryValue($request, 'object'),
        ));
    }

    public function researchQuality(Request $request, KnowledgeLearningWorkspace $workspace): Response
    {
        return Inertia::render('KnowledgeLearning/ResearchQuality', $workspace->researchQuality(
            $this->queryValue($request, 'object'),
            $this->queryValue($request, 'source'),
        ));
    }

    public function updateRevision(Request $request, KnowledgeLearningWorkspace $workspace, string $revision): RedirectResponse
    {
        $validated = $request->validate([
            'lock_version' => ['required', 'integer', 'min:1'],
            'blocks' => ['required', 'array', 'min:1', 'max:24'],
            'blocks.*' => ['array:type,body,depth'],
            'blocks.*.type' => ['required', Rule::in(['heading', 'paragraph', 'callout', 'rules', 'boundaries', 'code', 'request', 'response', 'log'])],
            'blocks.*.body' => ['required', 'string', 'max:4000'],
            'blocks.*.depth' => ['required', 'integer', 'min:0', 'max:3'],
            'citations' => ['required', 'array', 'min:1', 'max:20'],
            'citations.*' => ['required', 'string', 'max:80'],
        ]);

        try {
            $workspace->updateRevision(
                $revision,
                (int) $validated['lock_version'],
                $validated['blocks'],
                $validated['citations'],
                (string) $request->user()->id,
            );
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['revision' => $exception->getMessage()]);
        }

        return back()->with('status', 'تم حفظ التغييرات في المسودة القانونية بالقفل التفاؤلي.');
    }

    public function restoreRevision(Request $request, KnowledgeLearningWorkspace $workspace, string $revision): RedirectResponse
    {
        try {
            $draft = $workspace->restoreRevision($revision, (string) $request->user()->id);
        } catch (LogicException $exception) {
            return back()->withErrors(['revision' => $exception->getMessage()]);
        }

        return redirect()->route('cep.knowledge.library', [
            'object' => $draft['knowledge_unit_id'],
            'revision' => $draft['id'],
        ])->with('status', 'أُنشئت مسودة جديدة من النسخة المنشورة دون تعديل التاريخ المنشور.');
    }

    private function queryValue(Request $request, string $key): ?string
    {
        $value = trim((string) $request->query($key, ''));

        return $value === '' ? null : $value;
    }
}
