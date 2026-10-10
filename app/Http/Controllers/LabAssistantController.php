<?php

namespace App\Http\Controllers;

use App\Models\LabReport;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ConsentService;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class LabAssistantController extends Controller
{
    public function __construct(
        protected ConsentService $consentService,
        protected AuditService $auditService,
        protected NotificationService $notificationService,
    ) {}

    public function dashboard(): View
    {
        return view('lab.dashboard', [
            'pendingCount' => $this->pendingReports()->count(),
            'uploadedToday' => LabReport::query()
                ->where('upload_status', 'uploaded')
                ->whereDate('uploaded_at', today())
                ->count(),
            'uploadedCount' => LabReport::query()->where('upload_status', 'uploaded')->count(),
            'reports' => $this->pendingReports()->limit(12)->get($this->columns()),
        ]);
    }

    public function reports(Request $request): View
    {
        $reference = strtoupper(trim($request->string('reference')->toString()));
        $notice = null;
        $matches = new Collection;

        if ($reference !== '') {
            if (preg_match('/^PAT-(\d+)$/', $reference, $found) === 1) {
                $matches = $this->pendingReports()
                    ->where('patient_id', (int) $found[1])
                    ->get($this->columns());

                if ($matches->isEmpty()) {
                    $notice = 'No pending report matches that reference.';
                }
            } else {
                $report = LabReport::query()->where('reference', $reference)->first($this->columns());

                if ($report === null) {
                    $notice = 'No report matches that reference.';
                } elseif (! $report->isPendingUpload()) {
                    $notice = 'This report has already been uploaded.';
                } else {
                    $matches = new Collection([$report]);
                }
            }
        }

        return view('lab.reports', [
            'reference' => $reference,
            'notice' => $notice,
            'matches' => $matches,
            'reports' => $reference === '' ? $this->pendingReports()->get($this->columns()) : new Collection,
        ]);
    }

    public function upload(LabReport $labReport): View
    {
        $report = LabReport::query()->whereKey($labReport->id)->firstOrFail($this->columns());

        return view('lab.upload', [
            'report' => $report,
            'alreadyUploaded' => ! $report->isPendingUpload(),
        ]);
    }

    public function store(Request $request, LabReport $labReport): RedirectResponse
    {
        $report = LabReport::query()->whereKey($labReport->id)->firstOrFail($this->columns());

        if (! $report->isPendingUpload()) {
            return redirect()
                ->route('lab.reports.upload', $report)
                ->withErrors(['report_file' => 'This report has already been uploaded.']);
        }

        $request->validate([
            'report_file' => [
                'required',
                'file',
                'max:10240',
                'extensions:pdf,jpg,jpeg,png',
                'mimes:pdf,jpg,jpeg,png',
            ],
        ]);

        $path = $request->file('report_file')->store('lab-reports', 'private');

        $updated = LabReport::query()
            ->whereKey($report->id)
            ->where('upload_status', 'pending_upload')
            ->whereNull('file_path')
            ->update([
                'file_path' => $path,
                'upload_status' => 'uploaded',
                'uploaded_by' => $request->user()->id,
                'uploaded_at' => now(),
            ]);

        if ($updated === 0) {
            Storage::disk('private')->delete($path);

            return redirect()
                ->route('lab.reports.upload', $report)
                ->withErrors(['report_file' => 'This report has already been uploaded.']);
        }

        $stored = LabReport::query()->findOrFail($report->id);

        $this->consentService->issue(
            patientId: (int) $stored->patient_id,
            granteeId: (int) $stored->doctor_id,
            resourceType: 'lab_report',
            resourceId: $stored->id,
            ttlMinutes: 1440,
        );

        $this->auditService->log(
            actorId: (int) $request->user()->id,
            patientId: (int) $stored->patient_id,
            resourceType: 'lab_report',
            resourceId: $stored->id,
            ipAddress: $request->ip(),
        );

        $patient = User::query()->find($stored->patient_id);
        $doctor = User::query()->find($stored->doctor_id);

        if ($patient !== null) {
            $this->notificationService->alert(
                $patient,
                'Laboratory report available',
                'Your laboratory report is now available.',
                route('patient.timeline'),
            );
        }

        if ($doctor !== null) {
            $this->notificationService->alert(
                $doctor,
                'Laboratory report ready',
                'A new laboratory report is available for review.',
                route('doctor.pending-reviews'),
            );
        }

        return redirect()
            ->route('lab.history')
            ->with('status', $stored->reference.' uploaded.');
    }

    public function history(Request $request): View
    {
        $reports = LabReport::query()
            ->where('uploaded_by', $request->user()->id)
            ->latest('uploaded_at')
            ->get(['id', 'reference', 'report_title', 'upload_status', 'uploaded_at']);

        return view('lab.history', compact('reports'));
    }

    /**
     * @return Builder<LabReport>
     */
    private function pendingReports(): Builder
    {
        return LabReport::query()
            ->where('upload_status', 'pending_upload')
            ->whereNull('file_path')
            ->oldest();
    }

    /**
     * Columns safe to render for a lab assistant. Patient identity stays off this list.
     *
     * @return list<string>
     */
    private function columns(): array
    {
        return ['id', 'reference', 'patient_id', 'report_title', 'upload_status', 'file_path', 'created_at', 'uploaded_at'];
    }
}
