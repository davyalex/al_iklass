<?php

namespace Database\Factories;

use App\Models\Fournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fournisseur>
 */
class FournisseurFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => $this->faker->company(),
            'telephone' => $this->faker->numerify('07########'),
            'email' => $this->faker->unique()->safeEmail(),
            'adresse' => $this->faker->address(),
            'actif' => true,
        ];
    }
}
