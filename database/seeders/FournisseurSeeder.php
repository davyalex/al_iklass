<?php

namespace Database\Seeders;

use App\Models\Fournisseur;
use Illuminate\Database\Seeder;

class FournisseurSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['nom' => 'Ivoire Auto Pièces', 'telephone' => '0708001122', 'email' => 'contact@ivoireautopieces.ci', 'adresse' => 'Zone industrielle de Yopougon, Abidjan'],
            ['nom' => 'CFAO Motors Côte d\'Ivoire', 'telephone' => '2721250000', 'email' => 'commercial@cfaomotors.ci', 'adresse' => 'Boulevard de Marseille, Treichville, Abidjan'],
            ['nom' => 'Garage Moderne du Plateau', 'telephone' => '0102030405', 'email' => null, 'adresse' => 'Avenue Chardy, Plateau, Abidjan'],
            ['nom' => 'Société Africaine de Pneumatiques', 'telephone' => '0506070809', 'email' => 'sav@safripneu.ci', 'adresse' => 'Route de Bassam, Marcory, Abidjan'],
        ] as $fournisseur) {
            Fournisseur::firstOrCreate(['nom' => $fournisseur['nom']], [
                'telephone' => $fournisseur['telephone'],
                'email' => $fournisseur['email'],
                'adresse' => $fournisseur['adresse'],
                'actif' => true,
            ]);
        }
    }
}
