<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Versement;

class VersementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('flotte.vehicule.voir') || $user->can('flotte.versement.gerer');
    }

    public function view(User $user, Versement $versement): bool
    {
        if ($user->can('flotte.vehicule.voir')) {
            return true;
        }

        return $user->can('flotte.versement.gerer') && $versement->gestionnaire_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('flotte.vehicule.gerer') || $user->can('flotte.versement.gerer');
    }
}
