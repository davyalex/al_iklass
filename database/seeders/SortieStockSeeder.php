<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\MouvementStock;
use App\Models\User;
use App\Models\Vehicule;
use App\Services\Stock\SortieStockService;
use Illuminate\Database\Seeder;

class SortieStockSeeder extends Seeder
{
    private const MARQUEUR = '[DEMO]';

    public function run(): void
    {
        if (MouvementStock::sorties()->where('motif', 'like', self::MARQUEUR.'%')->exists()) {
            return;
        }

        $utilisateur = User::where('username', 'gestionnaire.demo')->first() ?? User::first();
        $vehicules = Vehicule::limit(2)->get();

        if (! $utilisateur || $vehicules->count() < 2) {
            $this->command?->warn('SortieStockSeeder ignoré : utilisateur ou véhicules manquants.');

            return;
        }

        $service = app(SortieStockService::class);

        foreach ([
            ['article' => 'MOT-0001', 'quantite' => 2, 'vehicule' => $vehicules[0]],
            ['article' => 'FRE-0001', 'quantite' => 1, 'vehicule' => $vehicules[1]],
        ] as $sortie) {
            $article = Article::where('reference', $sortie['article'])->first();

            if (! $article) {
                continue;
            }

            $service->sortieInterne([
                'article_id' => $article->id,
                'quantite' => $sortie['quantite'],
                'vehicule_id' => $sortie['vehicule']->id,
                'motif' => self::MARQUEUR.' Entretien courant',
                'user_id' => $utilisateur->id,
            ]);
        }

        foreach ([
            ['article' => 'PNE-0002', 'quantite' => 4, 'prix_vente' => 7500, 'acheteur' => 'Koffi Transport'],
            ['article' => 'ELE-0002', 'quantite' => 6, 'prix_vente' => 2500, 'acheteur' => 'Garage Aya'],
        ] as $vente) {
            $article = Article::where('reference', $vente['article'])->first();

            if (! $article) {
                continue;
            }

            $service->sortieExterne([
                'article_id' => $article->id,
                'quantite' => $vente['quantite'],
                'prix_vente' => $vente['prix_vente'],
                'vehicule_externe' => 'Client de passage',
                'acheteur' => $vente['acheteur'],
                'motif' => self::MARQUEUR.' Vente comptoir',
                'user_id' => $utilisateur->id,
            ]);
        }
    }
}
