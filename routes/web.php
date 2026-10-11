<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminKnowledgeController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DoctorPortalController;
use App\Http\Controllers\LabAssistantController;
use App\Http\Controllers\LabReportController;
use App\Http\Controllers\LiveQueueController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReceptionistController;
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

Route::delete('/chatbot/sessions/{session}', [ChatbotController::class, 'destroy'])
    ->middleware('throttle:30,1')
    ->whereUuid('session')
    ->name('chatbot.sessions.destroy');

Route::middleware(['auth', 'signed', 'consent:lab_report'])
    ->get('/lab-reports/{labReport}/download', [LabReportController::class, 'download'])
    ->name('lab-reports.download');

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
        'lab_assistant' => redirect()->route('lab.dashboard'),
        'receptionist' => redirect()->route('receptionist.dashboard'),
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

        // Appointments
        Route::get('/appointments', [PatientPortalController::class, 'appointments'])->name('appointments.index');
        Route::get('/appointments/book', [PatientPortalController::class, 'bookAppointmentForm'])->name('appointments.create');
        Route::post('/appointments', [PatientPortalController::class, 'storeAppointment'])
            ->middleware('throttle:10,1')
            ->name('appointments.store');
        Route::post('/appointments/{appointment}/check-in', [PatientPortalController::class, 'checkIn'])->name('appointments.check-in');
        Route::post('/appointments/{appointment}/feedback', [PatientPortalController::class, 'storeFeedback'])->name('appointments.feedback');

        // Live queue polling fallback
        Route::get('/queue/status', [PatientPortalController::class, 'queueStatus'])->name('queue.status');

        // Consent Center
        Route::get('/consent-center', [PatientPortalController::class, 'consentCenter'])->name('consent-center');
        Route::post('/consent-tokens/{token}/revoke', [PatientPortalController::class, 'revokeConsent'])->name('consent.revoke');

        // Lab Report Upload
        Route::get('/lab-reports/upload', [PatientPortalController::class, 'uploadLabReportForm'])->name('lab-reports.create');
        Route::post('/lab-reports/upload', [PatientPortalController::class, 'storeLabReport'])->name('lab-reports.store');

        // Assistance Requests
        Route::get('/assistance-requests/create', [PatientPortalController::class, 'assistanceForm'])->name('assistance-requests.create');
        Route::post('/assistance-requests', [PatientPortalController::class, 'storeAssistanceRequest'])
            ->middleware('throttle:10,1')
            ->name('assistance-requests.store');

        // Consent-scoped route: Patient accessing own lab report (consent automatically bypassed)
        Route::middleware('consent:lab_report')->group(function () {
            Route::get('/lab-reports/{labReport}', [LabReportController::class, 'show'])
                ->name('lab-reports.show');
            Route::get('/lab-reports/{labReport}/open', [LabReportController::class, 'open'])
                ->name('lab-reports.open');
        });
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
        Route::post('/queue/delay', [LiveQueueController::class, 'delay'])->name('queue.delay');
        Route::post('/queue/pause', [LiveQueueController::class, 'pause'])->name('queue.pause');
        Route::post('/appointments/{appointment}/no-show', [DoctorPortalController::class, 'markNoShow'])->name('appointments.no-show');
        Route::post('/lab-reports/{labReport}/review', [DoctorPortalController::class, 'reviewReport'])->name('lab-reports.review');

        Route::get('/assistance', [DoctorPortalController::class, 'assistance'])->name('assistance.index');
        Route::post('/assistance/{assistanceRequest}', [DoctorPortalController::class, 'respondToAssistance'])->name('assistance.respond');

        Route::post('/patients/{patient}/lab-orders', [DoctorPortalController::class, 'orderLabTest'])->name('lab-orders.store');

        // Requires authentication, doctor role, AND an active consent token for this lab report
        Route::middleware('consent:lab_report')->group(function () {
            Route::get('/lab-reports/{labReport}', [LabReportController::class, 'show'])
                ->name('lab-reports.show');
            Route::get('/lab-reports/{labReport}/open', [LabReportController::class, 'open'])
                ->name('lab-reports.open');
        });
    });

// ─── Lab Assistant Protected Routes ────────────────────────────────────
Route::middleware(['auth', 'role:lab_assistant'])
    ->prefix('lab-assistant')
    ->name('lab.')
    ->group(function () {
        Route::get('/dashboard', [LabAssistantController::class, 'dashboard'])->name('dashboard');
        Route::get('/reports', [LabAssistantController::class, 'reports'])->name('reports.index');
        Route::get('/reports/{labReport}/upload', [LabAssistantController::class, 'upload'])->name('reports.upload');
        Route::post('/reports/{labReport}/upload', [LabAssistantController::class, 'store'])->name('reports.store');
        Route::get('/upload-history', [LabAssistantController::class, 'history'])->name('history');
    });

// ─── Receptionist Protected Routes ─────────────────────────────────────
Route::middleware(['auth', 'role:receptionist'])
    ->prefix('receptionist')
    ->name('receptionist.')
    ->group(function () {
        Route::get('/dashboard', [ReceptionistController::class, 'dashboard'])->name('dashboard');
        Route::get('/walk-in', [ReceptionistController::class, 'create'])->name('walk-in');
        Route::post('/walk-in', [ReceptionistController::class, 'store'])->name('walk-in.store');
        Route::post('/appointments/{appointment}/check-in', [ReceptionistController::class, 'checkIn'])->name('check-in');
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
        Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');

        Route::get('/knowledge', [AdminKnowledgeController::class, 'index'])->name('knowledge.index');
        Route::get('/knowledge/{article}', [AdminKnowledgeController::class, 'edit'])->name('knowledge.edit');
        Route::put('/knowledge/{article}', [AdminKnowledgeController::class, 'update'])->name('knowledge.update');

        // Admin lab report access — also requires consent
        Route::middleware('consent:lab_report')
            ->get('/lab-reports/{labReport}', [LabReportController::class, 'show'])
            ->name('lab-reports.show');
    });

// ─── User Profile Routes ───────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
