<?php

namespace App\Policies;

use App\Models\Fournisseur;
use App\Models\User;

class FournisseurPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.dashboard.view');
    }

    public function view(User $user, Fournisseur $fournisseur): bool
    {
        return $user->can('stock.dashboard.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock.fournisseur.manage');
    }

    public function update(User $user, Fournisseur $fournisseur): bool
    {
        return $user->can('stock.fournisseur.manage');
    }

    public function delete(User $user, Fournisseur $fournisseur): bool
    {
        return $user->can('stock.fournisseur.manage');
    }
}
