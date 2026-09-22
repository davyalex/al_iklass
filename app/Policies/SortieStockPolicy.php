<?php

namespace App\Policies;

use App\Models\SortieStock;
use App\Models\User;

class SortieStockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.tableau_bord.voir');
    }

    public function view(User $user, SortieStock $sortie): bool
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
