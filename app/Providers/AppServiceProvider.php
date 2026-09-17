<?php

namespace App\Providers;

use App\Models\Achat;
use App\Models\MouvementStock;
use App\Models\PaiementFournisseur;
use App\Models\User;
use App\Observers\AchatObserver;
use App\Observers\MouvementStockObserver;
use App\Observers\PaiementFournisseurObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('superadmin') ? true : null);

        Achat::observe(AchatObserver::class);
        PaiementFournisseur::observe(PaiementFournisseurObserver::class);
        MouvementStock::observe(MouvementStockObserver::class);
    }
}
