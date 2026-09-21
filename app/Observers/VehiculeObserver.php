<?php

namespace App\Observers;

use App\Models\HistoriqueStatutVehicule;
use App\Models\StatutVehicule;
use App\Models\Vehicule;

class VehiculeObserver
{
    /**
     * Synchronise le drapeau "actif" du véhicule avec son statut : un
     * véhicule n'est considéré actif (utilisable pour une sortie de stock,
     * etc.) que si son statut porte le code "en_circulation".
     */
    public function saving(Vehicule $vehicule): void
    {
        if ($vehicule->statut_id === null) {
            return;
        }

        $vehicule->actif = StatutVehicule::whereKey($vehicule->statut_id)->value('code') === 'en_circulation';
    }

    /**
     * Journalise le statut initial à la création du véhicule.
     */
    public function created(Vehicule $vehicule): void
    {
        $this->enregistrerHistorique($vehicule, null, $vehicule->statut_id);
    }

    /**
     * Journalise chaque changement de statut (règle d'or : historique par
     * copie figée, cf. CONTEXTE.md §6).
     */
    public function updated(Vehicule $vehicule): void
    {
        if ($vehicule->wasChanged('statut_id')) {
            $this->enregistrerHistorique($vehicule, $vehicule->getOriginal('statut_id'), $vehicule->statut_id);
        }
    }

    private function enregistrerHistorique(Vehicule $vehicule, ?int $ancienStatutId, ?int $nouveauStatutId): void
    {
        if ($nouveauStatutId === null) {
            return;
        }

        $ancien = $ancienStatutId ? StatutVehicule::find($ancienStatutId) : null;
        $nouveau = StatutVehicule::find($nouveauStatutId);

        HistoriqueStatutVehicule::create([
            'vehicule_id' => $vehicule->id,
            'vehicule_code' => $vehicule->code,
            'ancien_statut_id' => $ancien?->id,
            'ancien_statut_code' => $ancien?->code,
            'ancien_statut_libelle' => $ancien?->libelle,
            'nouveau_statut_id' => $nouveau?->id,
            'nouveau_statut_code' => $nouveau?->code,
            'nouveau_statut_libelle' => $nouveau?->libelle,
            'user_id' => auth()->id(),
            // Renseigné ponctuellement par certains flux (ex. remise en
            // circulation par le chef mécanicien avec rapport obligatoire) via
            // un attribut non persisté posé sur le modèle avant l'update.
            'commentaire' => $vehicule->commentaireHistorique ?? null,
        ]);
    }
}
