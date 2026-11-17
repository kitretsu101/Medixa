<?php

namespace App\Policies;

use App\Models\BloodDonor;
use App\Models\User;

class BloodDonorPolicy
{
    public function update(User $user, BloodDonor $bloodDonor): bool
    {
        return $user->id === $bloodDonor->user_id;
    }
}
