<?php

namespace App\Services;

use App\Models\BloodDonationRecord;
use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BloodDonationService
{
    public function __construct(private BloodMatchingService $matching) {}

    /** @param array<string, mixed> $data */
    public function record(BloodRequest $bloodRequest, User $admin, array $data): BloodDonationRecord
    {
        abort_unless($admin->role === 'admin', 403);
        try {
            return DB::transaction(function () use ($bloodRequest, $admin, $data): BloodDonationRecord {
                $locked = BloodRequest::whereKey($bloodRequest->id)->lockForUpdate()->firstOrFail();
                $donor = BloodDonor::whereKey($data['blood_donor_id'])->lockForUpdate()->firstOrFail();
                if (! $locked->isActive() || $data['units_donated'] > $locked->units_required - $locked->units_fulfilled
                    || ! $locked->interests()->where('blood_donor_id', $donor->id)->exists()
                    || ! in_array($donor->blood_group, $this->matching->compatibleDonorGroups($locked->blood_group), true)) {
                    throw ValidationException::withMessages(['units_donated' => 'Verify an interested, compatible donor and do not exceed the remaining units of an active request.']);
                }
                $record = $locked->donations()->create([
                    'blood_donor_id' => $donor->id, 'donation_date' => $data['donation_date'], 'units_donated' => $data['units_donated'],
                    'hospital_name' => $locked->hospital_name, 'status' => 'verified', 'verified_by' => $admin->id,
                ]);
                $locked->units_fulfilled += $data['units_donated'];
                if ($locked->units_fulfilled >= $locked->units_required) {
                    $locked->status = 'fulfilled';
                    $locked->fulfilled_at = now();
                }
                $locked->save();
                $donor->availability_status = 'temporarily_unavailable';
                if ($donor->last_donation_date === null || $donor->last_donation_date->lt(Carbon::parse($data['donation_date']))) {
                    $donor->last_donation_date = $data['donation_date'];
                }
                $donor->save();
                $locked->events()->create(['user_id' => $admin->id, 'action' => $locked->status === 'fulfilled' ? 'fulfilled' : 'donation_verified', 'notes' => 'Verified '.$data['units_donated'].' unit(s). Record #'.$record->id]);

                return $record;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['donation_date' => 'This donor already has a donation recorded for this request on this date.']);
        }
    }
}
