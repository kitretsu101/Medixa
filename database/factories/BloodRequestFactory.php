<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BloodRequestFactory extends Factory
{
    public function definition(): array
    {
        return ['request_code' => 'BC-'.Str::uuid(), 'submission_hash' => hash('sha256', Str::uuid()), 'user_id' => null, 'patient_name' => fake()->name(), 'blood_group' => 'A+', 'units_required' => 2, 'units_fulfilled' => 0, 'hospital_name' => 'Medixa Hospital', 'district' => 'Dhaka', 'area' => 'Dhanmondi', 'required_date' => today()->addDay(), 'contact_name' => fake()->name(), 'contact_number' => '01712345678', 'emergency_level' => 'urgent', 'description' => null, 'status' => 'pending', 'contact_consent' => false, 'verified_at' => null, 'fulfilled_at' => null, 'expires_at' => today()->addDay()->endOfDay()];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => 'active', 'verified_at' => now()]);
    }
}
