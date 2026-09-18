<?php

namespace App\Policies;

use App\Models\Inventaire;
use App\Models\User;

class InventairePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.tableau_bord.voir');
    }

    public function view(User $user, Inventaire $inventaire): bool
    {
        return $user->can('stock.tableau_bord.voir');
    }

    public function create(User $user): bool
    {
        return $user->can('stock.inventaire.gerer');
    }

    public function update(User $user, Inventaire $inventaire): bool
    {
        return $user->can('stock.inventaire.gerer');
    }

    public function delete(User $user, Inventaire $inventaire): bool
    {
        return $user->can('stock.inventaire.gerer');
    }
}
