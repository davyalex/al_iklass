<?php

namespace Database\Seeders;

use App\Models\TypePreteur;
use Illuminate\Database\Seeder;

class TypePreteurSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'banque', 'libelle' => 'Banque'],
            ['code' => 'personne', 'libelle' => 'Personne'],
        ] as $type) {
            TypePreteur::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
