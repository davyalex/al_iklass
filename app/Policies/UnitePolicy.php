<?php

namespace App\Policies;

use App\Models\Unite;
use App\Models\User;

class UnitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('unites.voir');
    }

    public function view(User $user, Unite $unite): bool
    {
        return $user->can('unites.voir');
    }

    public function create(User $user): bool
    {
        return $user->can('unites.gerer');
    }

    public function update(User $user, Unite $unite): bool
    {
        return $user->can('unites.gerer');
    }

    public function delete(User $user, Unite $unite): bool
    {
        return $user->can('unites.gerer');
    }
}
