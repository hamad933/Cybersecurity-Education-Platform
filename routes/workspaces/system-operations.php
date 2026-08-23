<?php

use App\Http\Controllers\ReleaseController;
use Illuminate\Support\Facades\Route;

// CEP-BUILD-001-W05 owns this route file.
// Existing Release Center endpoints are preserved here as REFACTOR_FOR_REUSE inputs while
// W05 builds the approved System & Operations workspace around real platform state.
Route::middleware('auth')->group(function (): void {
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
