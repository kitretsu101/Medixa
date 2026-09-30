<?php

use App\Http\Controllers\DoctorPortalController;
use App\Http\Controllers\LabReportController;
use App\Http\Controllers\PatientPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Portal Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/doctors', [PublicController::class, 'doctors'])->name('doctors.index');
Route::get('/doctors/{doctor}', [PublicController::class, 'doctorProfile'])->name('doctors.show');
Route::get('/departments/{department}', [PublicController::class, 'departmentDetail'])->name('departments.show');

/*
|--------------------------------------------------------------------------
| Role-Based Dashboards & Consent-Scoped Routes
|--------------------------------------------------------------------------
*/

// Common dashboard redirector for Breeze compatibility
Route::get('/dashboard', function (Request $request) {
    return match ($request->user()?->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'doctor' => redirect()->route('doctor.dashboard'),
        default => redirect()->route('patient.dashboard'),
    };
})->middleware(['auth'])->name('dashboard');

// ─── Patient Protected Routes ──────────────────────────────────────────
Route::middleware(['auth', 'role:patient'])
    ->prefix('patient')
    ->name('patient.')
    ->group(function () {
        Route::get('/dashboard', [PatientPortalController::class, 'dashboard'])->name('dashboard');

        // Profile
        Route::get('/profile', [PatientPortalController::class, 'profile'])->name('profile');
        Route::put('/profile', [PatientPortalController::class, 'updateProfile'])->name('profile.update');

        // Medical Timeline
        Route::get('/timeline', [PatientPortalController::class, 'timeline'])->name('timeline');

        // Consent Center
        Route::get('/consent-center', [PatientPortalController::class, 'consentCenter'])->name('consent-center');
        Route::post('/consent-tokens/{token}/revoke', [PatientPortalController::class, 'revokeConsent'])->name('consent.revoke');

        // Lab Report Upload
        Route::get('/lab-reports/upload', [PatientPortalController::class, 'uploadLabReportForm'])->name('lab-reports.create');
        Route::post('/lab-reports/upload', [PatientPortalController::class, 'storeLabReport'])->name('lab-reports.store');

        // Assistance Requests
        Route::get('/assistance-requests/create', [PatientPortalController::class, 'assistanceForm'])->name('assistance-requests.create');
        Route::post('/assistance-requests', [PatientPortalController::class, 'storeAssistanceRequest'])->name('assistance-requests.store');

        // Consent-scoped route: Patient accessing own lab report (consent automatically bypassed)
        Route::middleware('consent:lab_report')
            ->get('/lab-reports/{labReport}', [LabReportController::class, 'show'])
            ->name('lab-reports.show');
    });

// ─── Doctor Protected Routes ───────────────────────────────────────────
Route::middleware(['auth', 'role:doctor'])
    ->prefix('doctor')
    ->name('doctor.')
    ->group(function () {
        Route::get('/dashboard', [DoctorPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/patients', [DoctorPortalController::class, 'patients'])->name('patients.index');
        Route::get('/patients/{patient}', [DoctorPortalController::class, 'patientHistory'])->name('patients.history');
        Route::get('/pending-reviews', [DoctorPortalController::class, 'pendingReviews'])->name('pending-reviews');
        Route::get('/appointments/{appointment}/prescription', [DoctorPortalController::class, 'prescriptionForm'])->name('prescriptions.create');
        Route::post('/appointments/{appointment}/prescription', [DoctorPortalController::class, 'storePrescription'])->name('prescriptions.store');

        // Stacking Example: Role + Consent
        // Requires authentication, doctor role, AND active patient consent token for the requested lab report
        Route::middleware('consent:lab_report')
            ->get('/lab-reports/{labReport}', [LabReportController::class, 'show'])
            ->name('lab-reports.show');
    });

// ─── Admin Protected Routes ────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');

        Route::middleware('consent:lab_report')
            ->get('/lab-reports/{labReport}', [LabReportController::class, 'show'])
            ->name('lab-reports.show');
    });

// ─── User Profile Routes ───────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
