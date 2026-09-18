<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('utilisateurs.voir');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('utilisateurs.voir');
    }

    public function create(User $user): bool
    {
        return $user->can('utilisateurs.gerer');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('utilisateurs.gerer');
    }

    public function toggleActive(User $user, User $model): bool
    {
        return $user->can('utilisateurs.gerer') && $user->isNot($model);
    }

    public function resetPassword(User $user, User $model): bool
    {
        return $user->can('utilisateurs.gerer');
    }
}
