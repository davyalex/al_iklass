<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Versement;

class VersementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('flotte.vehicule.voir');
    }

    public function view(User $user, Versement $versement): bool
    {
        return $user->can('flotte.vehicule.voir');
    }
}
