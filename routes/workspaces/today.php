<?php

use App\Http\Controllers\TodayController;
use Illuminate\Support\Facades\Route;

// CEP-BUILD-001-W01 owns this route file.
// Keep the existing `dashboard` name because the authenticated-session flow already targets it.
Route::middleware('auth')->group(function (): void {
    Route::get('/', TodayController::class)->name('dashboard');
});
