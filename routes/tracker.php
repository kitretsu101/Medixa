<?php

use App\Http\Controllers\Tracker\TrackerApiController;
use App\Http\Controllers\Tracker\TrackerInvitationController;
use App\Http\Controllers\Tracker\TrackerPwaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Medixa Medicine Tracker PWA Routes
|--------------------------------------------------------------------------
|
| Independent entry points for the installable progressive web app.
| Supports standalone execution under /tracker and tracker.medixa.com.
|
*/

// Standalone PWA Web Shell
Route::middleware(['web'])->prefix('tracker')->group(function () {
    Route::get('/', [TrackerPwaController::class, 'index'])->name('tracker.app');
    Route::get('/offline', [TrackerPwaController::class, 'offline'])->name('tracker.offline');

    // Invitation responses
    Route::middleware(['auth', 'role:patient'])->group(function () {
        Route::post('/invitations/{invitation}/activate', [TrackerInvitationController::class, 'activate'])
            ->name('tracker.invitations.activate');
        Route::post('/invitations/{invitation}/dismiss', [TrackerInvitationController::class, 'dismiss'])
            ->name('tracker.invitations.dismiss');
    });
});

// PWA REST APIs (Auth-gated for patient role)
Route::middleware(['web', 'auth', 'role:patient'])->prefix('api/tracker')->group(function () {
    Route::get('/dashboard', [TrackerApiController::class, 'dashboard'])->name('api.tracker.dashboard');
    Route::post('/doses/{dose}/confirm', [TrackerApiController::class, 'confirmDose'])->name('api.tracker.doses.confirm');
    Route::post('/doses/{dose}/missed', [TrackerApiController::class, 'reportMissed'])->name('api.tracker.doses.missed');
    Route::post('/doses/{dose}/skip', [TrackerApiController::class, 'skipDose'])->name('api.tracker.doses.skip');
    Route::get('/history', [TrackerApiController::class, 'history'])->name('api.tracker.history');
    Route::get('/rewards', [TrackerApiController::class, 'rewards'])->name('api.tracker.rewards');
});

