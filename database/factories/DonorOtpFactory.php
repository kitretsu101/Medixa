<?php

namespace Database\Factories;

use App\Models\BloodDonor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class DonorOtpFactory extends Factory
{
    public function definition(): array
    {
        return ['donor_id' => BloodDonor::factory()->independent(), 'code_hash' => fn (array $attributes): string => Hash::make(BloodDonor::findOrFail($attributes['donor_id'])->phone.'|123456'), 'expires_at' => now()->addMinutes(10), 'attempts' => 0];
    }
}
