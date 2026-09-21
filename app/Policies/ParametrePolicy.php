<?php

namespace App\Policies;

use App\Models\Parametre;
use App\Models\User;

class ParametrePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('parametres.voir');
    }

    public function view(User $user, Parametre $parametre): bool
    {
        return $user->can('parametres.voir');
    }

    public function update(User $user, Parametre $parametre): bool
    {
        return $user->can('parametres.gerer');
    }
}
