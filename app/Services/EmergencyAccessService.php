<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LabReport;
use App\Models\Prescription;
use App\Models\User;
use App\Support\DoctorName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmergencyAccessService
{
    public function __construct(private NotificationService $notifications) {}

    public function grantEmergencyAccess(
        int $doctorId,
        int $patientId,
        string $resourceType,
        int $resourceId,
        string $reason,
        ?string $ipAddress = null,
    ): bool {
        Validator::make(['reason' => Str::trim($reason)], [
            'reason' => ['required', 'string', 'min:10'],
        ])->validate();

        $doctor = User::query()->findOrFail($doctorId);
        abort_unless($doctor->isDoctor(), 403);

        $patient = User::query()->findOrFail($patientId);
        abort_unless($patient->isPatient(), 404);

        $this->resourceForPatient($patientId, $resourceType, $resourceId);

        return DB::transaction(function () use ($doctor, $patient, $resourceType, $resourceId, $reason, $ipAddress): bool {
            $accessedAt = now();

            AuditLog::create([
                'actor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'consent_token_id' => null,
                'ip_address' => $ipAddress,
                'accessed_at' => $accessedAt,
                'is_emergency_override' => true,
                'override_reason' => $reason,
            ]);

            $this->notifications->alert(
                $patient,
                'Emergency access to your record',
                DoctorName::format($doctor->name).' used emergency access to view your '
                    .str_replace('_', ' ', $resourceType).' on '.$accessedAt->format('Y-m-d H:i:s')
                    .' — reason: '.$reason,
                route('patient.consent-center'),
                'warning',
            );

            return true;
        });
    }

    public function resourceForPatient(int $patientId, string $resourceType, int $resourceId): LabReport|Prescription
    {
        $model = match ($resourceType) {
            'lab_report' => LabReport::class,
            'prescription' => Prescription::class,
            default => abort(404),
        };

        return $model::query()->where('patient_id', $patientId)->findOrFail($resourceId);
    }
}
