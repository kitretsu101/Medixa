<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DoctorPortalController;
use App\Http\Controllers\LabReportController;
use App\Http\Controllers\LiveQueueController;
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
| Chatbot API (Public Portal) — Rate Limited
|--------------------------------------------------------------------------
*/

Route::post('/chatbot/message', [ChatbotController::class, 'message'])
    ->middleware('throttle:30,1')
    ->name('chatbot.message');

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

        // Signed temporary download URL for lab report files
        Route::get('/lab-reports/{labReport}/download', [LabReportController::class, 'download'])
            ->middleware('signed')
            ->name('lab-reports.download');
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

        // Live Queue Controls (AJAX)
        Route::post('/queue/increment', [LiveQueueController::class, 'increment'])->name('queue.increment');
        Route::post('/queue/decrement', [LiveQueueController::class, 'decrement'])->name('queue.decrement');
        Route::post('/queue/toggle-presence', [LiveQueueController::class, 'togglePresence'])->name('queue.toggle-presence');

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
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        // Departments CRUD
        Route::get('/departments', [AdminController::class, 'departments'])->name('departments.index');
        Route::get('/departments/create', [AdminController::class, 'createDepartment'])->name('departments.create');
        Route::post('/departments', [AdminController::class, 'storeDepartment'])->name('departments.store');
        Route::get('/departments/{department}/edit', [AdminController::class, 'editDepartment'])->name('departments.edit');
        Route::put('/departments/{department}', [AdminController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{department}', [AdminController::class, 'destroyDepartment'])->name('departments.destroy');

        // Doctors CRUD
        Route::get('/doctors', [AdminController::class, 'doctors'])->name('doctors.index');
        Route::get('/doctors/create', [AdminController::class, 'createDoctor'])->name('doctors.create');
        Route::post('/doctors', [AdminController::class, 'storeDoctor'])->name('doctors.store');
        Route::get('/doctors/{doctor}/edit', [AdminController::class, 'editDoctor'])->name('doctors.edit');
        Route::put('/doctors/{doctor}', [AdminController::class, 'updateDoctor'])->name('doctors.update');
        Route::delete('/doctors/{doctor}', [AdminController::class, 'destroyDoctor'])->name('doctors.destroy');

        // Schedule Management
        Route::get('/schedule', [AdminController::class, 'schedule'])->name('schedule');

        // Audit Oversight — AGGREGATE ONLY, no patient record content
        Route::get('/audit-oversight', [AdminController::class, 'auditOversight'])->name('audit-oversight');

        // Admin lab report access — also requires consent
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
