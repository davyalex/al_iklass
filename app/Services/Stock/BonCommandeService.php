<?php

namespace App\Services\Stock;

use App\Models\Article;
use App\Models\BonCommande;
use App\Models\BonCommandeLigne;
use App\Models\Fournisseur;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BonCommandeService
{
    /**
     * @param  array{
     *     fournisseur_id: int,
     *     reference?: ?string,
     *     date_commande?: ?string,
     *     commentaire?: ?string,
     *     user_id: int,
     *     lignes: list<array{article_id: int, quantite_commandee: int, prix_unitaire_estime: float}>,
     * }  $data
     */
    public function creer(array $data): BonCommande
    {
        return DB::transaction(function () use ($data) {
            $fournisseur = Fournisseur::findOrFail($data['fournisseur_id']);

            $bonCommande = BonCommande::create([
                'reference' => ($data['reference'] ?? null) ?: $this->genererReference(),
                'fournisseur_id' => $fournisseur->id,
                'fournisseur_nom' => $fournisseur->nom,
                'date_commande' => $data['date_commande'] ?? now(),
                'statut' => 'en_attente',
                'commentaire' => $data['commentaire'] ?? null,
                'user_id' => $data['user_id'],
            ]);

            foreach ($data['lignes'] as $ligneData) {
                /** @var Article $article */
                $article = Article::findOrFail($ligneData['article_id']);

                BonCommandeLigne::create([
                    'bon_commande_id' => $bonCommande->id,
                    'article_id' => $article->id,
                    'article_reference' => $article->reference,
                    'article_nom' => $article->nom,
                    'quantite_commandee' => $ligneData['quantite_commandee'],
                    'quantite_recue' => 0,
                    'prix_unitaire_estime' => $ligneData['prix_unitaire_estime'],
                    'montant_estime' => $ligneData['quantite_commandee'] * $ligneData['prix_unitaire_estime'],
                ]);
            }

            return $bonCommande->fresh('lignes');
        });
    }

    /**
     * @throws ValidationException
     */
    public function annuler(BonCommande $bonCommande): BonCommande
    {
        $bonCommande->loadMissing('lignes');

        if ($bonCommande->lignes->contains(fn ($l) => $l->quantite_recue > 0)) {
            throw ValidationException::withMessages([
                'statut' => 'Ce bon de commande a déjà été partiellement reçu, il ne peut plus être annulé.',
            ]);
        }

        $bonCommande->update(['statut' => 'annule']);

        return $bonCommande;
    }

    /**
     * Archivage (soft delete) : refusé si une ligne a déjà été réceptionnée, même partiellement —
     * l'historique de réception doit rester consultable (règle d'or : jamais de suppression
     * physique d'un enregistrement porteur d'historique).
     *
     * @throws ValidationException
     */
    public function supprimer(BonCommande $bonCommande): void
    {
        $bonCommande->loadMissing('lignes');

        if ($bonCommande->lignes->contains(fn ($l) => $l->quantite_recue > 0)) {
            throw ValidationException::withMessages([
                'statut' => 'Ce bon de commande a des lignes déjà réceptionnées, il ne peut pas être supprimé.',
            ]);
        }

        $bonCommande->delete();
    }

    private function genererReference(): string
    {
        $annee = now()->year;
        $sequence = BonCommande::withTrashed()->whereYear('date_commande', $annee)->count() + 1;

        return sprintf('BC-%d-%04d', $annee, $sequence);
    }
}
