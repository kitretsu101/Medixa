<?php

namespace App\Services;

use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Models\BloodRequestDonor;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BloodRequestService
{
    public function __construct(private BloodMatchingService $matching) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $user): BloodRequest
    {
        $data = array_intersect_key($data, array_flip(['patient_name', 'contact_name', 'blood_group', 'units_required', 'hospital_name', 'district', 'area', 'required_date', 'emergency_level', 'contact_number', 'description', 'contact_consent']));
        $hash = hash('sha256', implode('|', [$data['contact_number'], $data['blood_group'], mb_strtolower(trim($data['hospital_name'])), $data['required_date'], today()->toDateString()]));
        try {
            return DB::transaction(function () use ($data, $user, $hash): BloodRequest {
                $bloodRequest = BloodRequest::create([...$data, 'user_id' => $user?->id, 'request_code' => 'BC-'.Str::uuid(), 'submission_hash' => $hash, 'status' => 'pending', 'units_fulfilled' => 0, 'expires_at' => Carbon::parse($data['required_date'])->endOfDay()]);
                $bloodRequest->events()->create(['user_id' => $user?->id, 'action' => 'submitted']);

                return $bloodRequest;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (! BloodRequest::where('submission_hash', $hash)->exists()) {
                throw $exception;
            }
            throw ValidationException::withMessages(['contact_number' => 'A matching blood request has already been submitted today.']);
        }
    }

    public function transition(BloodRequest $bloodRequest, User $admin, string $status, string $notes): void
    {
        abort_unless($admin->role === 'admin', 403);
        DB::transaction(function () use ($bloodRequest, $admin, $status, $notes): void {
            $locked = BloodRequest::whereKey($bloodRequest->id)->lockForUpdate()->firstOrFail();
            $allowed = match ($locked->status) {
                'pending' => ['active', 'rejected'],
                'active' => ['rejected', 'expired', 'fulfilled'],
                default => [],
            };
            if (! in_array($status, $allowed, true) || ($status === 'active' && (! $locked->expires_at->isFuture() || $locked->required_date->lt(today()))) || ($status === 'fulfilled' && $locked->units_fulfilled < $locked->units_required)) {
                throw ValidationException::withMessages(['status' => 'This status change is not permitted. Fulfillment requires verified donations covering the requested units.']);
            }
            $locked->status = $status;
            if ($status === 'active') {
                $locked->verified_at = now();
            }
            if ($status === 'fulfilled') {
                $locked->fulfilled_at = now();
            }
            if ($status === 'expired') {
                $locked->expires_at = now();
            }
            $locked->save();
            $locked->events()->create(['user_id' => $admin->id, 'action' => $status, 'notes' => $notes]);
        });
    }

    public function interest(BloodRequest $bloodRequest, BloodDonor $donor): void
    {
        DB::transaction(function () use ($bloodRequest, $donor): void {
            $locked = BloodRequest::whereKey($bloodRequest->id)->lockForUpdate()->firstOrFail();
            $lockedDonor = BloodDonor::whereKey($donor->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->isActive(), 404);
            if (! in_array($lockedDonor->blood_group, $this->matching->compatibleDonorGroups($locked->blood_group), true)) {
                throw ValidationException::withMessages(['donor' => 'Your registered blood group is not a preliminary red-cell match for this request.']);
            }
            if (! $lockedDonor->isDiscoverable()) {
                throw ValidationException::withMessages(['donor' => 'Set your donor profile to available and enable profile and contact consent before expressing interest.']);
            }
            BloodRequestDonor::firstOrCreate(['blood_request_id' => $locked->id, 'blood_donor_id' => $lockedDonor->id], ['response_at' => now()]);
        });
    }
}
