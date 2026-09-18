<?php

namespace Database\Seeders;

use App\Models\Vehicule;
use Illuminate\Database\Seeder;

class VehiculeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'AL-001', 'libelle' => 'Bus 30 places - Koffi'],
            ['code' => 'AL-002', 'libelle' => 'Bus 30 places - Ouattara'],
            ['code' => 'AL-003', 'libelle' => 'Minicar 20 places - Traoré'],
            ['code' => 'AL-004', 'libelle' => 'Minicar 20 places - Bamba'],
            ['code' => 'AL-005', 'libelle' => 'Car 50 places - Diabaté'],
            ['code' => 'AL-006', 'libelle' => 'Car 50 places - Kouassi'],
            ['code' => 'AL-007', 'libelle' => 'Minicar 20 places - Yao'],
            ['code' => 'AL-008', 'libelle' => 'Bus 30 places - Aka'],
        ] as $vehicule) {
            Vehicule::firstOrCreate(['code' => $vehicule['code']], [
                'libelle' => $vehicule['libelle'],
                'actif' => true,
            ]);
        }
    }
}
