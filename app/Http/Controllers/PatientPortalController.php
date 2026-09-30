<?php

namespace App\Http\Controllers;

use App\Models\AssistanceRequest;
use App\Models\AuditLog;
use App\Models\LabReport;
use App\Models\PatientProfile;
use App\Models\Prescription;
use App\Models\User;
use App\Services\ConsentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PatientPortalController extends Controller
{
    public function __construct(
        protected ConsentService $consentService,
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

        return view('patient.dashboard', compact(
            'prescriptionCount',
            'labReportCount',
            'activeConsentsCount',
            'openAssistanceCount',
            'recentPrescriptions',
            'recentLabReports'
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

        return view('patient.profile', compact('user', 'profile'));
    }

    /**
     * Update the patient profile.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dob' => ['nullable', 'date', 'before:today'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
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
                'detail' => 'Status: '.ucfirst($r->review_status),
                'medicines' => null,
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
    public function revokeConsent(Request $request, string $tokenId): RedirectResponse
    {
        $this->consentService->revoke($tokenId);

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

        AssistanceRequest::create([
            'patient_id' => Auth::id(),
            'doctor_id' => $validated['doctor_id'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        return redirect()->route('patient.dashboard')
            ->with('status', 'Assistance request submitted successfully.');
    }
}
