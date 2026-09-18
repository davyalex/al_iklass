<?php

namespace App\Policies;

use App\Models\BonCommande;
use App\Models\User;

class BonCommandePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.tableau_bord.voir');
    }

    public function view(User $user, BonCommande $bonCommande): bool
    {
        return $user->can('stock.tableau_bord.voir');
    }

    public function create(User $user): bool
    {
        return $user->can('stock.bon_commande.gerer');
    }

    public function update(User $user, BonCommande $bonCommande): bool
    {
        return $user->can('stock.bon_commande.gerer');
    }

    public function delete(User $user, BonCommande $bonCommande): bool
    {
        return $user->can('stock.bon_commande.gerer');
    }
}
