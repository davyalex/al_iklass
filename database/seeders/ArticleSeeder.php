<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\CategorieArticle;
use App\Models\Unite;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['reference' => 'MOT-0001', 'nom' => 'Filtre à huile', 'categorie' => 'moteur', 'unite' => 'pièce', 'prix_achat' => 3500, 'prix_vente' => 5000, 'quantite_stock' => 25, 'seuil_alerte' => 5],
            ['reference' => 'MOT-0002', 'nom' => 'Filtre à air', 'categorie' => 'moteur', 'unite' => 'pièce', 'prix_achat' => 4000, 'prix_vente' => 6000, 'quantite_stock' => 20, 'seuil_alerte' => 5],
            ['reference' => 'MOT-0003', 'nom' => 'Courroie de distribution', 'categorie' => 'moteur', 'unite' => 'pièce', 'prix_achat' => 15000, 'prix_vente' => 22000, 'quantite_stock' => 8, 'seuil_alerte' => 2],
            ['reference' => 'FRE-0001', 'nom' => 'Plaquettes de frein avant', 'categorie' => 'freinage', 'unite' => 'jeu', 'prix_achat' => 12000, 'prix_vente' => 18000, 'quantite_stock' => 15, 'seuil_alerte' => 4],
            ['reference' => 'FRE-0002', 'nom' => 'Disque de frein', 'categorie' => 'freinage', 'unite' => 'pièce', 'prix_achat' => 18000, 'prix_vente' => 26000, 'quantite_stock' => 10, 'seuil_alerte' => 2],
            ['reference' => 'CAR-0001', 'nom' => 'Pare-brise avant', 'categorie' => 'carrosserie', 'unite' => 'pièce', 'prix_achat' => 45000, 'prix_vente' => 65000, 'quantite_stock' => 3, 'seuil_alerte' => 1],
            ['reference' => 'CAR-0002', 'nom' => 'Rétroviseur latéral', 'categorie' => 'carrosserie', 'unite' => 'pièce', 'prix_achat' => 8000, 'prix_vente' => 12000, 'quantite_stock' => 12, 'seuil_alerte' => 3],
            ['reference' => 'PNE-0001', 'nom' => 'Pneu 195/70 R15', 'categorie' => 'pneumatique', 'unite' => 'pièce', 'prix_achat' => 35000, 'prix_vente' => 48000, 'quantite_stock' => 16, 'seuil_alerte' => 4],
            ['reference' => 'PNE-0002', 'nom' => 'Chambre à air', 'categorie' => 'pneumatique', 'unite' => 'pièce', 'prix_achat' => 5000, 'prix_vente' => 7500, 'quantite_stock' => 20, 'seuil_alerte' => 5],
            ['reference' => 'ELE-0001', 'nom' => 'Batterie 12V 70Ah', 'categorie' => 'electricite', 'unite' => 'pièce', 'prix_achat' => 40000, 'prix_vente' => 55000, 'quantite_stock' => 6, 'seuil_alerte' => 2],
            ['reference' => 'ELE-0002', 'nom' => 'Ampoule phare H4', 'categorie' => 'electricite', 'unite' => 'pièce', 'prix_achat' => 1500, 'prix_vente' => 2500, 'quantite_stock' => 30, 'seuil_alerte' => 8],
            ['reference' => 'LUB-0001', 'nom' => 'Huile moteur 15W40 (bidon 5L)', 'categorie' => 'lubrifiant', 'unite' => 'bidon', 'prix_achat' => 12000, 'prix_vente' => 17000, 'quantite_stock' => 18, 'seuil_alerte' => 5],
            ['reference' => 'LUB-0002', 'nom' => 'Liquide de frein', 'categorie' => 'lubrifiant', 'unite' => 'litre', 'prix_achat' => 3000, 'prix_vente' => 4500, 'quantite_stock' => 14, 'seuil_alerte' => 4],
            ['reference' => 'AUT-0001', 'nom' => 'Balai d\'essuie-glace', 'categorie' => 'autre', 'unite' => 'pièce', 'prix_achat' => 2500, 'prix_vente' => 4000, 'quantite_stock' => 22, 'seuil_alerte' => 6],
        ] as $article) {
            $categorie = CategorieArticle::where('code', $article['categorie'])->first();
            $unite = Unite::where('libelle', $article['unite'])->first();

            Article::firstOrCreate(
                ['reference' => $article['reference']],
                [
                    'nom' => $article['nom'],
                    'categorie_id' => $categorie?->id,
                    'unite_id' => $unite?->id,
                    'quantite_stock' => $article['quantite_stock'],
                    'prix_achat' => $article['prix_achat'],
                    'prix_vente' => $article['prix_vente'],
                    'seuil_alerte' => $article['seuil_alerte'],
                    'actif' => true,
                ]
            );
        }
    }
}
