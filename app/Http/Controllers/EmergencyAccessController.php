<?php

namespace App\Http\Controllers;

use App\Models\LabReport;
use App\Models\User;
use App\Services\EmergencyAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class EmergencyAccessController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        User $patient,
        string $resourceType,
        int $resourceId,
        EmergencyAccessService $emergencyAccess,
    ): Response {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10'],
        ]);

        abort_unless($patient->isPatient(), 404);
        $resource = $emergencyAccess->resourceForPatient($patient->id, $resourceType, $resourceId);
        $headers = [
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($resource instanceof LabReport) {
            $disk = Storage::disk('private');
            abort_unless(filled($resource->file_path) && $disk->exists($resource->file_path), 404);

            $extension = pathinfo($resource->file_path, PATHINFO_EXTENSION) ?: 'pdf';
            $response = $disk->response($resource->file_path, $resource->report_title.'.'.$extension, $headers);
        } else {
            $response = response()->view('doctor.emergency-prescription', [
                'patient' => $patient,
                'prescription' => $resource,
            ], 200, $headers);
        }

        $emergencyAccess->grantEmergencyAccess(
            doctorId: $request->user()->id,
            patientId: $patient->id,
            resourceType: $resourceType,
            resourceId: $resourceId,
            reason: $validated['reason'],
            ipAddress: $request->ip(),
        );

        return $response;
    }
}
