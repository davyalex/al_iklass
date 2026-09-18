<?php

namespace App\Policies;

use App\Models\Fournisseur;
use App\Models\User;

class FournisseurPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.tableau_bord.voir');
    }

    public function view(User $user, Fournisseur $fournisseur): bool
    {
        return $user->can('stock.tableau_bord.voir');
    }

    public function create(User $user): bool
    {
        return $user->can('stock.fournisseur.gerer');
    }

    public function update(User $user, Fournisseur $fournisseur): bool
    {
        return $user->can('stock.fournisseur.gerer');
    }

    public function delete(User $user, Fournisseur $fournisseur): bool
    {
        return $user->can('stock.fournisseur.gerer');
    }
}
