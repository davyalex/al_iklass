<?php

namespace App\Policies;

use App\Models\DemandeSortie;
use App\Models\User;

class DemandeSortiePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.demande.creer') || $user->can('stock.demande.traiter');
    }

    public function view(User $user, DemandeSortie $demande): bool
    {
        if ($user->can('stock.demande.traiter')) {
            return true;
        }

        return $user->can('stock.demande.creer') && $demande->demandeur_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('stock.demande.creer');
    }

    public function traiter(User $user): bool
    {
        return $user->can('stock.demande.traiter');
    }
}
