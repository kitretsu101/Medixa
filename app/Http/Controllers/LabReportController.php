<?php

namespace App\Http\Controllers;

use App\Models\LabReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class LabReportController extends Controller
{
    /**
     * Display the specified lab report.
     */
    public function show(Request $request, LabReport $labReport): JsonResponse
    {
        // Generate a signed, short-lived temporary download URL (2 minutes)
        $downloadUrl = URL::signedRoute(
            'patient.lab-reports.download',
            ['labReport' => $labReport->id],
            now()->addMinutes(2),
        );

        return response()->json([
            'status' => 'success',
            'data' => $labReport,
            'download_url' => $downloadUrl,
        ]);
    }

    /**
     * Download a lab report file via signed, short-lived temporary URL.
     *
     * The route is protected by `signed` middleware — unsigned or
     * expired requests are rejected automatically. URLs expire
     * after 2 minutes.
     */
    public function download(Request $request, LabReport $labReport): Response
    {
        $user = $request->user();

        // Patient can download their own report, doctor can download patient report, or admin
        $isPatient = (int) $user->id === (int) $labReport->patient_id;
        $isDoctor = (int) $user->id === (int) $labReport->doctor_id || $user->isDoctor();
        $isAdmin = $user->isAdmin();

        if (! $user || (! $isPatient && ! $isDoctor && ! $isAdmin)) {
            abort(Response::HTTP_FORBIDDEN, 'You are not authorized to download this lab report.');
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($labReport->file_path)) {
            abort(Response::HTTP_NOT_FOUND, 'Lab report file not found.');
        }

        return $disk->download(
            $labReport->file_path,
            $labReport->report_title.'.pdf',
        );
    }
}
