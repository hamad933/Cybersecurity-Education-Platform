<?php

use App\Http\Controllers\ReleaseController;
use App\Modules\Platform\SystemOperations\SystemOperationsController;
use Illuminate\Support\Facades\Route;

// CEP-BUILD-001-W05 owns this route file.
// /release is retained only as a compatibility surface while /system is the approved global IA.
Route::middleware('auth')->group(function (): void {
    Route::prefix('system')->name('cep.system.')->group(function (): void {
        Route::get('/', [SystemOperationsController::class, 'health'])->name('index');
        Route::get('/processing', [SystemOperationsController::class, 'processing'])->name('processing');
        Route::get('/validation', [SystemOperationsController::class, 'validation'])->name('validation');
        Route::get('/ai-bridge', [SystemOperationsController::class, 'aiBridge'])->name('ai-bridge');
        Route::get('/backups', [SystemOperationsController::class, 'backups'])->name('backups');
        Route::get('/audit', [SystemOperationsController::class, 'audit'])->name('audit');
        Route::get('/releases', [SystemOperationsController::class, 'releases'])->name('releases');
        Route::get('/configuration', [SystemOperationsController::class, 'configuration'])->name('configuration');

        Route::post('/processing/runs/{run}/cancel', [SystemOperationsController::class, 'cancelProcessingRun'])
            ->middleware('throttle:30,1')->whereUuid('run')->name('processing.runs.cancel');
        Route::post('/validation/sources/import', [ReleaseController::class, 'importSource'])
            ->middleware('throttle:10,1')->name('validation.sources.import');
        Route::post('/ai-bridge/prompts/export', [ReleaseController::class, 'exportAiPrompt'])
            ->middleware('throttle:10,1')->name('ai-bridge.prompts.export');
        Route::post('/ai-bridge/results/import', [ReleaseController::class, 'importAiResult'])
            ->middleware('throttle:10,1')->name('ai-bridge.results.import');
        Route::post('/ai-bridge/results/{result}/decide', [ReleaseController::class, 'decideAi'])
            ->middleware('throttle:10,1')->whereUuid('result')->name('ai-bridge.results.decide');
        Route::post('/backups', [ReleaseController::class, 'createBackup'])
            ->middleware('throttle:3,10')->name('backups.create');
        Route::post('/backups/restores/stage', [ReleaseController::class, 'stageRestore'])
            ->middleware('throttle:3,10')->name('backups.restores.stage');
        Route::get('/packages/{package}', [ReleaseController::class, 'downloadPackage'])
            ->middleware('throttle:20,1')->whereUuid('package')->name('packages.download');
    });

    Route::prefix('release')->name('release.')->group(function (): void {
        Route::get('/', [ReleaseController::class, 'index'])->name('center');
        Route::post('/sources/import', [ReleaseController::class, 'importSource'])->middleware('throttle:10,1')->name('sources.import');
        Route::post('/ai/prompts/export', [ReleaseController::class, 'exportAiPrompt'])->middleware('throttle:10,1')->name('ai.prompts.export');
        Route::post('/ai/results/import', [ReleaseController::class, 'importAiResult'])->middleware('throttle:10,1')->name('ai.results.import');
        Route::post('/ai/results/{result}/decide', [ReleaseController::class, 'decideAi'])->middleware('throttle:10,1')->whereUuid('result')->name('ai.results.decide');
        Route::post('/evidence/import', [ReleaseController::class, 'importEvidence'])->middleware('throttle:10,1')->name('evidence.import');
        Route::post('/backups', [ReleaseController::class, 'createBackup'])->middleware('throttle:3,10')->name('backups.create');
        Route::post('/restores/stage', [ReleaseController::class, 'stageRestore'])->middleware('throttle:3,10')->name('restores.stage');
        Route::get('/packages/{package}', [ReleaseController::class, 'downloadPackage'])->middleware('throttle:20,1')->whereUuid('package')->name('packages.download');
    });
});
