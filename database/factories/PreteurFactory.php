<?php

namespace Database\Factories;

use App\Models\Preteur;
use App\Models\TypePreteur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Preteur>
 */
class PreteurFactory extends Factory
{
    protected $model = Preteur::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => $this->faker->company(),
            'type_preteur_id' => TypePreteur::factory(),
            'type_preteur_code' => fn (array $attributes) => TypePreteur::find($attributes['type_preteur_id'])?->code ?? 'banque',
            'type_preteur_libelle' => fn (array $attributes) => TypePreteur::find($attributes['type_preteur_id'])?->libelle ?? 'Banque',
            'telephone' => $this->faker->numerify('07########'),
            'email' => $this->faker->unique()->safeEmail(),
            'adresse' => $this->faker->address(),
            'actif' => true,
        ];
    }
}
