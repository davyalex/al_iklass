<?php

namespace App\Policies;

use App\Models\RemboursementFinancement;
use App\Models\User;

class RemboursementFinancementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('financements.voir');
    }

    public function view(User $user, RemboursementFinancement $remboursement): bool
    {
        return $user->can('financements.voir');
    }

    public function create(User $user): bool
    {
        return $user->can('financements.rembourser');
    }
}
