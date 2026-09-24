<?php

namespace Database\Factories;

use App\Models\Financement;
use App\Models\Preteur;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Financement>
 */
class FinancementFactory extends Factory
{
    protected $model = Financement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $montantTotal = $this->faker->numberBetween(100000, 2000000);

        return [
            'reference' => strtoupper($this->faker->unique()->bothify('FIN-####')),
            'preteur_id' => Preteur::factory(),
            'preteur_nom' => $this->faker->company(),
            'date_financement' => now(),
            'montant_total' => $montantTotal,
            'montant_rembourse' => 0,
            'montant_restant' => $montantTotal,
            'statut' => 'en_cours',
            'user_id' => User::factory(),
        ];
    }
}
