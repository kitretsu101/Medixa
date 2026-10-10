<?php

namespace App\Http\Controllers;

use App\Models\LabReport;
use App\Services\ConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class LabReportController extends Controller
{
    public function __construct(
        protected ConsentService $consentService,
    ) {}

    /**
     * Display the specified lab report metadata.
     *
     * The route is behind EnsureConsent. This payload includes a two-minute
     * signed download URL; the download route checks consent again.
     */
    public function show(Request $request, LabReport $labReport): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $labReport,
            'download_url' => $labReport->getTemporaryDownloadUrl(),
        ]);
    }

    /**
     * Send an authorized user to the short-lived download URL.
     */
    public function open(LabReport $labReport): RedirectResponse
    {
        return redirect()->to($labReport->getTemporaryDownloadUrl());
    }

    /**
     * Download a lab report file via a signed, short-lived temporary URL.
     *
     * Signature expiry is enforced by the `signed` middleware. This method
     * still requires the caller to be the patient who owns the report, or a
     * grantee with a token that is neither expired nor revoked.
     */
    public function download(Request $request, LabReport $labReport): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED, 'Authentication required.');
        }

        $ownsReport = (int) $user->id === (int) $labReport->patient_id;
        $token = $this->consentService->findValidToken($user->id, 'lab_report', $labReport->id);

        if (! $ownsReport && $token === null) {
            abort(Response::HTTP_FORBIDDEN, 'You are not authorized to download this lab report.');
        }

        $disk = Storage::disk('private');

        if (! $disk->exists($labReport->file_path)) {
            abort(Response::HTTP_NOT_FOUND, 'Lab report file not found.');
        }

        $extension = pathinfo($labReport->file_path, PATHINFO_EXTENSION) ?: 'pdf';

        return $disk->download(
            $labReport->file_path,
            $labReport->report_title.'.'.$extension,
        );
    }
}
