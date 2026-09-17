<?php

namespace Database\Factories;

use App\Models\Vehicule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicule>
 */
class VehiculeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'AL-'.$this->faker->unique()->numerify('###'),
            'libelle' => $this->faker->randomElement(['Bus 30 places', 'Minicar 20 places', 'Car 50 places']).' - '.$this->faker->lastName(),
            'actif' => true,
        ];
    }
}
