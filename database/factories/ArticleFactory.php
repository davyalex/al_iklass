<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => strtoupper($this->faker->unique()->bothify('ART-####')),
            'nom' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'categorie_id' => null,
            'unite' => $this->faker->randomElement(['pièce', 'litre', 'kg']),
            'quantite_stock' => $this->faker->numberBetween(0, 100),
            'prix_achat' => $this->faker->numberBetween(1000, 50000),
            'prix_vente' => $this->faker->numberBetween(1500, 60000),
            'seuil_alerte' => 5,
            'actif' => true,
        ];
    }
}
