<?php

namespace Database\Factories;

use App\Models\BloodDonor;
use Illuminate\Database\Eloquent\Factories\Factory;

class DonorPasswordResetFactory extends Factory
{
    public function definition(): array
    {
        return ['donor_id' => BloodDonor::factory()->independent(), 'token_hash' => hash('sha256', 'test-reset-token'), 'expires_at' => now()->addHours(24)];
    }
}
