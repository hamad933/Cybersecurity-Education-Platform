<?php

use App\Modules\Simulator\Http\Controllers\SimulationEnterpriseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('simulation')
    ->name('cep.simulation.')
    ->group(function (): void {
        Route::get('/', [SimulationEnterpriseController::class, 'index'])->name('index');
        Route::get('/scenarios', [SimulationEnterpriseController::class, 'scenarios'])->name('scenarios');
        Route::get('/labs', [SimulationEnterpriseController::class, 'labs'])->name('labs');
        Route::get('/runs', [SimulationEnterpriseController::class, 'runs'])->name('runs');
        Route::get('/results', [SimulationEnterpriseController::class, 'results'])->name('results');

        Route::post('/enterprise/{enterprise}/entities', [SimulationEnterpriseController::class, 'createEnterpriseEntity'])->whereUuid('enterprise')->name('enterprise.entities.create');
        Route::post('/enterprise/{enterprise}/relationships', [SimulationEnterpriseController::class, 'createEnterpriseRelationship'])->whereUuid('enterprise')->name('enterprise.relationships.create');
        Route::post('/enterprise/{enterprise}/device-templates', [SimulationEnterpriseController::class, 'createDeviceTemplateDraft'])->whereUuid('enterprise')->name('enterprise.device-templates.create');
        Route::post('/device-template-revisions/{revision}/validate', [SimulationEnterpriseController::class, 'validateDeviceTemplateRevision'])->whereUuid('revision')->name('device-template-revisions.validate');
        Route::post('/device-template-revisions/{revision}/publish', [SimulationEnterpriseController::class, 'publishDeviceTemplateRevision'])->whereUuid('revision')->name('device-template-revisions.publish');
        Route::post('/enterprise/{enterprise}/digital-twins', [SimulationEnterpriseController::class, 'createDigitalTwinDraft'])->whereUuid('enterprise')->name('enterprise.digital-twins.create');
        Route::post('/digital-twin-revisions/{revision}/components', [SimulationEnterpriseController::class, 'addDigitalTwinComponent'])->whereUuid('revision')->name('digital-twin-revisions.components.create');
        Route::post('/digital-twin-revisions/{revision}/relationships', [SimulationEnterpriseController::class, 'addDigitalTwinRelationship'])->whereUuid('revision')->name('digital-twin-revisions.relationships.create');
        Route::post('/digital-twin-revisions/{revision}/validate', [SimulationEnterpriseController::class, 'validateDigitalTwinRevision'])->whereUuid('revision')->name('digital-twin-revisions.validate');
        Route::post('/digital-twin-revisions/{revision}/publish', [SimulationEnterpriseController::class, 'publishDigitalTwinRevision'])->whereUuid('revision')->name('digital-twin-revisions.publish');
        Route::post('/digital-twin-revisions/{revision}/clone', [SimulationEnterpriseController::class, 'cloneDigitalTwinRevision'])->whereUuid('revision')->name('digital-twin-revisions.clone');
        Route::post('/digital-twin-revisions/{revision}/baselines', [SimulationEnterpriseController::class, 'createBaseline'])->whereUuid('revision')->name('digital-twin-revisions.baselines.create');

        Route::post('/labs/drafts', [SimulationEnterpriseController::class, 'createLabDraft'])->name('labs.drafts.create');
        Route::post('/labs/{lab}/facets', [SimulationEnterpriseController::class, 'updateLabDraftR20Facets'])->whereUuid('lab')->name('labs.facets.update');
        Route::post('/labs/{lab}/tasks', [SimulationEnterpriseController::class, 'addLabTask'])->whereUuid('lab')->name('labs.tasks.create');
        Route::post('/labs/{lab}/task-dependencies', [SimulationEnterpriseController::class, 'addLabTaskDependency'])->whereUuid('lab')->name('labs.task-dependencies.create');
        Route::post('/labs/{lab}/device-template-references', [SimulationEnterpriseController::class, 'addLabDeviceTemplateReference'])->whereUuid('lab')->name('labs.device-template-references.create');
        Route::post('/labs/{lab}/validate', [SimulationEnterpriseController::class, 'validateLabDefinition'])->whereUuid('lab')->name('labs.validate');
        Route::post('/labs/{lab}/publish', [SimulationEnterpriseController::class, 'publishLabDefinition'])->whereUuid('lab')->name('labs.publish');
        Route::post('/labs/{lab}/clone', [SimulationEnterpriseController::class, 'cloneLabDefinition'])->whereUuid('lab')->name('labs.clone');

        Route::post('/scenarios/{scenario}/runs', [SimulationEnterpriseController::class, 'prepareScenario'])->whereUuid('scenario')->name('scenarios.runs.prepare');
        Route::post('/labs/{lab}/runs', [SimulationEnterpriseController::class, 'prepareLab'])->whereUuid('lab')->name('labs.runs.prepare');
        Route::post('/runs/{run}/ready', [SimulationEnterpriseController::class, 'ready'])->whereUuid('run')->name('runs.ready');
        Route::post('/runs/{run}/start', [SimulationEnterpriseController::class, 'start'])->whereUuid('run')->name('runs.start');
        Route::post('/runs/{run}/operations', [SimulationEnterpriseController::class, 'operate'])->whereUuid('run')->name('runs.operations');
        Route::post('/runs/{run}/pause', [SimulationEnterpriseController::class, 'pause'])->whereUuid('run')->name('runs.pause');
        Route::post('/runs/{run}/resume', [SimulationEnterpriseController::class, 'resume'])->whereUuid('run')->name('runs.resume');
        Route::post('/runs/{run}/stop', [SimulationEnterpriseController::class, 'stop'])->whereUuid('run')->name('runs.stop');
        Route::post('/runs/{run}/complete', [SimulationEnterpriseController::class, 'complete'])->whereUuid('run')->name('runs.complete');
        Route::post('/runs/{run}/snapshot', [SimulationEnterpriseController::class, 'snapshot'])->whereUuid('run')->name('runs.snapshot');
        Route::post('/runs/{run}/result', [SimulationEnterpriseController::class, 'sealResult'])->whereUuid('run')->name('runs.result.seal');
        Route::post('/results/{result}/replay-compare', [SimulationEnterpriseController::class, 'replayCompare'])->whereUuid('result')->name('results.replay-compare');
        Route::post('/results/{result}/candidate-evidence-handoff', [SimulationEnterpriseController::class, 'candidateEvidenceHandoff'])->whereUuid('result')->name('results.handoff');
    });
