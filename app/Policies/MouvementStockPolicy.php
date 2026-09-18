<?php

namespace App\Policies;

use App\Models\MouvementStock;
use App\Models\User;

class MouvementStockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.tableau_bord.voir');
    }

    public function view(User $user, MouvementStock $mouvement): bool
    {
        return $user->can('stock.tableau_bord.voir');
    }

    public function sortieInterne(User $user): bool
    {
        return $user->can('stock.sortie.interne');
    }

    public function sortieVente(User $user): bool
    {
        return $user->can('stock.sortie.vente');
    }
}
