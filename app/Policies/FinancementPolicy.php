<?php

namespace App\Policies;

use App\Models\Financement;
use App\Models\User;

class FinancementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('financements.voir');
    }

    public function view(User $user, Financement $financement): bool
    {
        return $user->can('financements.voir');
    }

    public function create(User $user): bool
    {
        return $user->can('financements.gerer');
    }
}
