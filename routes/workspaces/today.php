<?php

use App\Modules\Platform\Health\DashboardController;
use Illuminate\Support\Facades\Route;

// CEP-BUILD-001-W01 owns this route file.
// The current DashboardController keeps the pre-build root behavior reachable until W01
// replaces the surface with the approved real Today workspace.
Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
});
