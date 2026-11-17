<?php

namespace Database\Factories;

use App\Models\BloodRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class BloodRequestEventFactory extends Factory
{
    public function definition(): array
    {
        return ['blood_request_id' => BloodRequest::factory(), 'action' => 'submitted', 'notes' => null];
    }
}
