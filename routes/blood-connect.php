<?php

use App\Http\Controllers\BloodBankController;
use App\Http\Controllers\BloodDashboardController;
use App\Http\Controllers\BloodDonationController;
use App\Http\Controllers\BloodDonorController;
use App\Http\Controllers\BloodDonorManagementController;
use App\Http\Controllers\BloodRequestController;
use App\Http\Controllers\BloodRequestManagementController;
use App\Http\Controllers\DonorAuthController;
use App\Http\Controllers\DonorDirectoryController;
use App\Http\Controllers\DonorPasswordController;
use App\Http\Controllers\DonorPhoneController;
use App\Http\Controllers\DonorPortalController;
use App\Http\Middleware\AuthenticateDonor;
use App\Http\Middleware\StartDonorSession;
use Illuminate\Support\Facades\Route;

Route::prefix('blood-connect')->name('blood-connect.')->group(function (): void {
    Route::get('/', [BloodBankController::class, 'index'])->name('index');
    Route::get('/requests', [BloodRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [BloodRequestController::class, 'create'])->name('requests.create');
    Route::get('/sent', [BloodRequestController::class, 'sent'])->name('sent');
    Route::post('/requests', [BloodRequestController::class, 'store'])->middleware('throttle:10,1,blood-submit:')->name('requests.store');
    Route::get('/requests/{bloodRequest}', [BloodRequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{bloodRequest}/report', [BloodRequestController::class, 'report'])->middleware('throttle:5,1,blood-report:')->name('requests.report');
    Route::middleware(StartDonorSession::class)->group(function (): void {
        Route::get('/donors', [DonorDirectoryController::class, 'index'])->name('donors.index');
        Route::get('/donors/sent', [DonorDirectoryController::class, 'sent'])->name('donors.sent');
        Route::get('/donors/{bloodDonor}/request', [DonorDirectoryController::class, 'create'])->name('donors.request');
        Route::post('/donors/{bloodDonor}/request', [DonorDirectoryController::class, 'store'])->middleware('throttle:5,1,donor-contact:')->name('donors.request.store');
        Route::get('/donor/register', [DonorAuthController::class, 'create'])->name('donor.register');
        Route::post('/donor/register', [DonorAuthController::class, 'store'])->middleware('throttle:10,1,blood-register:')->name('donor.store');
        Route::get('/donor/login', [DonorAuthController::class, 'login'])->name('donor.login');
        Route::post('/donor/login', [DonorAuthController::class, 'authenticate'])->middleware('throttle:5,15,donor-login:')->name('donor.login.store');
        Route::get('/donor/reset-password/{bloodDonor}/{token}', [DonorPasswordController::class, 'edit'])->where('token', '[A-Za-z0-9]{64}')->name('donor.password.edit');
        Route::post('/donor/reset-password/{bloodDonor}', [DonorPasswordController::class, 'update'])->middleware('throttle:5,15,donor-reset:')->name('donor.password.update');
        Route::middleware([AuthenticateDonor::class, 'auth:donor'])->group(function (): void {
            Route::post('/donor/logout', [DonorAuthController::class, 'logout'])->name('donor.logout');
            Route::get('/donor/dashboard', [DonorPortalController::class, 'dashboard'])->name('donor.dashboard');
            Route::post('/donor/preferences', [DonorPortalController::class, 'preferences'])->name('donor.preferences');
            Route::post('/donor/verify-phone', [DonorPhoneController::class, 'verify'])->middleware('throttle:5,15,donor-verify:')->name('donor.verify-phone');
            Route::post('/donor/resend-otp', [DonorPhoneController::class, 'resend'])->middleware('throttle:1,1,donor-resend:')->name('donor.resend-otp');
            Route::get('/donor/requests', [DonorPortalController::class, 'requests'])->name('donor.requests');
            Route::post('/donor/requests/{contactRequest}/status', [DonorPortalController::class, 'status'])->whereNumber('contactRequest')->name('donor.requests.status');
        });
    });
    Route::middleware('auth')->group(function (): void {
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
    Route::get('/donations', [BloodDonationController::class, 'index'])->name('donations.index');
    Route::post('/requests/{bloodRequest}/donations', [BloodDonationController::class, 'store'])->middleware('throttle:10,1,blood-donation:')->name('donations.store');
});
