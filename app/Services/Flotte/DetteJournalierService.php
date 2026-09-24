<?php

namespace App\Services\Flotte;

use App\Models\Caisse;
use App\Models\HistoriqueDette;
use App\Models\MouvementCaisse;
use App\Models\Parametre;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Versement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DetteJournalierService
{
    private const CLE_DERNIERE_EXECUTION = 'flotte.dette_journaliere.derniere_execution';

    /**
     * Bascule en dette, pour chaque gestionnaire, le reste à verser de la
     * veille (recette attendue des véhicules en circulation hier − versements
     * effectués hier). Idempotent par jour, verrouillé contre une double
     * bascule en cas d'appels concurrents (la dette est cumulative, à la
     * différence du reset de statut journalier qui est sans risque à rejouer).
     *
     * @return int Nombre de gestionnaires basculés en dette.
     */
    public function basculerSiNecessaire(): int
    {
        return DB::transaction(function () {
            $parametre = Parametre::where('cle', self::CLE_DERNIERE_EXECUTION)->lockForUpdate()->first();

            if (! $parametre) {
                $parametre = Parametre::create([
                    'cle' => self::CLE_DERNIERE_EXECUTION,
                    'valeur' => '',
                    'libelle' => 'Dernière bascule de dette journalière',
                    'groupe' => 'interne',
                    'ordre' => 0,
                ]);
            }

            $aujourdhui = now()->format('Y-m-d');

            if ($parametre->valeur === $aujourdhui) {
                return 0;
            }

            $hier = now()->subDay();
            $compteur = 0;

            // lockForUpdate() : verrouille chaque gestionnaire pour la durée de la
            // transaction, évite un lost update si un règlement/une annulation
            // concurrent(e) touche la même ligne "dette" pendant la bascule.
            foreach (User::role('gestionnaire')->lockForUpdate()->get() as $gestionnaire) {
                $attendu = (float) Vehicule::where('gestionnaire_id', $gestionnaire->id)
                    ->whereHas('statut', fn ($q) => $q->where('code', 'en_circulation'))
                    ->sum('recette_journaliere');

                $dejaVerseHier = (float) Versement::whereDate('date_versement', $hier)
                    ->where('gestionnaire_id', $gestionnaire->id)
                    ->sum('montant');

                $resteAVerser = max(0, $attendu - $dejaVerseHier);

                if ($resteAVerser <= 0) {
                    continue;
                }

                $detteAvant = (float) $gestionnaire->dette;
                $gestionnaire->dette = $detteAvant + $resteAVerser;
                $gestionnaire->save();

                HistoriqueDette::create([
                    'gestionnaire_id' => $gestionnaire->id,
                    'gestionnaire_nom' => $gestionnaire->name,
                    'type' => 'bascule',
                    'montant' => $resteAVerser,
                    'dette_avant' => $detteAvant,
                    'dette_apres' => $gestionnaire->dette,
                    'date_reference' => $hier->toDateString(),
                    'attendu' => $attendu,
                    'deja_verse' => $dejaVerseHier,
                    'user_id' => null,
                ]);

                $compteur++;
            }

            $parametre->update(['valeur' => $aujourdhui]);

            return $compteur;
        });
    }

    /**
     * Annulation (partielle ou totale) par un admin. N'écrit volontairement
     * aucun MouvementCaisse : une annulation de dette n'est pas un mouvement
     * d'argent réel, seul HistoriqueDette trace l'opération.
     *
     * @throws ValidationException
     */
    public function annuler(User $gestionnaire, float $montant, string $motif, User $admin): HistoriqueDette
    {
        if ($montant <= 0) {
            throw ValidationException::withMessages(['montant' => 'Le montant doit être supérieur à 0.']);
        }

        return DB::transaction(function () use ($gestionnaire, $montant, $motif, $admin) {
            /** @var User $gestionnaire */
            $gestionnaire = User::lockForUpdate()->findOrFail($gestionnaire->id);
            $detteAvant = (float) $gestionnaire->dette;

            if ($montant > $detteAvant) {
                throw ValidationException::withMessages(['montant' => 'Le montant ne peut pas dépasser la dette actuelle.']);
            }

            $gestionnaire->dette = $detteAvant - $montant;
            $gestionnaire->save();

            $historique = HistoriqueDette::create([
                'gestionnaire_id' => $gestionnaire->id,
                'gestionnaire_nom' => $gestionnaire->name,
                'type' => 'annulation',
                'montant' => $montant,
                'dette_avant' => $detteAvant,
                'dette_apres' => $gestionnaire->dette,
                'motif' => $motif,
                'user_id' => $admin->id,
            ]);

            activity()
                ->performedOn($gestionnaire)
                ->causedBy($admin)
                ->withProperties(['montant' => $montant, 'motif' => $motif, 'dette_avant' => $detteAvant, 'dette_apres' => $gestionnaire->dette])
                ->log("Dette de « {$gestionnaire->name} » annulée pour {$montant} FCFA — motif : {$motif}");

            return $historique;
        });
    }

    /**
     * Règlement (partiel ou total) de sa dette par le gestionnaire lui-même
     * (ou saisi par un admin en son nom, ex. paiement en espèces reçu en
     * personne). Contrairement à l'annulation, c'est un mouvement d'argent
     * réel : écrit un MouvementCaisse (entrée) sur la caisse "versements",
     * lié à cette ligne d'historique via origine_type/origine_id — pas un
     * Versement classique, pour ne pas fausser le calcul du reste à verser
     * du jour (qui ne regarde que les Versement datés d'aujourd'hui).
     *
     * @throws ValidationException
     */
    public function regler(User $gestionnaire, float $montant, ?string $motif, User $auteur): HistoriqueDette
    {
        if ($montant <= 0) {
            throw ValidationException::withMessages(['montant' => 'Le montant doit être supérieur à 0.']);
        }

        return DB::transaction(function () use ($gestionnaire, $montant, $motif, $auteur) {
            /** @var User $gestionnaire */
            $gestionnaire = User::lockForUpdate()->findOrFail($gestionnaire->id);
            $detteAvant = (float) $gestionnaire->dette;

            if ($montant > $detteAvant) {
                throw ValidationException::withMessages(['montant' => 'Le montant ne peut pas dépasser la dette actuelle.']);
            }

            $gestionnaire->dette = $detteAvant - $montant;
            $gestionnaire->save();

            $historique = HistoriqueDette::create([
                'gestionnaire_id' => $gestionnaire->id,
                'gestionnaire_nom' => $gestionnaire->name,
                'type' => 'reglement',
                'montant' => $montant,
                'dette_avant' => $detteAvant,
                'dette_apres' => $gestionnaire->dette,
                'motif' => $motif,
                'user_id' => $auteur->id,
            ]);

            $caisse = Caisse::where('type', 'versements')->firstOrFail();

            MouvementCaisse::create([
                'caisse_id' => $caisse->id,
                'sens' => 'entree',
                'montant' => $montant,
                'motif' => $motif ?: "Règlement de dette — {$gestionnaire->name}",
                'origine_type' => HistoriqueDette::class,
                'origine_id' => $historique->id,
                'user_id' => $auteur->id,
                'date_mouvement' => now(),
            ]);

            activity()
                ->performedOn($gestionnaire)
                ->causedBy($auteur)
                ->withProperties(['montant' => $montant, 'dette_avant' => $detteAvant, 'dette_apres' => $gestionnaire->dette])
                ->log("Dette de « {$gestionnaire->name} » réglée pour {$montant} FCFA.");

            return $historique;
        });
    }
}
