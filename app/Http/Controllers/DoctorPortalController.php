<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ConsentToken;
use App\Models\LabReport;
use App\Models\LiveQueue;
use App\Models\Prescription;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ConsentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DoctorPortalController extends Controller
{
    public function __construct(
        protected ConsentService $consentService,
        protected AuditService $auditService,
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

        if (! $patient->isPatient()) {
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

        return redirect()->route('doctor.dashboard')->with('success', 'Prescription successfully created and appointment completed.');
    }
}
