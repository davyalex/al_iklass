<?php

namespace App\Policies;

use App\Models\PaiementFournisseur;
use App\Models\User;

class PaiementFournisseurPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.dashboard.view');
    }

    public function view(User $user, PaiementFournisseur $paiement): bool
    {
        return $user->can('stock.dashboard.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock.paiement.manage');
    }
}
