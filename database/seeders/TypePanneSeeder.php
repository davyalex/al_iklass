<?php

namespace Database\Seeders;

use App\Models\TypePanne;
use Illuminate\Database\Seeder;

class TypePanneSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'moteur', 'libelle' => 'Moteur'],
            ['code' => 'freinage', 'libelle' => 'Freinage'],
            ['code' => 'electrique', 'libelle' => 'Électrique'],
            ['code' => 'carrosserie', 'libelle' => 'Carrosserie'],
            ['code' => 'pneumatique', 'libelle' => 'Pneumatique'],
            ['code' => 'autre', 'libelle' => 'Autre'],
        ] as $type) {
            TypePanne::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
