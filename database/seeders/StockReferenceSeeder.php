<?php

namespace Database\Seeders;

use App\Models\Caisse;
use App\Models\CategorieArticle;
use App\Models\ModePaiement;
use Illuminate\Database\Seeder;

class StockReferenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'moteur', 'libelle' => 'Moteur'],
            ['code' => 'freinage', 'libelle' => 'Freinage'],
            ['code' => 'carrosserie', 'libelle' => 'Carrosserie'],
            ['code' => 'pneumatique', 'libelle' => 'Pneumatique'],
            ['code' => 'electricite', 'libelle' => 'Électricité'],
            ['code' => 'lubrifiant', 'libelle' => 'Lubrifiant & fluides'],
            ['code' => 'autre', 'libelle' => 'Autre'],
        ] as $categorie) {
            CategorieArticle::firstOrCreate(['code' => $categorie['code']], $categorie);
        }

        foreach ([
            ['code' => 'especes', 'libelle' => 'Espèces'],
            ['code' => 'wave', 'libelle' => 'Wave'],
            ['code' => 'virement', 'libelle' => 'Virement bancaire'],
            ['code' => 'cheque', 'libelle' => 'Chèque'],
        ] as $mode) {
            ModePaiement::firstOrCreate(['code' => $mode['code']], $mode);
        }

        foreach ([
            ['type' => 'versements', 'libelle' => 'Versements gestionnaires'],
            ['type' => 'ventes_externes', 'libelle' => 'Ventes externes de pièces'],
            ['type' => 'emprunt', 'libelle' => 'Emprunts / financements'],
            ['type' => 'depenses_fournisseurs', 'libelle' => 'Paiements fournisseurs'],
        ] as $caisse) {
            Caisse::firstOrCreate(['type' => $caisse['type']], $caisse);
        }
    }
}
