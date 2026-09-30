<?php

namespace Database\Factories;

use App\Models\LabReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabReport>
 */
class LabReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => User::factory()->patient(),
            'doctor_id' => User::factory()->doctor(),
            'report_title' => fake()->sentence(3),
            'file_path' => 'reports/'.fake()->uuid().'.pdf',
            'review_status' => 'pending',
            'uploaded_at' => now(),
        ];
    }
}
