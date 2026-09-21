<?php

namespace Database\Seeders;

use App\Models\Caisse;
use App\Models\CategorieArticle;
use App\Models\ModePaiement;
use App\Models\Unite;
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
            // withTrashed() : "code" est unique et non filtré sur deleted_at, donc
            // firstOrCreate() percuterait une catégorie archivée au lieu de la
            // retrouver (elle est exclue du scope par défaut).
            if (! CategorieArticle::withTrashed()->where('code', $categorie['code'])->exists()) {
                CategorieArticle::create($categorie);
            }
        }

        foreach ([
            ['code' => 'especes', 'libelle' => 'Espèces'],
            ['code' => 'wave', 'libelle' => 'Wave'],
            ['code' => 'virement', 'libelle' => 'Virement bancaire'],
            ['code' => 'cheque', 'libelle' => 'Chèque'],
        ] as $mode) {
            ModePaiement::firstOrCreate(['code' => $mode['code']], $mode);
        }

        foreach (['pièce', 'jeu', 'bidon', 'litre', 'kg', 'mètre'] as $libelle) {
            if (! Unite::withTrashed()->where('libelle', $libelle)->exists()) {
                Unite::create(['libelle' => $libelle]);
            }
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
