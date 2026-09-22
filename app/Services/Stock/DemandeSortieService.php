<?php

namespace App\Services\Stock;

use App\Exceptions\Stock\StockInsuffisantException;
use App\Models\Article;
use App\Models\DemandeSortie;
use App\Models\DemandeSortieLigne;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DemandeSortieService
{
    public function __construct(private readonly SortieStockService $sortieService) {}

    /**
     * @param  array{
     *     vehicule_id: int,
     *     motif?: ?string,
     *     date_demande?: ?string,
     *     reference?: ?string,
     *     demandeur_id: int,
     *     lignes: list<array{article_id: int, quantite: int}>,
     * }  $data
     */
    public function creer(array $data): DemandeSortie
    {
        return DB::transaction(function () use ($data) {
            $vehicule = Vehicule::findOrFail($data['vehicule_id']);
            $demandeur = User::findOrFail($data['demandeur_id']);

            $demande = DemandeSortie::create([
                'reference' => ($data['reference'] ?? null) ?: $this->genererReference(),
                'vehicule_id' => $vehicule->id,
                'vehicule_code' => $vehicule->code,
                'motif' => $data['motif'] ?? null,
                'date_demande' => $data['date_demande'] ?? now(),
                'statut' => 'en_attente',
                'demandeur_id' => $demandeur->id,
                'demandeur_nom' => $demandeur->name,
            ]);

            foreach ($data['lignes'] as $ligneData) {
                /** @var Article $article */
                $article = Article::findOrFail($ligneData['article_id']);

                DemandeSortieLigne::create([
                    'demande_sortie_id' => $demande->id,
                    'article_id' => $article->id,
                    'article_reference' => $article->reference,
                    'article_nom' => $article->nom,
                    'quantite' => $ligneData['quantite'],
                ]);
            }

            return $demande->fresh('lignes');
        });
    }

    /**
     * Transforme la demande en sortie de stock réelle (décrémente le stock,
     * écrit les mouvements) via SortieStockService, comme le fait déjà le
     * gestionnaire de stock pour une sortie interne classique.
     *
     * @throws StockInsuffisantException
     * @throws ValidationException
     */
    public function valider(DemandeSortie $demande, User $gestionnaire): DemandeSortie
    {
        if ($demande->statut !== 'en_attente') {
            throw ValidationException::withMessages([
                'statut' => 'Cette demande a déjà été traitée.',
            ]);
        }

        return DB::transaction(function () use ($demande, $gestionnaire) {
            $demande->loadMissing('lignes');

            $sortie = $this->sortieService->creerInterne([
                'vehicule_id' => $demande->vehicule_id,
                'motif' => $demande->motif ?? "Demande {$demande->reference}",
                'reference' => null,
                'user_id' => $gestionnaire->id,
                'lignes' => $demande->lignes->map(fn (DemandeSortieLigne $l) => [
                    'article_id' => $l->article_id,
                    'quantite' => $l->quantite,
                ])->all(),
            ]);

            $demande->update([
                'statut' => 'validee',
                'traite_par_id' => $gestionnaire->id,
                'traite_par_nom' => $gestionnaire->name,
                'sortie_id' => $sortie->id,
            ]);

            return $demande->fresh(['lignes', 'sortie']);
        });
    }

    /**
     * @throws ValidationException
     */
    public function rejeter(DemandeSortie $demande, User $gestionnaire, string $motif): DemandeSortie
    {
        if ($demande->statut !== 'en_attente') {
            throw ValidationException::withMessages([
                'statut' => 'Cette demande a déjà été traitée.',
            ]);
        }

        $demande->update([
            'statut' => 'rejetee',
            'traite_par_id' => $gestionnaire->id,
            'traite_par_nom' => $gestionnaire->name,
            'commentaire_traitement' => $motif,
        ]);

        return $demande;
    }

    private function genererReference(): string
    {
        $annee = now()->year;
        $sequence = DemandeSortie::withTrashed()->whereYear('date_demande', $annee)->count() + 1;

        return sprintf('DEM-%d-%04d', $annee, $sequence);
    }
}
