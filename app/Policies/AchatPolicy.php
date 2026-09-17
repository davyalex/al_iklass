<?php

namespace App\Policies;

use App\Models\Achat;
use App\Models\User;

class AchatPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.dashboard.view');
    }

    public function view(User $user, Achat $achat): bool
    {
        return $user->can('stock.dashboard.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock.achat.manage');
    }
}
