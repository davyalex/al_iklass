<?php

namespace Database\Seeders;

use App\Models\TypeOperation;
use Illuminate\Database\Seeder;

class TypeOperationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'vidange', 'libelle' => 'Vidange', 'periodicite_jours' => 90],
            ['code' => 'assurance', 'libelle' => 'Assurance', 'periodicite_jours' => 365],
            ['code' => 'visite_technique', 'libelle' => 'Visite technique', 'periodicite_jours' => 365],
        ] as $type) {
            TypeOperation::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
