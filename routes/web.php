<?php

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
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
