<?php

use App\Http\Controllers\BloodBankController;
use App\Http\Controllers\BloodDashboardController;
use App\Http\Controllers\BloodDonationController;
use App\Http\Controllers\BloodDonorController;
use App\Http\Controllers\BloodDonorManagementController;
use App\Http\Controllers\BloodRequestController;
use App\Http\Controllers\BloodRequestManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('blood-connect')->name('blood-connect.')->group(function (): void {
    Route::get('/', [BloodBankController::class, 'index'])->name('index');
    Route::get('/requests', [BloodRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [BloodRequestController::class, 'create'])->name('requests.create');
    Route::get('/sent', [BloodRequestController::class, 'sent'])->name('sent');
    Route::post('/requests', [BloodRequestController::class, 'store'])->middleware('throttle:10,1,blood-submit:')->name('requests.store');
    Route::get('/requests/{bloodRequest}', [BloodRequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{bloodRequest}/report', [BloodRequestController::class, 'report'])->middleware('throttle:5,1,blood-report:')->name('requests.report');
    Route::middleware('auth')->group(function (): void {
        Route::get('/donor/register', [BloodDonorController::class, 'create'])->name('donor.register');
        Route::post('/donor/register', [BloodDonorController::class, 'store'])->middleware('throttle:10,1,blood-register:')->name('donor.store');
        Route::get('/donor/profile', [BloodDonorController::class, 'edit'])->name('donor.profile');
        Route::put('/donor/profile', [BloodDonorController::class, 'update'])->name('donor.update');
        Route::put('/donor/availability', [BloodDonorController::class, 'availability'])->name('donor.availability');
        Route::post('/requests/{bloodRequest}/interest', [BloodDonorController::class, 'interest'])->middleware('throttle:10,1,blood-interest:')->name('requests.interest');
    });
});
Route::middleware(['auth', 'role:admin'])->prefix('admin/blood-connect')->name('admin.blood-connect.')->group(function (): void {
    Route::get('/', [BloodDashboardController::class, 'index'])->name('index');
    Route::get('/requests', [BloodRequestManagementController::class, 'index'])->name('requests.index');
    Route::get('/requests/{bloodRequest}', [BloodRequestManagementController::class, 'show'])->name('requests.show');
    Route::put('/requests/{bloodRequest}', [BloodRequestManagementController::class, 'update'])->name('requests.update');
    Route::post('/requests/{bloodRequest}/approve', [BloodRequestManagementController::class, 'approve'])->name('requests.approve');
    Route::post('/requests/{bloodRequest}/reject', [BloodRequestManagementController::class, 'reject'])->name('requests.reject');
    Route::post('/requests/{bloodRequest}/fulfill', [BloodRequestManagementController::class, 'fulfill'])->name('requests.fulfill');
    Route::get('/donors', [BloodDonorManagementController::class, 'index'])->name('donors.index');
    Route::put('/donors/{bloodDonor}', [BloodDonorManagementController::class, 'update'])->name('donors.update');
    Route::get('/donations', [BloodDonationController::class, 'index'])->name('donations.index');
    Route::post('/requests/{bloodRequest}/donations', [BloodDonationController::class, 'store'])->middleware('throttle:10,1,blood-donation:')->name('donations.store');
});
