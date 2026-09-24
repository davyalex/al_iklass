<?php

namespace Database\Seeders;

use App\Models\Preteur;
use App\Models\TypePreteur;
use Illuminate\Database\Seeder;

class PreteurSeeder extends Seeder
{
    public function run(): void
    {
        $banque = TypePreteur::where('code', 'banque')->firstOrFail();
        $personne = TypePreteur::where('code', 'personne')->firstOrFail();

        foreach ([
            ['nom' => 'SGBCI — Société Générale Côte d\'Ivoire', 'type' => $banque, 'telephone' => '2720200000', 'email' => 'entreprises@sgbci.ci', 'adresse' => 'Avenue Lamblin, Plateau, Abidjan'],
            ['nom' => 'Ecobank Côte d\'Ivoire', 'type' => $banque, 'telephone' => '2720301717', 'email' => 'contact@ecobank.ci', 'adresse' => 'Boulevard Botreau Roussel, Plateau, Abidjan'],
            ['nom' => 'NSIA Banque', 'type' => $banque, 'telephone' => '2720300000', 'email' => null, 'adresse' => 'Avenue Franchet d\'Esperey, Plateau, Abidjan'],
            ['nom' => 'Kouassi Amenan Yolande', 'type' => $personne, 'telephone' => '0709112233', 'email' => null, 'adresse' => 'Cocody, Abidjan'],
            ['nom' => 'Traoré Mamadou', 'type' => $personne, 'telephone' => '0506778899', 'email' => 'mtraore@gmail.com', 'adresse' => 'Yopougon, Abidjan'],
        ] as $preteur) {
            Preteur::firstOrCreate(['nom' => $preteur['nom']], [
                'type_preteur_id' => $preteur['type']->id,
                'type_preteur_code' => $preteur['type']->code,
                'type_preteur_libelle' => $preteur['type']->libelle,
                'telephone' => $preteur['telephone'],
                'email' => $preteur['email'],
                'adresse' => $preteur['adresse'],
                'actif' => true,
            ]);
        }
    }
}
