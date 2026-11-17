<?php

namespace Database\Factories;

use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BloodDonationRecordFactory extends Factory
{
    public function definition(): array
    {
        return ['blood_request_id' => BloodRequest::factory()->active(), 'blood_donor_id' => BloodDonor::factory(), 'donation_date' => today(), 'units_donated' => 1, 'hospital_name' => 'Medixa Hospital', 'status' => 'verified', 'verified_by' => User::factory()->admin()];
    }
}
