<?php

namespace App\Providers;

use App\Models\Achat;
use App\Models\MouvementStock;
use App\Models\PaiementFournisseur;
use App\Models\Parametre;
use App\Models\User;
use App\Models\Vehicule;
use App\Observers\AchatObserver;
use App\Observers\MouvementStockObserver;
use App\Observers\PaiementFournisseurObserver;
use App\Observers\ParametreObserver;
use App\Observers\VehiculeObserver;
use App\Policies\RolePolicy;
use App\Services\Admin\AuditService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        Paginator::useBootstrapFive();

        Gate::before(fn (User $user) => $user->hasRole('superadmin') ? true : null);

        Gate::policy(Role::class, RolePolicy::class);

        Achat::observe(AchatObserver::class);
        PaiementFournisseur::observe(PaiementFournisseurObserver::class);
        MouvementStock::observe(MouvementStockObserver::class);
        Vehicule::observe(VehiculeObserver::class);
        Parametre::observe(ParametreObserver::class);

        $this->brancherJournalAudit();
    }

    /**
     * Audit automatique : chaque écriture sur un modèle applicatif et chaque
     * connexion/déconnexion est journalisée (cf. AuditService).
     */
    private function brancherJournalAudit(): void
    {
        foreach (['created', 'updated', 'deleted', 'restored'] as $evenement) {
            Event::listen(
                "eloquent.{$evenement}: App\\Models\\*",
                fn (string $nomEvenement, array $donnees) => app(AuditService::class)->journaliserModele($evenement, $donnees[0]),
            );
        }

        Event::listen(function (Login $event): void {
            if ($event->user instanceof User) {
                app(AuditService::class)->journaliserConnexion($event->user);
            }
        });

        Event::listen(function (Logout $event): void {
            if ($event->user instanceof User) {
                app(AuditService::class)->journaliserDeconnexion($event->user);
            }
        });
    }
}
