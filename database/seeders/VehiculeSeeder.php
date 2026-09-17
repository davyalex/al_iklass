<?php

namespace Database\Seeders;

use App\Models\Vehicule;
use Illuminate\Database\Seeder;

class VehiculeSeeder extends Seeder
{
    public function run(): void
    {
        if (Vehicule::count() > 0) {
            return;
        }

        Vehicule::factory()->count(8)->create();
    }
}
