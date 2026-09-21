<?php

namespace Database\Seeders;

use App\Models\StatutVehicule;
use Illuminate\Database\Seeder;

class StatutVehiculeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'en_circulation', 'libelle' => 'En circulation'],
            ['code' => 'depannage', 'libelle' => 'Dépannage'],
            ['code' => 'maintenance', 'libelle' => 'Maintenance'],
            ['code' => 'arret', 'libelle' => "À l'arrêt"],
        ] as $statut) {
            StatutVehicule::firstOrCreate(['code' => $statut['code']], $statut);
        }
    }
}
