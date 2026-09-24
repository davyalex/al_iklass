<?php

namespace Database\Factories;

use App\Models\TypePreteur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TypePreteur>
 */
class TypePreteurFactory extends Factory
{
    protected $model = TypePreteur::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->word(),
            'libelle' => $this->faker->words(2, true),
            'actif' => true,
        ];
    }
}
