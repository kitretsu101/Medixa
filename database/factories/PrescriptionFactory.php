<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'patient_id' => fn (array $attributes) => Appointment::find($attributes['appointment_id'])?->patient_id ?? User::factory()->patient(),
            'doctor_id' => fn (array $attributes) => Appointment::find($attributes['appointment_id'])?->doctor_id ?? User::factory()->doctor(),
            'diagnosis' => fake()->sentence(),
            'medicines' => [
                [
                    'name' => 'Paracetamol',
                    'dosage' => '500mg',
                    'frequency' => '1-0-1',
                    'duration' => '5 days',
                ],
            ],
            'instructions' => fake()->sentence(),
        ];
    }
}
