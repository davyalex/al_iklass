<?php

namespace Database\Seeders;

use App\Models\Achat;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\User;
use App\Services\Stock\AchatService;
use Illuminate\Database\Seeder;

class AchatSeeder extends Seeder
{
    public function run(): void
    {
        $utilisateur = User::where('username', 'gestionnaire.demo')->first() ?? User::first();

        if (! $utilisateur) {
            $this->command?->warn('AchatSeeder ignoré : aucun utilisateur trouvé.');

            return;
        }

        foreach ([
            [
                'reference' => 'ACH-DEMO-0001',
                'fournisseur' => 'Ivoire Auto Pièces',
                'montant_paye_ratio' => 1, // comptant
                'lignes' => [
                    ['article' => 'MOT-0001', 'quantite' => 20, 'prix_unitaire' => 3500],
                    ['article' => 'MOT-0002', 'quantite' => 15, 'prix_unitaire' => 4000],
                ],
            ],
            [
                'reference' => 'ACH-DEMO-0002',
                'fournisseur' => 'Société Africaine de Pneumatiques',
                'montant_paye_ratio' => 0.5, // partiel
                'lignes' => [
                    ['article' => 'PNE-0001', 'quantite' => 12, 'prix_unitaire' => 35000],
                    ['article' => 'PNE-0002', 'quantite' => 10, 'prix_unitaire' => 5000],
                ],
            ],
            [
                'reference' => 'ACH-DEMO-0003',
                'fournisseur' => 'CFAO Motors Côte d\'Ivoire',
                'montant_paye_ratio' => 0, // crédit
                'lignes' => [
                    ['article' => 'ELE-0001', 'quantite' => 5, 'prix_unitaire' => 40000],
                    ['article' => 'FRE-0001', 'quantite' => 8, 'prix_unitaire' => 12000],
                ],
            ],
        ] as $achatDemo) {
            if (Achat::where('reference', $achatDemo['reference'])->exists()) {
                continue;
            }

            $fournisseur = Fournisseur::where('nom', $achatDemo['fournisseur'])->first();

            if (! $fournisseur) {
                continue;
            }

            $lignes = collect($achatDemo['lignes'])
                ->map(function (array $ligne) {
                    $article = Article::where('reference', $ligne['article'])->first();

                    return $article ? [
                        'article_id' => $article->id,
                        'quantite' => $ligne['quantite'],
                        'prix_unitaire' => $ligne['prix_unitaire'],
                    ] : null;
                })
                ->filter()
                ->values()
                ->all();

            if ($lignes === []) {
                continue;
            }

            $montantTotal = collect($lignes)->sum(fn (array $l) => $l['quantite'] * $l['prix_unitaire']);

            app(AchatService::class)->creer([
                'reference' => $achatDemo['reference'],
                'fournisseur_id' => $fournisseur->id,
                'montant_paye' => $montantTotal * $achatDemo['montant_paye_ratio'],
                'commentaire' => 'Achat de démonstration (seeder)',
                'user_id' => $utilisateur->id,
                'lignes' => $lignes,
            ]);
        }
    }
}
