<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BloodDonorFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'full_name' => fake()->name(), 'blood_group' => 'O-', 'phone' => '01812345678', 'district' => 'Dhaka', 'area' => 'Dhanmondi', 'last_donation_date' => null, 'availability_status' => 'available', 'public_profile' => true, 'contact_consent' => true];
    }
}
