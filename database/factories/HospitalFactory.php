<?php

namespace Database\Factories;

use App\Models\Hospital;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Hospital> */
class HospitalFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->company().' Hospital'];
    }
}
