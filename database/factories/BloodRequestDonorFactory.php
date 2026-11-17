<?php

namespace Database\Factories;

use App\Models\BloodDonor;
use App\Models\BloodRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class BloodRequestDonorFactory extends Factory
{
    public function definition(): array
    {
        return ['blood_request_id' => BloodRequest::factory()->active(), 'blood_donor_id' => BloodDonor::factory(), 'status' => 'interested', 'response_at' => now()];
    }
}
