<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ParametreSeeder::class,
            AdminUserSeeder::class,
            DemoUserSeeder::class,
            StatutVehiculeSeeder::class,
            TypeOperationSeeder::class,
            VehiculeSeeder::class,
            StockReferenceSeeder::class,
            ArticleSeeder::class,
            FournisseurSeeder::class,
            AchatSeeder::class,
            SortieStockSeeder::class,
            InventaireSeeder::class,
        ]);
    }
}
