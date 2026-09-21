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
            'marque' => $this->faker->randomElement(['Toyota', 'Mercedes', 'Iveco', 'Hyundai']),
            'modele' => $this->faker->randomElement(['Coaster', 'Sprinter', 'Daily', 'County']),
            'immatriculation' => strtoupper($this->faker->unique()->bothify('##??##CI')),
            'date_mise_circulation' => $this->faker->dateTimeBetween('-8 years', '-1 year'),
            'statut_id' => null,
            'chauffeur_nom' => $this->faker->name(),
            'chauffeur_telephone' => '07'.$this->faker->numerify('########'),
            'recette_journaliere' => $this->faker->numberBetween(15000, 25000),
            'gestionnaire_id' => null,
        ];
    }
}
