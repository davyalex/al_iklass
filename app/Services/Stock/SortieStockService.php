<?php

namespace App\Services\Stock;

use App\Exceptions\Stock\StockInsuffisantException;
use App\Models\Article;
use App\Models\Caisse;
use App\Models\MouvementCaisse;
use App\Models\MouvementStock;
use App\Models\SortieLigne;
use App\Models\SortieStock;
use App\Models\Vehicule;
use Illuminate\Support\Facades\DB;

class SortieStockService
{
    /**
     * @param  array{
     *     vehicule_id: int,
     *     motif?: ?string,
     *     date_sortie?: ?string,
     *     reference?: ?string,
     *     user_id: int,
     *     lignes: list<array{article_id: int, quantite: int}>,
     * }  $data
     *
     * @throws StockInsuffisantException
     */
    public function creerInterne(array $data): SortieStock
    {
        return DB::transaction(function () use ($data) {
            $vehicule = Vehicule::findOrFail($data['vehicule_id']);

            $sortie = SortieStock::create([
                'reference' => ($data['reference'] ?? null) ?: $this->genererReference(),
                'nature' => 'interne',
                'date_sortie' => $data['date_sortie'] ?? now(),
                'motif' => $data['motif'] ?? null,
                'vehicule_id' => $vehicule->id,
                'vehicule_code' => $vehicule->code,
                'user_id' => $data['user_id'],
            ]);

            $montantTotal = 0;

            foreach ($data['lignes'] as $ligneData) {
                $montantTotal += $this->enregistrerLigne($sortie, $ligneData);
            }

            $sortie->update(['montant_total' => $montantTotal]);

            return $sortie->fresh('lignes');
        });
    }

    /**
     * @param  array{
     *     vehicule_externe: string,
     *     acheteur: string,
     *     motif?: ?string,
     *     date_sortie?: ?string,
     *     reference?: ?string,
     *     user_id: int,
     *     lignes: list<array{article_id: int, quantite: int, prix_vente: float}>,
     * }  $data
     *
     * @throws StockInsuffisantException
     */
    public function creerExterne(array $data): SortieStock
    {
        return DB::transaction(function () use ($data) {
            $sortie = SortieStock::create([
                'reference' => ($data['reference'] ?? null) ?: $this->genererReference(),
                'nature' => 'externe',
                'date_sortie' => $data['date_sortie'] ?? now(),
                'motif' => $data['motif'] ?? null,
                'vehicule_externe' => $data['vehicule_externe'],
                'acheteur' => $data['acheteur'],
                'user_id' => $data['user_id'],
            ]);

            $montantTotal = 0;

            foreach ($data['lignes'] as $ligneData) {
                $montantTotal += $this->enregistrerLigne($sortie, $ligneData);
            }

            $caisse = Caisse::where('type', 'ventes_externes')->firstOrFail();

            $mouvementCaisse = MouvementCaisse::create([
                'caisse_id' => $caisse->id,
                'sens' => 'entree',
                'montant' => $montantTotal,
                'motif' => "Vente externe — {$sortie->reference}",
                'origine_type' => SortieStock::class,
                'origine_id' => $sortie->id,
                'user_id' => $data['user_id'],
                'date_mouvement' => now(),
            ]);

            $sortie->update(['montant_total' => $montantTotal, 'caisse_mouvement_id' => $mouvementCaisse->id]);

            return $sortie->fresh('lignes');
        });
    }

    /**
     * @param  array{article_id: int, quantite: int, prix_vente?: float}  $ligneData
     *
     * @throws StockInsuffisantException
     */
    private function enregistrerLigne(SortieStock $sortie, array $ligneData): float
    {
        /** @var Article $article */
        $article = Article::lockForUpdate()->findOrFail($ligneData['article_id']);

        if ($ligneData['quantite'] > $article->quantite_stock) {
            throw new StockInsuffisantException($article, $ligneData['quantite']);
        }

        $prixVente = $sortie->nature === 'externe' ? (float) $ligneData['prix_vente'] : null;
        $montant = $ligneData['quantite'] * ($prixVente ?? (float) $article->prix_achat);

        $article->decrement('quantite_stock', $ligneData['quantite']);

        SortieLigne::create([
            'sortie_id' => $sortie->id,
            'article_id' => $article->id,
            'article_reference' => $article->reference,
            'article_nom' => $article->nom,
            'quantite' => $ligneData['quantite'],
            'prix_unitaire' => $article->prix_achat,
            'prix_vente' => $prixVente,
            'montant' => $montant,
        ]);

        MouvementStock::create([
            'article_id' => $article->id,
            'article_reference' => $article->reference,
            'article_nom' => $article->nom,
            'type' => 'sortie',
            'nature' => $sortie->nature,
            'quantite' => $ligneData['quantite'],
            'prix_unitaire' => $article->prix_achat,
            'prix_vente' => $prixVente,
            'motif' => $sortie->motif,
            'vehicule_id' => $sortie->vehicule_id,
            'vehicule_code' => $sortie->vehicule_code,
            'vehicule_externe' => $sortie->vehicule_externe,
            'acheteur' => $sortie->acheteur,
            'sortie_id' => $sortie->id,
            'user_id' => $sortie->user_id,
            'date_mouvement' => now(),
        ]);

        return $montant;
    }

    private function genererReference(): string
    {
        $annee = now()->year;
        $sequence = SortieStock::withTrashed()->whereYear('date_sortie', $annee)->count() + 1;

        return sprintf('SOR-%d-%04d', $annee, $sequence);
    }
}
