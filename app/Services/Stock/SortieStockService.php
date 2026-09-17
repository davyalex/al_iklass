<?php

namespace App\Services\Stock;

use App\Exceptions\Stock\StockInsuffisantException;
use App\Models\Article;
use App\Models\Caisse;
use App\Models\MouvementCaisse;
use App\Models\MouvementStock;
use App\Models\Vehicule;
use Illuminate\Support\Facades\DB;

class SortieStockService
{
    /**
     * @param  array{article_id: int, quantite: int, vehicule_id: int, motif: string, user_id: int}  $data
     *
     * @throws StockInsuffisantException
     */
    public function sortieInterne(array $data): MouvementStock
    {
        return DB::transaction(function () use ($data) {
            $article = $this->verrouillerEtVerifierStock($data['article_id'], $data['quantite']);
            $vehicule = Vehicule::findOrFail($data['vehicule_id']);

            $article->decrement('quantite_stock', $data['quantite']);

            return MouvementStock::create([
                'article_id' => $article->id,
                'article_reference' => $article->reference,
                'article_nom' => $article->nom,
                'type' => 'sortie',
                'nature' => 'interne',
                'quantite' => $data['quantite'],
                'prix_unitaire' => $article->prix_achat,
                'motif' => $data['motif'],
                'vehicule_id' => $vehicule->id,
                'vehicule_code' => $vehicule->code,
                'user_id' => $data['user_id'],
                'date_mouvement' => now(),
            ]);
        });
    }

    /**
     * @param  array{
     *     article_id: int,
     *     quantite: int,
     *     prix_vente: float,
     *     vehicule_externe: string,
     *     acheteur: string,
     *     motif: string,
     *     user_id: int,
     * }  $data
     *
     * @throws StockInsuffisantException
     */
    public function sortieExterne(array $data): MouvementStock
    {
        return DB::transaction(function () use ($data) {
            $article = $this->verrouillerEtVerifierStock($data['article_id'], $data['quantite']);

            $article->decrement('quantite_stock', $data['quantite']);

            $mouvement = MouvementStock::create([
                'article_id' => $article->id,
                'article_reference' => $article->reference,
                'article_nom' => $article->nom,
                'type' => 'sortie',
                'nature' => 'externe',
                'quantite' => $data['quantite'],
                'prix_unitaire' => $article->prix_achat,
                'prix_vente' => $data['prix_vente'],
                'motif' => $data['motif'],
                'vehicule_externe' => $data['vehicule_externe'],
                'acheteur' => $data['acheteur'],
                'user_id' => $data['user_id'],
                'date_mouvement' => now(),
            ]);

            $caisse = Caisse::where('type', 'ventes_externes')->firstOrFail();

            $mouvementCaisse = MouvementCaisse::create([
                'caisse_id' => $caisse->id,
                'sens' => 'entree',
                'montant' => $data['quantite'] * $data['prix_vente'],
                'motif' => "Vente externe — {$article->nom}",
                'origine_type' => MouvementStock::class,
                'origine_id' => $mouvement->id,
                'user_id' => $data['user_id'],
                'date_mouvement' => now(),
            ]);

            $mouvement->update(['caisse_mouvement_id' => $mouvementCaisse->id]);

            return $mouvement;
        });
    }

    /**
     * @throws StockInsuffisantException
     */
    private function verrouillerEtVerifierStock(int $articleId, int $quantite): Article
    {
        /** @var Article $article */
        $article = Article::lockForUpdate()->findOrFail($articleId);

        if ($quantite > $article->quantite_stock) {
            throw new StockInsuffisantException($article, $quantite);
        }

        return $article;
    }
}
