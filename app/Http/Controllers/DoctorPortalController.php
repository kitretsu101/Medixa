<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AssistanceRequest;
use App\Models\ConsentToken;
use App\Models\LabReport;
use App\Models\LiveQueue;
use App\Models\Prescription;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ConsentService;
use App\Services\NotificationService;
use App\Services\QueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DoctorPortalController extends Controller
{
    public function __construct(
        protected ConsentService $consentService,
        protected AuditService $auditService,
        protected NotificationService $notificationService,
        protected QueueService $queueService,
    ) {}

    /**
     * Doctor dashboard — displays patient workload stats and upcoming schedule.
     */
    public function dashboard(Request $request): View
    {
        $doctor = $request->user();

        // Workload stats
        $completedPatientsCount = Appointment::where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->distinct('patient_id')
            ->count('patient_id');

        $profileCount = $doctor->doctorProfile?->patients_seen_count ?? 0;
        $totalPatientsSeen = max($completedPatientsCount, $profileCount);

        $pendingAppointments = Appointment::where('doctor_id', $doctor->id)
            ->where('status', 'pending')
            ->count();

        $pendingReportReviews = LabReport::where('doctor_id', $doctor->id)
            ->where('review_status', 'pending')
            ->where('upload_status', 'uploaded')
            ->count();

        // Recent appointments
        $recentAppointments = Appointment::where('doctor_id', $doctor->id)
            ->with(['patient.patientProfile', 'prescription'])
            ->latest('appointment_date')
            ->take(6)
            ->get();

        // Live Queue State
        $liveQueue = LiveQueue::firstOrCreate(
            ['doctor_id' => $doctor->id],
            ['current_running_serial' => 0, 'doctor_present_status' => false]
        );

        return view('doctor.dashboard', compact(
            'totalPatientsSeen',
            'pendingAppointments',
            'pendingReportReviews',
            'recentAppointments',
            'liveQueue',
        ));
    }

    /**
     * Linked patient list — distinct patients with an appointment linked to this doctor.
     */
    public function patients(Request $request): View
    {
        $doctor = $request->user();

        $patientIds = Appointment::where('doctor_id', $doctor->id)
            ->pluck('patient_id')
            ->unique();

        $patients = User::whereIn('id', $patientIds)
            ->with(['patientProfile'])
            ->paginate(15);

        // Attach last appointment and appointment counts for this doctor
        $patients->getCollection()->transform(function (User $patient) use ($doctor) {
            $patient->doctor_appointments_count = Appointment::where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->count();

            $patient->latest_appointment = Appointment::where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->latest('appointment_date')
                ->first();

            return $patient;
        });

        return view('doctor.patients', compact('patients'));
    }

    /**
     * Patient history view — displays ONLY the resources this doctor currently
     * has a valid, unexpired consent token for.
     */
    public function patientHistory(Request $request, User $patient): View
    {
        $doctor = $request->user();

        if (! $patient->isPatient() || ! $this->doctorIsLinkedTo($doctor, $patient)) {
            abort(Response::HTTP_NOT_FOUND, 'Patient not found.');
        }

        // Fetch all active, non-revoked, unexpired tokens granted by this patient to this doctor
        $activeTokens = ConsentToken::query()
            ->where('patient_id', $patient->id)
            ->where('grantee_id', $doctor->id)
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        $consentedLabReportIds = $activeTokens
            ->where('resource_type', 'lab_report')
            ->pluck('resource_id')
            ->all();

        $consentedPrescriptionIds = $activeTokens
            ->where('resource_type', 'prescription')
            ->pluck('resource_id')
            ->all();

        // Load ONLY consented resources
        $labReports = LabReport::whereIn('id', $consentedLabReportIds)
            ->where('patient_id', $patient->id)
            ->latest('uploaded_at')
            ->get();

        $prescriptions = Prescription::whereIn('id', $consentedPrescriptionIds)
            ->where('patient_id', $patient->id)
            ->with('doctor')
            ->latest()
            ->get();

        // Write audit log entry for each accessed consented resource
        foreach ($labReports as $report) {
            $token = $activeTokens->first(fn (ConsentToken $t) => $t->resource_type === 'lab_report' && (int) $t->resource_id === (int) $report->id);

            if ($token) {
                $this->auditService->log(
                    actorId: $doctor->id,
                    patientId: $patient->id,
                    resourceType: 'lab_report',
                    resourceId: $report->id,
                    consentTokenId: $token->id,
                    ipAddress: $request->ip(),
                );
            }
        }

        foreach ($prescriptions as $prescription) {
            $token = $activeTokens->first(fn (ConsentToken $t) => $t->resource_type === 'prescription' && (int) $t->resource_id === (int) $prescription->id);

            if ($token) {
                $this->auditService->log(
                    actorId: $doctor->id,
                    patientId: $patient->id,
                    resourceType: 'prescription',
                    resourceId: $prescription->id,
                    consentTokenId: $token->id,
                    ipAddress: $request->ip(),
                );
            }
        }

        return view('doctor.patient-history', [
            'patient' => $patient,
            'labReports' => $labReports,
            'prescriptions' => $prescriptions,
            'activeTokens' => $activeTokens,
        ]);
    }

    /**
     * Pending Report Reviews inbox — lab reports where doctor_id = this doctor and review_status = 'pending',
     * each showing its auto-issued consent token's expiry countdown.
     */
    public function pendingReviews(Request $request): View
    {
        $doctor = $request->user();

        $reports = LabReport::where('doctor_id', $doctor->id)
            ->where('review_status', 'pending')
            ->where('upload_status', 'uploaded')
            ->whereNotNull('file_path')
            ->with(['patient.patientProfile'])
            ->latest('uploaded_at')
            ->paginate(15);

        // Attach active consent token for each report
        $reports->getCollection()->transform(function (LabReport $report) use ($doctor) {
            $report->active_consent_token = ConsentToken::where('grantee_id', $doctor->id)
                ->where('resource_type', 'lab_report')
                ->where('resource_id', $report->id)
                ->whereNull('revoked_at')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->latest('issued_at')
                ->first();

            return $report;
        });

        return view('doctor.pending-reviews', compact('reports'));
    }

    /**
     * Digital Prescription Builder form tied to an appointment.
     */
    public function prescriptionForm(Request $request, Appointment $appointment): View
    {
        $doctor = $request->user();

        if ((int) $appointment->doctor_id !== (int) $doctor->id) {
            abort(Response::HTTP_FORBIDDEN, 'You are not assigned to this appointment.');
        }

        $appointment->load(['patient.patientProfile', 'prescription']);

        // Check if there are pending lab reports for this patient that can be reviewed
        $pendingReports = LabReport::where('patient_id', $appointment->patient_id)
            ->where('doctor_id', $doctor->id)
            ->where('review_status', 'pending')
            ->get();

        return view('doctor.prescription-builder', compact('appointment', 'pendingReports'));
    }

    /**
     * Save the prescription, mark appointment as completed,
     * and mark related pending lab reports as 'reviewed'.
     */
    public function storePrescription(Request $request, Appointment $appointment): RedirectResponse
    {
        $doctor = $request->user();

        if ((int) $appointment->doctor_id !== (int) $doctor->id) {
            abort(Response::HTTP_FORBIDDEN, 'You are not assigned to this appointment.');
        }

        $validated = $request->validate([
            'diagnosis' => ['required', 'string', 'max:5000'],
            'medicines' => ['required', 'array', 'min:1'],
            'medicines.*.name' => ['required', 'string', 'max:255'],
            'medicines.*.dosage' => ['required', 'string', 'max:100'],
            'medicines.*.frequency' => ['nullable', 'string', 'max:100'],
            'medicines.*.duration' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ]);

        // Filter out empty medicines if user left an empty row
        $medicines = array_values(array_filter($validated['medicines'], fn ($m) => ! empty($m['name'])));

        if (empty($medicines)) {
            return back()->withErrors(['medicines' => 'At least one valid medicine with name and dosage is required.'])->withInput();
        }

        // Create prescription
        $prescription = Prescription::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $doctor->id,
            'diagnosis' => $validated['diagnosis'],
            'medicines' => $medicines,
            'instructions' => $validated['instructions'] ?? null,
        ]);

        // Mark appointment as completed
        $appointment->update([
            'status' => 'completed',
            'consultation_ended_at' => $appointment->consultation_ended_at ?? now(),
            'consultation_started_at' => $appointment->consultation_started_at ?? now(),
        ]);

        // Mark any pending lab reports for this patient & doctor as reviewed
        LabReport::where('patient_id', $appointment->patient_id)
            ->where('doctor_id', $doctor->id)
            ->where('review_status', 'pending')
            ->update([
                'review_status' => 'reviewed',
            ]);

        // Update doctor's patients seen count if profile exists
        if ($doctor->doctorProfile) {
            $doctor->doctorProfile->increment('patients_seen_count');
        }

        $patient = User::query()->find($appointment->patient_id);
        if ($patient !== null) {
            $this->notificationService->alert(
                $patient,
                'New prescription',
                'Dr. '.$doctor->name.' issued a prescription for your visit.',
                route('patient.timeline'),
            );
        }

        return redirect()->route('doctor.dashboard')->with('success', 'Prescription successfully created and appointment completed.');
    }

    /**
     * Assistance requests assigned to this doctor.
     */
    public function assistance(Request $request): View
    {
        $requests = AssistanceRequest::query()
            ->where('doctor_id', $request->user()->id)
            ->with('patient')
            ->latest()
            ->paginate(15);

        return view('doctor.assistance', compact('requests'));
    }

    /**
     * Respond to an assistance request assigned to this doctor.
     */
    public function respondToAssistance(Request $request, AssistanceRequest $assistanceRequest): RedirectResponse
    {
        if ((int) $assistanceRequest->doctor_id !== (int) $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'This request is not assigned to you.');
        }

        $validated = $request->validate([
            'response' => ['required', 'string', 'max:2000'],
        ]);

        $assistanceRequest->update([
            'response' => $validated['response'],
            'status' => 'resolved',
            'responded_at' => now(),
        ]);

        $patient = $assistanceRequest->patient;
        if ($patient !== null) {
            $this->notificationService->alert(
                $patient,
                'Your doctor responded',
                'A response was added to your assistance request. This is not an emergency service.',
                route('patient.dashboard'),
            );
        }

        return redirect()->route('doctor.assistance.index')
            ->with('success', 'Response sent to the patient.');
    }

    /**
     * Create a laboratory upload task. The lab assistant later attaches the file.
     */
    public function reviewReport(Request $request, LabReport $labReport): RedirectResponse
    {
        $doctor = $request->user();

        if ((int) $labReport->doctor_id !== (int) $doctor->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($labReport->file_path === null || $labReport->upload_status !== 'uploaded') {
            abort(Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'clinical_outcome' => ['required', 'in:routine_follow_up,needs_attention,urgent_clinical_review,physical_consultation'],
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $labReport->update([
            'review_status' => 'reviewed',
            'clinical_outcome' => $validated['clinical_outcome'],
            'review_note' => $validated['review_note'] ?? null,
            'reviewed_at' => now(),
        ]);

        $this->auditService->log(
            actorId: (int) $doctor->id,
            patientId: (int) $labReport->patient_id,
            resourceType: 'lab_report',
            resourceId: $labReport->id,
            ipAddress: $request->ip(),
        );

        $patient = User::query()->find($labReport->patient_id);
        $level = in_array($validated['clinical_outcome'], ['urgent_clinical_review', 'physical_consultation'], true) ? 'urgent' : ($validated['clinical_outcome'] === 'needs_attention' ? 'warning' : 'info');
        $body = match ($validated['clinical_outcome']) {
            'physical_consultation' => 'Your doctor has requested an in-person follow-up.',
            'urgent_clinical_review' => 'Your doctor has requested clinical follow-up for a laboratory report.',
            'needs_attention' => 'Your doctor marked a laboratory report as needing attention.',
            default => 'Your laboratory report has been reviewed.',
        };

        if ($patient !== null) {
            $this->notificationService->alert($patient, 'Laboratory report reviewed', $body, route('patient.timeline'), $level);
        }

        return back()->with('success', 'Review recorded. The clinical decision stays with you.');
    }

    public function markNoShow(Request $request, Appointment $appointment): RedirectResponse
    {
        if ((int) $appointment->doctor_id !== (int) $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        try {
            $this->queueService->markNoShow($appointment, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Appointment marked as a no-show.');
    }

    public function orderLabTest(Request $request, User $patient): RedirectResponse
    {
        $doctor = $request->user();

        if (! $patient->isPatient() || ! $this->doctorIsLinkedTo($doctor, $patient)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'test_name' => ['required', 'string', 'max:255'],
        ]);

        $report = LabReport::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'report_title' => $validated['test_name'],
            'file_path' => null,
            'review_status' => 'pending',
            'upload_status' => 'pending_upload',
            'uploaded_at' => null,
        ]);

        $report->refresh();

        $this->notificationService->alert(
            $patient,
            'Laboratory test ordered',
            'Present report reference '.$report->reference.' at the laboratory.',
            route('patient.timeline'),
        );

        return back()->with('success', 'Lab order '.$report->reference.' created. Give this reference to the laboratory.');
    }

    /**
     * A doctor may open a chart only when an appointment, assigned report, or consent token links them.
     */
    private function doctorIsLinkedTo(User $doctor, User $patient): bool
    {
        return Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->exists()
            || LabReport::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->exists()
            || ConsentToken::query()
                ->where('grantee_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->exists();
    }
}
