<?php

namespace App\Services;

use App\Models\BloodDonor;
use App\Models\DonorContactRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DonorContactService
{
    /** @param array<string, mixed> $data */
    public function create(BloodDonor $donor, array $data): DonorContactRequest
    {
        return DB::transaction(function () use ($donor, $data): DonorContactRequest {
            $locked = BloodDonor::whereKey($donor->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->acceptsContactRequests(), 404);
            if ($locked->contactRequests()->where('requester_phone', $data['requester_phone'])->where('created_at', '>', now()->subHours(24))->exists()) {
                throw ValidationException::withMessages(['requester_phone' => 'You have already requested this donor within the last 24 hours. Please wait for them to contact you.']);
            }

            return $locked->contactRequests()->create([
                ...array_intersect_key($data, array_flip(['requester_name', 'requester_phone', 'blood_group_needed', 'units_needed', 'hospital_name', 'required_date', 'emergency_level', 'message'])),
                'location' => $data['district'].' · '.$data['area'],
            ]);
        });
    }

    public function transition(BloodDonor $donor, int $requestId, string $status): void
    {
        DB::transaction(function () use ($donor, $requestId, $status): void {
            $contactRequest = $donor->contactRequests()->whereKey($requestId)->lockForUpdate()->firstOrFail();
            $next = match ($contactRequest->status) {
                'pending' => 'contacted',
                'contacted' => 'closed',
                default => null,
            };
            if ($status !== $next) {
                throw ValidationException::withMessages(['status' => 'Requests must move from pending to contacted, then closed.']);
            }
            $contactRequest->status = $status;
            $contactRequest->save();
        });
    }
}
