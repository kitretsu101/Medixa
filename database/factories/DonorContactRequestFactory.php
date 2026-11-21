<?php

namespace Database\Factories;

use App\Models\BloodDonor;
use Illuminate\Database\Eloquent\Factories\Factory;

class DonorContactRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'blood_donor_id' => BloodDonor::factory()->independent()->phoneVerified(),
            'requester_name' => fake()->name(),
            'requester_phone' => fake()->numerify('017########'),
            'blood_group_needed' => 'O-',
            'units_needed' => 1,
            'hospital_name' => 'Medixa Hospital',
            'location' => 'Dhaka · Dhanmondi',
            'required_date' => today()->addDay(),
            'emergency_level' => 'normal',
            'message' => 'Please contact me about this request.',
            'status' => 'pending',
        ];
    }
}
