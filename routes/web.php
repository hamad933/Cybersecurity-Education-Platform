<?php

use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\Vs001Controller;
use App\Http\Controllers\Vs002Controller;
use App\Http\Controllers\Vs003Controller;
use App\Modules\IdentityAccess\Http\Controllers\AuthenticatedSessionController;
use App\Modules\Platform\Health\DashboardController;
use App\Modules\Platform\Health\LivenessController;
use Illuminate\Support\Facades\Route;

Route::get('/health/live', LivenessController::class)->name('health.live');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
});
Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::prefix('vs001')->name('vs001.')->group(function (): void {
        Route::get('/sources', [Vs001Controller::class, 'sourceReview'])->name('sources');
        Route::get('/lesson/editor', [Vs001Controller::class, 'lessonEditor'])->name('lesson.editor');
        Route::post('/lesson/{revision}/restore', [Vs001Controller::class, 'restoreLesson'])->middleware('throttle:10,1')->name('lesson.restore');
        Route::post('/lesson/{revision}/update', [Vs001Controller::class, 'updateLesson'])->middleware('throttle:20,1')->name('lesson.update');
        Route::post('/lesson/{revision}/submit', [Vs001Controller::class, 'submitLesson'])->middleware('throttle:10,1')->name('lesson.submit');
        Route::post('/lesson/{revision}/return', [Vs001Controller::class, 'returnLesson'])->middleware('throttle:10,1')->name('lesson.return');
        Route::post('/lesson/{revision}/approve', [Vs001Controller::class, 'approveLesson'])->middleware('throttle:10,1')->name('lesson.approve');
        Route::post('/lesson/{revision}/publish', [Vs001Controller::class, 'publishLesson'])->middleware('throttle:10,1')->name('lesson.publish');
        Route::get('/lesson', [Vs001Controller::class, 'lessonReader'])->name('lesson.reader');
        Route::get('/practice', [Vs001Controller::class, 'microPractice'])->name('practice');
        Route::post('/practice', [Vs001Controller::class, 'submitPractice'])->middleware('throttle:20,1')->name('practice.submit');
        Route::get('/lab', [Vs001Controller::class, 'guidedLab'])->name('lab');
        Route::post('/lab/run', [Vs001Controller::class, 'runCase'])->middleware('throttle:30,1')->name('lab.run');
        Route::post('/lab/{run}/replay', [Vs001Controller::class, 'replay'])->middleware('throttle:10,1')->name('lab.replay');
        Route::get('/evidence', [Vs001Controller::class, 'evidenceMastery'])->name('evidence');
        Route::post('/evidence/{evidence}/decision', [Vs001Controller::class, 'decideEvidence'])->middleware('throttle:30,1')->name('evidence.decision');
        Route::post('/mastery/evaluate', [Vs001Controller::class, 'evaluateMastery'])->middleware('throttle:20,1')->name('mastery.evaluate');
    });
    Route::prefix('vs002')->name('vs002.')->group(function (): void {
        Route::get('/sources', [Vs002Controller::class, 'sources'])->name('sources');
        Route::get('/lesson/editor', [Vs002Controller::class, 'lessonEditor'])->name('lesson.editor');
        Route::post('/lesson/{revision}/restore', [Vs002Controller::class, 'restoreLesson'])->middleware('throttle:10,1')->name('lesson.restore');
        Route::post('/lesson/{revision}/update', [Vs002Controller::class, 'updateLesson'])->middleware('throttle:20,1')->name('lesson.update');
        Route::post('/lesson/{revision}/submit', [Vs002Controller::class, 'submitLesson'])->middleware('throttle:10,1')->name('lesson.submit');
        Route::post('/lesson/{revision}/return', [Vs002Controller::class, 'returnLesson'])->middleware('throttle:10,1')->name('lesson.return');
        Route::post('/lesson/{revision}/approve', [Vs002Controller::class, 'approveLesson'])->middleware('throttle:10,1')->name('lesson.approve');
        Route::post('/lesson/{revision}/publish', [Vs002Controller::class, 'publishLesson'])->middleware('throttle:10,1')->name('lesson.publish');
        Route::get('/lesson', [Vs002Controller::class, 'lesson'])->name('lesson');
        Route::get('/practice', [Vs002Controller::class, 'practice'])->name('practice');
        Route::post('/practice', [Vs002Controller::class, 'submitPractice'])->middleware('throttle:20,1')->name('practice.submit');
        Route::get('/lab', [Vs002Controller::class, 'lab'])->name('lab');
        Route::post('/lab/run', [Vs002Controller::class, 'runCase'])->middleware('throttle:30,1')->name('lab.run');
        Route::post('/lab/{run}/replay', [Vs002Controller::class, 'replay'])->middleware('throttle:10,1')->name('lab.replay');
        Route::post('/remediation', [Vs002Controller::class, 'remediate'])->middleware('throttle:10,1')->name('remediation');
        Route::post('/findings/{finding}/verify', [Vs002Controller::class, 'verify'])->middleware('throttle:10,1')->name('findings.verify');
        Route::get('/evidence', [Vs002Controller::class, 'evidence'])->name('evidence');
        Route::post('/evidence/{evidence}/decision', [Vs002Controller::class, 'decideEvidence'])->middleware('throttle:30,1')->name('evidence.decision');
        Route::post('/mastery/evaluate', [Vs002Controller::class, 'evaluateMastery'])->middleware('throttle:20,1')->name('mastery.evaluate');
    });
    Route::prefix('vs003')->name('vs003.')->group(function (): void {
        Route::get('/lab', [Vs003Controller::class, 'lab'])->name('lab');
        Route::post('/lab/run', [Vs003Controller::class, 'run'])->middleware('throttle:30,1')->name('lab.run');
        Route::post('/triage', [Vs003Controller::class, 'triage'])->middleware('throttle:20,1')->name('triage');
        Route::post('/evidence/preserve', [Vs003Controller::class, 'preserve'])->middleware('throttle:20,1')->name('evidence.preserve');
        Route::post('/containment/propose', [Vs003Controller::class, 'proposeContainment'])->middleware('throttle:10,1')->name('containment.propose');
        Route::post('/containment/{proposal}/approve', [Vs003Controller::class, 'approveContainment'])->middleware('throttle:10,1')->whereUuid('proposal')->name('containment.approve');
        Route::post('/containment/{proposal}/verify', [Vs003Controller::class, 'verifyContainment'])->middleware('throttle:10,1')->whereUuid('proposal')->name('containment.verify');
        Route::post('/practice', [Vs003Controller::class, 'practice'])->middleware('throttle:20,1')->name('practice');
        Route::post('/mastery/evaluate', [Vs003Controller::class, 'mastery'])->middleware('throttle:10,1')->name('mastery.evaluate');
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
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
