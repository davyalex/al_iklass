<?php

namespace Database\Factories;

use App\Models\Achat;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Achat>
 */
class AchatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $montantTotal = $this->faker->numberBetween(10000, 200000);

        return [
            'reference' => strtoupper($this->faker->unique()->bothify('PO-####')),
            'fournisseur_id' => Fournisseur::factory(),
            'fournisseur_nom' => $this->faker->company(),
            'date_achat' => now(),
            'montant_total' => $montantTotal,
            'montant_paye' => 0,
            'montant_restant' => $montantTotal,
            'statut_paiement' => 'credit',
            'user_id' => User::factory(),
        ];
    }
}
