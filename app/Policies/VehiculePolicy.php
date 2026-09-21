<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicule;
use App\Support\FenetreStatutJournalier;

class VehiculePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('flotte.vehicule.voir')
            || $user->can('flotte.vehicule.voir_affectes')
            || $user->can('flotte.vehicule.remise_circulation');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vehicule $vehicule): bool
    {
        if ($user->can('flotte.vehicule.voir') || $user->can('flotte.vehicule.remise_circulation')) {
            return true;
        }

        return $user->can('flotte.vehicule.voir_affectes') && $vehicule->gestionnaire_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('flotte.vehicule.gerer');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vehicule $vehicule): bool
    {
        return $user->can('flotte.vehicule.gerer');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vehicule $vehicule): bool
    {
        return $user->can('flotte.vehicule.gerer');
    }

    /**
     * Changement rapide de statut : l'admin peut à tout moment, un
     * gestionnaire uniquement sur ses propres véhicules et pendant la
     * fenêtre horaire configurée (CONTEXTE.md §5).
     */
    public function changerStatut(User $user, Vehicule $vehicule): bool
    {
        if ($user->can('flotte.vehicule.gerer')) {
            return true;
        }

        if (! $user->can('flotte.vehicule.statut.gerer')) {
            return false;
        }

        return $vehicule->gestionnaire_id === $user->id && FenetreStatutJournalier::estOuverte();
    }

    /**
     * Remise en circulation par le chef mécanicien, avec rapport obligatoire
     * (possible à tout moment, y compris hors fenêtre gestionnaire).
     */
    public function remiseEnCirculation(User $user, Vehicule $vehicule): bool
    {
        return $user->can('flotte.vehicule.gerer') || $user->can('flotte.vehicule.remise_circulation');
    }
}
