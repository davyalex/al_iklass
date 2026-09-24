<?php

namespace App\Services\Stock;

use App\Models\Article;
use App\Models\Inventaire;
use App\Models\InventaireLigne;
use App\Models\MouvementStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventaireService
{
    /**
     * Crée un inventaire en brouillon et fige la quantité théorique de chaque article
     * concerné (tous les articles actifs, ou ceux de la catégorie choisie) : c'est cette
     * quantité, et non le stock courant au moment de la validation, qui sert de référence
     * pour calculer l'écart de chaque ligne.
     *
     * @param  array{
     *     categorie_id?: ?int,
     *     reference?: ?string,
     *     date_inventaire?: ?string,
     *     commentaire?: ?string,
     *     user_id: int,
     * }  $data
     */
    public function creer(array $data): Inventaire
    {
        return DB::transaction(function () use ($data) {
            $inventaire = Inventaire::create([
                'reference' => ($data['reference'] ?? null) ?: $this->genererReference(),
                'categorie_id' => $data['categorie_id'] ?? null,
                'date_inventaire' => $data['date_inventaire'] ?? now(),
                'statut' => 'brouillon',
                'commentaire' => $data['commentaire'] ?? null,
                'user_id' => $data['user_id'],
            ]);

            Article::query()
                ->actif()
                ->when($inventaire->categorie_id, fn ($q) => $q->where('categorie_id', $inventaire->categorie_id))
                ->orderBy('nom')
                ->each(function (Article $article) use ($inventaire) {
                    InventaireLigne::create([
                        'inventaire_id' => $inventaire->id,
                        'article_id' => $article->id,
                        'article_reference' => $article->reference,
                        'article_nom' => $article->nom,
                        'quantite_theorique' => $article->quantite_stock,
                        'prix_achat_unitaire' => $article->prix_achat,
                    ]);
                });

            return $inventaire->fresh('lignes');
        });
    }

    /**
     * Sauvegarde le comptage d'une ligne (appelé à chaque saisie, ligne par ligne, pour ne
     * rien perdre pendant un comptage potentiellement long).
     *
     * @throws ValidationException
     */
    public function enregistrerComptage(InventaireLigne $ligne, ?int $quantiteComptee, ?string $commentaire): InventaireLigne
    {
        $this->garantirBrouillon($ligne->inventaire);

        $ligne->update([
            'quantite_comptee' => $quantiteComptee,
            'commentaire' => $commentaire,
        ]);

        return $ligne;
    }

    /**
     * Applique les écarts au stock : pour chaque ligne comptée avec un écart non nul, crée
     * un mouvement d'ajustement (entree si excédent, sortie si manquant) et met à jour
     * quantite_stock. Les lignes non comptées sont ignorées (ex: catégorie non couverte).
     *
     * @throws ValidationException
     */
    public function valider(Inventaire $inventaire, int $valideParId): Inventaire
    {
        return DB::transaction(function () use ($inventaire, $valideParId) {
            /** @var Inventaire $inventaire */
            $inventaire = Inventaire::lockForUpdate()->findOrFail($inventaire->id);

            $this->garantirBrouillon($inventaire);

            $lignes = $inventaire->lignes()->whereNotNull('quantite_comptee')->get();

            if ($lignes->isEmpty()) {
                throw ValidationException::withMessages([
                    'statut' => "Aucune ligne n'a été comptée, l'inventaire ne peut pas être validé.",
                ]);
            }

            $nombreEcarts = 0;

            foreach ($lignes as $ligne) {
                $ecart = $ligne->quantite_comptee - $ligne->quantite_theorique;

                if ($ecart === 0) {
                    continue;
                }

                $nombreEcarts++;

                /** @var Article $article */
                $article = Article::lockForUpdate()->findOrFail($ligne->article_id);

                $article->increment('quantite_stock', $ecart);

                MouvementStock::create([
                    'article_id' => $article->id,
                    'article_reference' => $article->reference,
                    'article_nom' => $article->nom,
                    'type' => $ecart > 0 ? 'entree' : 'sortie',
                    'nature' => 'ajustement',
                    'quantite' => abs($ecart),
                    'prix_unitaire' => $ligne->prix_achat_unitaire,
                    'motif' => "Ajustement d'inventaire {$inventaire->reference}",
                    'inventaire_id' => $inventaire->id,
                    'user_id' => $valideParId,
                    'date_mouvement' => now(),
                ]);
            }

            $inventaire->update([
                'statut' => 'valide',
                'valide_par_id' => $valideParId,
                'valide_le' => now(),
            ]);

            activity()
                ->performedOn($inventaire)
                ->withProperties(['lignes_comptees' => $lignes->count(), 'ecarts_appliques' => $nombreEcarts])
                ->log("Inventaire {$inventaire->reference} validé ({$nombreEcarts} écart(s) appliqué(s) au stock).");

            return $inventaire->fresh('lignes');
        });
    }

    /**
     * Archivage (soft delete) : un inventaire déjà validé a impacté le stock et son
     * historique doit rester consultable, donc jamais supprimable.
     *
     * @throws ValidationException
     */
    public function supprimer(Inventaire $inventaire): void
    {
        $this->garantirBrouillon($inventaire);

        $inventaire->delete();
    }

    /**
     * @throws ValidationException
     */
    private function garantirBrouillon(Inventaire $inventaire): void
    {
        if ($inventaire->statut !== 'brouillon') {
            throw ValidationException::withMessages([
                'statut' => 'Cet inventaire est déjà validé, il ne peut plus être modifié.',
            ]);
        }
    }

    private function genererReference(): string
    {
        $annee = now()->year;
        $sequence = Inventaire::withTrashed()->whereYear('date_inventaire', $annee)->count() + 1;

        return sprintf('INV-%d-%04d', $annee, $sequence);
    }
}
