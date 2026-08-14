<?php

use App\Modules\Evidence\Http\Controllers\ProgressEvidenceController;
use Illuminate\Support\Facades\Route;

// CEP-BUILD-001-W04 owns this route file.
// Controller/shared-foundation code is responsible for registering this standalone file.
Route::middleware('auth')
    ->prefix('progress')
    ->name('cep.progress.')
    ->group(function (): void {
        Route::get('/', [ProgressEvidenceController::class, 'index'])->name('index');
        Route::get('/reviews', [ProgressEvidenceController::class, 'reviews'])->name('reviews');
        Route::get('/mastery', [ProgressEvidenceController::class, 'mastery'])->name('mastery');
        Route::get('/portfolio', [ProgressEvidenceController::class, 'portfolio'])->name('portfolio');

        Route::post('/intake', [ProgressEvidenceController::class, 'intake'])->name('intake');
        Route::post('/candidates/{candidate}/admit', [ProgressEvidenceController::class, 'admitCandidate'])->name('candidates.admit');
        Route::post('/evidence/{evidence}/revisions', [ProgressEvidenceController::class, 'createRevision'])->name('evidence.revisions.store');
        Route::post('/evidence/{evidence}/lifecycle', [ProgressEvidenceController::class, 'transitionLifecycle'])->name('evidence.lifecycle');
        Route::post('/evidence/{evidence}/review-requests', [ProgressEvidenceController::class, 'requestReview'])->name('review-requests.store');
        Route::post('/review-requests/{reviewRequest}/admit', [ProgressEvidenceController::class, 'admitReview'])->name('review-requests.admit');
        Route::post('/reviews/{review}/findings', [ProgressEvidenceController::class, 'addFinding'])->name('reviews.findings.store');
        Route::post('/reviews/{review}/decision', [ProgressEvidenceController::class, 'decideReview'])->name('reviews.decision.store');
        Route::post('/mastery/evaluate', [ProgressEvidenceController::class, 'evaluateMastery'])->name('mastery.evaluate');
        Route::post('/portfolio', [ProgressEvidenceController::class, 'createPortfolio'])->name('portfolio.store');
        Route::post('/portfolio/{portfolio}/evidence', [ProgressEvidenceController::class, 'addPortfolioEvidence'])->name('portfolio.evidence.store');
        Route::delete('/portfolio/{portfolio}/evidence/{evidence}', [ProgressEvidenceController::class, 'removePortfolioEvidence'])->name('portfolio.evidence.destroy');
    });
