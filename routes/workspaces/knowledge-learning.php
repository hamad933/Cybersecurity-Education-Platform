<?php

use App\Http\Controllers\KnowledgeLearning\KnowledgeLearningController;
use Illuminate\Support\Facades\Route;

// CEP-BUILD-001-W02 owns this route file. Legacy /vs001 and /vs002 remain
// reuse/reference surfaces only and are not target Knowledge & Learning routes.
Route::middleware('auth')
    ->prefix('knowledge')
    ->name('cep.knowledge.')
    ->group(function (): void {
        Route::get('/', [KnowledgeLearningController::class, 'library'])->name('library');
        Route::get('/learn', [KnowledgeLearningController::class, 'learn'])->name('learn');
        Route::get('/visualize', [KnowledgeLearningController::class, 'visualize'])->name('visualize');
        Route::get('/research-quality', [KnowledgeLearningController::class, 'researchQuality'])->name('research-quality');

        Route::patch('/library/revisions/{revision}', [KnowledgeLearningController::class, 'updateRevision'])
            ->whereUuid('revision')
            ->name('library.revisions.update');
        Route::post('/library/revisions/{revision}/restore', [KnowledgeLearningController::class, 'restoreRevision'])
            ->whereUuid('revision')
            ->name('library.revisions.restore');
    });
