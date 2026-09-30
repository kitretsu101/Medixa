<?php

namespace Database\Factories;

use App\Models\ConsentToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsentToken>
 */
class ConsentTokenFactory extends Factory
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
            'grantee_id' => User::factory()->doctor(),
            'resource_type' => 'lab_report',
            'resource_id' => 1,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'revoked_at' => null,
        ];
    }

    /**
     * Indicate that the token has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(10),
        ]);
    }

    /**
     * Indicate that the token has been revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now()->subMinutes(5),
        ]);
    }
}
