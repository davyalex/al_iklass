<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.voir');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.voir');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.gerer');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.gerer');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.gerer');
    }
}
