<?php

namespace App\Services\Stock;

use App\Models\Achat;
use App\Models\AchatLigne;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\MouvementStock;
use Illuminate\Support\Facades\DB;

class AchatService
{
    /**
     * @param  array{
     *     fournisseur_id: int,
     *     reference?: ?string,
     *     date_achat?: ?string,
     *     montant_paye?: float,
     *     commentaire?: ?string,
     *     user_id: int,
     *     lignes: list<array{article_id: int, quantite: int, prix_unitaire: float}>,
     * }  $data
     */
    public function creer(array $data): Achat
    {
        return DB::transaction(function () use ($data) {
            $fournisseur = Fournisseur::findOrFail($data['fournisseur_id']);

            $montantTotal = collect($data['lignes'])
                ->sum(fn (array $ligne) => $ligne['quantite'] * $ligne['prix_unitaire']);

            $montantPaye = min((float) ($data['montant_paye'] ?? 0), $montantTotal);
            $montantRestant = $montantTotal - $montantPaye;

            $achat = Achat::create([
                'reference' => $data['reference'] ?? null,
                'fournisseur_id' => $fournisseur->id,
                'fournisseur_nom' => $fournisseur->nom,
                'date_achat' => $data['date_achat'] ?? now(),
                'montant_total' => $montantTotal,
                'montant_paye' => $montantPaye,
                'montant_restant' => $montantRestant,
                'statut_paiement' => Achat::deriveStatutPaiement($montantPaye, $montantRestant),
                'commentaire' => $data['commentaire'] ?? null,
                'user_id' => $data['user_id'],
            ]);

            foreach ($data['lignes'] as $ligneData) {
                $this->enregistrerLigne($achat, $ligneData);
            }

            return $achat->fresh('lignes');
        });
    }

    /**
     * @param  array{article_id: int, quantite: int, prix_unitaire: float}  $ligneData
     */
    private function enregistrerLigne(Achat $achat, array $ligneData): AchatLigne
    {
        /** @var Article $article */
        $article = Article::lockForUpdate()->findOrFail($ligneData['article_id']);

        $montant = $ligneData['quantite'] * $ligneData['prix_unitaire'];

        $ligne = AchatLigne::create([
            'achat_id' => $achat->id,
            'article_id' => $article->id,
            'article_reference' => $article->reference,
            'article_nom' => $article->nom,
            'quantite' => $ligneData['quantite'],
            'prix_unitaire' => $ligneData['prix_unitaire'],
            'montant' => $montant,
        ]);

        $article->increment('quantite_stock', $ligneData['quantite']);
        $article->update(['prix_achat' => $ligneData['prix_unitaire']]);

        MouvementStock::create([
            'article_id' => $article->id,
            'article_reference' => $article->reference,
            'article_nom' => $article->nom,
            'type' => 'entree',
            'quantite' => $ligneData['quantite'],
            'prix_unitaire' => $ligneData['prix_unitaire'],
            'motif' => 'Achat',
            'achat_id' => $achat->id,
            'user_id' => $achat->user_id,
            'date_mouvement' => now(),
        ]);

        return $ligne;
    }
}
