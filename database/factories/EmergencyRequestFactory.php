<?php

namespace Database\Factories;

use App\Models\EmergencyRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyRequest>
 */
class EmergencyRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_name' => fake()->name(),
            'contact_phone' => '01712345678',
            'location' => fake()->streetAddress(),
            'issue_type' => 'other',
            'status' => 'pending',
            'handled_by' => null,
            'note' => null,
        ];
    }
}
