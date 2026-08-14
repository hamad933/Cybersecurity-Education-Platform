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

        Route::post('/scenarios/{scenario}/runs', [SimulationEnterpriseController::class, 'prepareScenario'])->name('scenarios.runs.prepare');
        Route::post('/labs/{lab}/runs', [SimulationEnterpriseController::class, 'prepareLab'])->name('labs.runs.prepare');
        Route::post('/runs/{run}/ready', [SimulationEnterpriseController::class, 'ready'])->name('runs.ready');
        Route::post('/runs/{run}/start', [SimulationEnterpriseController::class, 'start'])->name('runs.start');
        Route::post('/runs/{run}/pause', [SimulationEnterpriseController::class, 'pause'])->name('runs.pause');
        Route::post('/runs/{run}/resume', [SimulationEnterpriseController::class, 'resume'])->name('runs.resume');
        Route::post('/runs/{run}/stop', [SimulationEnterpriseController::class, 'stop'])->name('runs.stop');
        Route::post('/runs/{run}/complete', [SimulationEnterpriseController::class, 'complete'])->name('runs.complete');
        Route::post('/runs/{run}/snapshot', [SimulationEnterpriseController::class, 'snapshot'])->name('runs.snapshot');
        Route::post('/runs/{run}/result', [SimulationEnterpriseController::class, 'sealResult'])->name('runs.result.seal');
        Route::post('/results/{result}/candidate-evidence-handoff', [SimulationEnterpriseController::class, 'candidateEvidenceHandoff'])->name('results.handoff');
    });
