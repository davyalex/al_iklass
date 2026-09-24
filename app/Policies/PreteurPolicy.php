<?php

namespace App\Policies;

use App\Models\Preteur;
use App\Models\User;

class PreteurPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('financements.voir');
    }

    public function view(User $user, Preteur $preteur): bool
    {
        return $user->can('financements.voir');
    }

    public function create(User $user): bool
    {
        return $user->can('financements.preteur.gerer');
    }

    public function update(User $user, Preteur $preteur): bool
    {
        return $user->can('financements.preteur.gerer');
    }

    public function delete(User $user, Preteur $preteur): bool
    {
        return $user->can('financements.preteur.gerer');
    }
}
