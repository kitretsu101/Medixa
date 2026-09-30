<?php

namespace App\Services;

use App\Models\ConsentToken;
use Illuminate\Database\Eloquent\Collection;

class ConsentService
{
    /**
     * Issue a new consent token for a patient to grant access to a grantee.
     */
    public function issue(
        int $patientId,
        int $granteeId,
        string $resourceType,
        int $resourceId,
        ?int $ttlMinutes = null
    ): ConsentToken {
        $expiresAt = $ttlMinutes !== null && $ttlMinutes > 0
            ? now()->addMinutes($ttlMinutes)
            : null;

        return ConsentToken::create([
            'patient_id' => $patientId,
            'grantee_id' => $granteeId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'issued_at' => now(),
            'expires_at' => $expiresAt,
            'revoked_at' => null,
        ]);
    }

    /**
     * Validate whether a consent token exists, is not revoked, and is not expired.
     */
    public function validate(string $tokenId): bool
    {
        $token = ConsentToken::find($tokenId);

        if (! $token) {
            return false;
        }

        return $token->isActive();
    }

    /**
     * Revoke a consent token immediately.
     */
    public function revoke(string $tokenId): bool
    {
        $token = ConsentToken::find($tokenId);

        if (! $token) {
            return false;
        }

        $token->update([
            'revoked_at' => now(),
        ]);

        return true;
    }

    /**
     * Retrieve all currently active consent tokens for a given patient.
     *
     * @return Collection<int, ConsentToken>
     */
    public function activeTokensForPatient(int $patientId): Collection
    {
        return ConsentToken::query()
            ->where('patient_id', $patientId)
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with(['grantee', 'patient'])
            ->latest('issued_at')
            ->get();
    }

    /**
     * Find a currently valid active consent token for a specific grantee and resource.
     */
    public function findValidToken(
        int $granteeId,
        string $resourceType,
        int $resourceId
    ): ?ConsentToken {
        return ConsentToken::query()
            ->where('grantee_id', $granteeId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * All consent tokens for a patient (including expired and revoked) for the Consent Center UI.
     *
     * @return Collection<int, ConsentToken>
     */
    public function allTokensForPatient(int $patientId): Collection
    {
        return ConsentToken::query()
            ->where('patient_id', $patientId)
            ->with(['grantee'])
            ->latest('issued_at')
            ->get();
    }
}
