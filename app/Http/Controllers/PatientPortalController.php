<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentFeedback;
use App\Models\AssistanceRequest;
use App\Models\AuditLog;
use App\Models\ConsentToken;
use App\Models\LabReport;
use App\Models\PatientProfile;
use App\Models\Prescription;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\ConsentService;
use App\Services\NotificationService;
use App\Services\QueueOptimizationService;
use App\Services\QueueService;
use App\Support\DoctorName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PatientPortalController extends Controller
{
    public function __construct(
        protected ConsentService $consentService,
        protected AppointmentService $appointmentService,
        protected NotificationService $notificationService,
        protected QueueService $queueService,
        protected QueueOptimizationService $queueOptimization,
    ) {}

    // ─── Dashboard ───────────────────────────────────────────────────

    /**
     * Show the patient dashboard with summary stats and quick actions.
     */
    public function dashboard(): View
    {
        $userId = Auth::id();

        $prescriptionCount = Prescription::where('patient_id', $userId)->count();
        $labReportCount = LabReport::where('patient_id', $userId)->count();
        $activeConsentsCount = $this->consentService->activeTokensForPatient($userId)->count();
        $openAssistanceCount = AssistanceRequest::where('patient_id', $userId)->where('status', 'open')->count();

        $recentPrescriptions = Prescription::where('patient_id', $userId)
            ->with('doctor')
            ->latest()
            ->take(3)
            ->get();

        $recentLabReports = LabReport::where('patient_id', $userId)
            ->with('doctor')
            ->latest()
            ->take(3)
            ->get();

        $activeAppointment = $this->upcomingVisit($userId);
        $queueEstimate = $activeAppointment ? $this->queueOptimization->snapshot($activeAppointment) : null;

        return view('patient.dashboard', compact(
            'prescriptionCount',
            'labReportCount',
            'activeConsentsCount',
            'openAssistanceCount',
            'recentPrescriptions',
            'recentLabReports',
            'activeAppointment',
            'queueEstimate',
        ));
    }

    // ─── Profile ─────────────────────────────────────────────────────

    /**
     * Show the patient profile form.
     */
    public function profile(): View
    {
        $user = Auth::user();
        $profile = PatientProfile::firstOrCreate(['user_id' => $user->id]);
        $activeAppointment = $this->upcomingVisit($user->id);
        $queueEstimate = $activeAppointment ? $this->queueOptimization->snapshot($activeAppointment) : null;

        return view('patient.profile', compact('user', 'profile', 'activeAppointment', 'queueEstimate'));
    }

    /**
     * Update the patient profile.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dob' => ['nullable', 'date', 'before:today'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:30'],
            'nid' => ['nullable', 'string', 'max:30'],
            'emergency_contact' => ['nullable', 'string', 'max:30'],
        ]);

        $user = Auth::user();
        $profile = PatientProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update($validated);

        return redirect()->route('patient.profile')
            ->with('status', 'Profile updated successfully.');
    }

    // ─── Record Timeline ─────────────────────────────────────────────

    /**
     * Display a merged, chronological timeline of prescriptions and lab reports.
     */
    public function timeline(): View
    {
        $userId = Auth::id();

        $prescriptions = Prescription::where('patient_id', $userId)
            ->with('doctor')
            ->get()
            ->map(fn (Prescription $p) => (object) [
                'type' => 'prescription',
                'title' => 'Prescription — '.$p->diagnosis,
                'date' => $p->created_at,
                'doctor_name' => $p->doctor?->name ?? 'N/A',
                'detail' => $p->instructions,
                'medicines' => $p->medicines,
                'id' => $p->id,
            ]);

        $labReports = LabReport::where('patient_id', $userId)
            ->with('doctor')
            ->get()
            ->map(fn (LabReport $r) => (object) [
                'type' => 'lab_report',
                'title' => $r->report_title,
                'date' => $r->uploaded_at ?? $r->created_at,
                'doctor_name' => $r->doctor?->name ?? 'N/A',
                'detail' => filled($r->file_path) ? 'Status: '.ucfirst((string) $r->review_status) : 'Status: Awaiting laboratory upload',
                'medicines' => null,
                'has_file' => filled($r->file_path),
                'id' => $r->id,
            ]);

        $timeline = $prescriptions->concat($labReports)
            ->sortByDesc('date')
            ->values();

        return view('patient.timeline', compact('timeline'));
    }

    // ─── Consent Center ──────────────────────────────────────────────

    /**
     * Display the Consent Center: token list + audit log viewer.
     */
    public function consentCenter(): View
    {
        $userId = Auth::id();

        $tokens = $this->consentService->allTokensForPatient($userId);
        $auditLogs = AuditLog::where('patient_id', $userId)
            ->with(['actor', 'consentToken'])
            ->latest('accessed_at')
            ->get();

        return view('patient.consent-center', compact('tokens', 'auditLogs'));
    }

    /**
     * Revoke a consent token.
     */
    public function revokeConsent(Request $request, string $token): RedirectResponse
    {
        $record = ConsentToken::query()->find($token);

        if ($record === null || (int) $record->patient_id !== (int) $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You can only revoke your own consent tokens.');
        }

        $this->consentService->revoke($record->id);

        return redirect()->route('patient.consent-center')
            ->with('status', 'Consent token revoked successfully.');
    }

    // ─── Lab Report Upload ───────────────────────────────────────────

    /**
     * Show the lab report upload form.
     */
    public function uploadLabReportForm(): View
    {
        $doctors = User::doctors()->get();

        return view('patient.upload-lab-report', compact('doctors'));
    }

    /**
     * Store the uploaded lab report file and AUTO-ISSUE a 24-hour consent token
     * to the assigned doctor.
     *
     * ┌──────────────────────────────────────────────────────────────┐
     * │  AUTOMATIC CONSENT ISSUANCE — this is where it happens:     │
     * │                                                             │
     * │  After the LabReport row is created, we call                │
     * │  ConsentService::issue() to generate a 24-hour consent      │
     * │  token granting the assigned doctor access to THIS specific  │
     * │  lab report. The patient doesn't need to manually grant      │
     * │  consent — uploading to a doctor implies initial consent.    │
     * │                                                             │
     * │  The doctor's access AUTOMATICALLY EXPIRES after 24 hours.  │
     * │  The patient can also revoke it earlier via Consent Center.  │
     * └──────────────────────────────────────────────────────────────┘
     */
    public function storeLabReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'report_title' => ['required', 'string', 'max:255'],
            'doctor_id' => ['required', 'exists:users,id'],
            'report_file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        // Store the file in a PRIVATE disk (storage/app/private/reports)
        $filePath = $request->file('report_file')
            ->store('reports', 'private');

        // Create the lab report row
        $labReport = LabReport::create([
            'patient_id' => Auth::id(),
            'doctor_id' => $validated['doctor_id'],
            'report_title' => $validated['report_title'],
            'file_path' => $filePath,
            'review_status' => 'pending',
            'upload_status' => 'uploaded',
            'uploaded_at' => now(),
        ]);

        // ──────────────────────────────────────────────────────────
        //  AUTOMATIC CONSENT ISSUANCE
        //  Grant the assigned doctor a 24-hour (1440 min) consent
        //  token scoped to this specific lab report resource.
        // ──────────────────────────────────────────────────────────
        $this->consentService->issue(
            patientId: Auth::id(),
            granteeId: (int) $validated['doctor_id'],
            resourceType: 'lab_report',
            resourceId: $labReport->id,
            ttlMinutes: 1440, // 24 hours
        );

        $doctor = User::query()->find($validated['doctor_id']);
        if ($doctor !== null) {
            $this->notificationService->alert(
                $doctor,
                'New lab report to review',
                Auth::user()->name.' uploaded "'.$labReport->report_title.'". A 24-hour consent token was issued for this report only.',
                route('doctor.pending-reviews'),
            );
        }

        return redirect()->route('patient.timeline')
            ->with('status', 'Lab report uploaded. A 24-hour consent token has been automatically issued to the assigned doctor.');
    }

    // ─── Assistance Request ──────────────────────────────────────────

    /**
     * Show the assistance request form.
     */
    public function assistanceForm(): View
    {
        $doctors = User::doctors()->get();

        return view('patient.assistance-request', compact('doctors'));
    }

    /**
     * Store an assistance request.
     */
    public function storeAssistanceRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['nullable', 'exists:users,id'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $requestRecord = AssistanceRequest::create([
            'patient_id' => Auth::id(),
            'doctor_id' => $validated['doctor_id'] ?? null,
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        if ($requestRecord->doctor_id !== null) {
            $doctor = User::query()->find($requestRecord->doctor_id);
            if ($doctor !== null) {
                $this->notificationService->alert(
                    $doctor,
                    'New assistance request',
                    'A patient sent a non-emergency assistance request.',
                    route('doctor.assistance.index'),
                );
            }
        }

        return redirect()->route('patient.dashboard')
            ->with('status', 'Assistance request submitted successfully.');
    }

    // ─── Appointments ────────────────────────────────────────────────

    public function appointments(): View
    {
        $appointments = Appointment::query()
            ->where('patient_id', Auth::id())
            ->with(['doctor.liveQueue', 'doctor.doctorProfile.department', 'feedback'])
            ->latest('appointment_date')
            ->paginate(15);

        return view('patient.appointments', compact('appointments'));
    }

    public function bookAppointmentForm(Request $request): View
    {
        $doctors = User::doctors()->with('doctorProfile.department')->whereHas('doctorProfile')->orderBy('name')->get();
        $selectedDoctorId = $request->integer('doctor') ?: null;

        return view('patient.book-appointment', compact('doctors', 'selectedDoctorId'));
    }

    public function storeAppointment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'exists:users,id'],
            'appointment_date' => ['required', 'date'],
            'priority_type' => ['nullable', 'in:normal,senior,follow_up'],
        ]);

        $doctor = User::query()->findOrFail($validated['doctor_id']);

        try {
            $appointment = $this->appointmentService->book(
                $request->user(),
                $doctor,
                $validated['appointment_date'],
                $validated['priority_type'] ?? 'normal',
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        $this->notificationService->alert(
            $request->user(),
            'Appointment booked',
            'Serial #'.$appointment->serial_number.' with '.DoctorName::format($doctor->name).' on '.$appointment->appointment_date->format('M d, Y').'.',
            route('patient.appointments.index'),
        );
        $this->notificationService->alert(
            $doctor,
            'New appointment',
            'Serial #'.$appointment->serial_number.' was booked for '.$appointment->appointment_date->format('M d, Y').'.',
            route('doctor.dashboard'),
        );

        return redirect()->route('patient.appointments.index')
            ->with('status', 'Appointment booked. Your serial number is #'.$appointment->serial_number.'.');
    }

    /**
     * Polling fallback for the live queue when WebSockets are unavailable.
     */
    public function queueStatus(): JsonResponse
    {
        $appointment = $this->upcomingVisit((int) Auth::id());

        if ($appointment === null) {
            return response()->json(['active' => false]);
        }

        $queue = $appointment->doctor?->liveQueue;
        $estimate = $this->queueOptimization->snapshot($appointment);

        return response()->json([
            'active' => true,
            'current_serial' => (int) ($queue?->current_running_serial ?? 0),
            'doctor_present' => (bool) ($queue?->doctor_present_status ?? false),
            'your_serial' => (int) $appointment->serial_number,
            'patients_ahead' => $estimate['patients_ahead'],
            'estimated_wait_minutes' => $estimate['estimated_wait_minutes'],
            'delay_minutes' => $estimate['delay_minutes'],
            'delay_message' => $estimate['delay_message'],
            'explanation' => $estimate['explanation'],
            'priority_label' => $estimate['priority_label'],
            'recommended_arrival' => $estimate['recommended_arrival'],
            'checked_in' => $estimate['checked_in'],
            'session_status' => $estimate['session_status'],
        ]);
    }

    public function checkIn(Request $request, Appointment $appointment): RedirectResponse
    {
        if ((int) $appointment->patient_id !== (int) $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        try {
            $this->queueService->checkIn($appointment, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'You are checked in. Serial #'.$appointment->serial_number.' is in the queue.');
    }

    public function storeFeedback(Request $request, Appointment $appointment): RedirectResponse
    {
        if ((int) $appointment->patient_id !== (int) $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($appointment->status !== 'completed') {
            return back()->withErrors(['feedback' => 'Feedback is available after the consultation is completed.']);
        }

        if ($appointment->feedback !== null) {
            return back()->withErrors(['feedback' => 'Feedback was already submitted for this visit.']);
        }

        $validated = $request->validate([
            'waiting_rating' => ['required', 'integer', 'between:1,5'],
            'doctor_rating' => ['required', 'integer', 'between:1,5'],
            'system_rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        AppointmentFeedback::query()->create([
            'appointment_id' => $appointment->id,
            'patient_id' => $request->user()->id,
            ...$validated,
        ]);

        return back()->with('status', 'Thank you. Your feedback is visible only to the hospital team.');
    }

    /**
     * The patient's soonest pending or confirmed visit from today onward.
     */
    private function upcomingVisit(int $patientId): ?Appointment
    {
        return Appointment::query()
            ->where('patient_id', $patientId)
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->whereDate('appointment_date', '>=', today())
            ->with(['doctor.liveQueue', 'doctor.doctorProfile.department'])
            ->orderBy('appointment_date')
            ->first();
    }
}
