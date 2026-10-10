<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    /**
     * Write an immutable audit log entry for resource access.
     */
    public function log(
        int $actorId,
        int $patientId,
        string $resourceType,
        int $resourceId,
        ?string $consentTokenId = null,
        ?string $ipAddress = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => $actorId,
            'patient_id' => $patientId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'consent_token_id' => $consentTokenId,
            'ip_address' => $ipAddress,
            'accessed_at' => now(),
        ]);
    }
}
