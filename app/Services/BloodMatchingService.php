<?php

namespace App\Services;

use App\Models\BloodDonor;
use App\Models\BloodRequest;
use Illuminate\Database\Eloquent\Builder;

class BloodMatchingService
{
    /** Red-cell discovery only; crossmatching and donor eligibility require clinical confirmation.
     * @return list<string>
     */
    public function compatibleDonorGroups(string $recipient): array
    {
        return match ($recipient) {
            'O-' => ['O-'],
            'O+' => ['O-', 'O+'],
            'A-' => ['O-', 'A-'],
            'A+' => ['O-', 'O+', 'A-', 'A+'],
            'B-' => ['O-', 'B-'],
            'B+' => ['O-', 'O+', 'B-', 'B+'],
            'AB-' => ['O-', 'A-', 'B-', 'AB-'],
            'AB+' => BloodRequest::GROUPS,
            default => [],
        };
    }

    public function matches(BloodRequest $bloodRequest): Builder
    {
        return BloodDonor::query()->whereIn('blood_group', $this->compatibleDonorGroups($bloodRequest->blood_group))
            ->where('district', $bloodRequest->district)->where('availability_status', 'available')
            ->where('public_profile', true)->where('contact_consent', true)
            ->orderByRaw('CASE WHEN area = ? THEN 0 ELSE 1 END', [$bloodRequest->area])->orderBy('id');
    }
}
