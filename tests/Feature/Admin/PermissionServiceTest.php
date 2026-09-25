<?php

namespace Tests\Feature\Admin;

use App\Services\Admin\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cree_toutes_les_permissions_manquantes(): void
    {
        $this->assertSame(0, Permission::count());

        $resultat = app(PermissionService::class)->synchroniserDeFaconAdditive();

        $totalConfigure = collect(config('permissions.permissions'))->flatten()->count();
        $this->assertSame($totalConfigure, $resultat['permissions_creees']);
        $this->assertSame($totalConfigure, Permission::count());
    }

    public function test_est_idempotent_sur_un_second_appel(): void
    {
        $service = app(PermissionService::class);
        $service->synchroniserDeFaconAdditive();

        $second = $service->synchroniserDeFaconAdditive();

        $this->assertSame(0, $second['permissions_creees']);
        $this->assertSame(0, $second['attributions_ajoutees']);
    }

    public function test_attribue_les_permissions_par_defaut_au_role_admin(): void
    {
        app(PermissionService::class)->synchroniserDeFaconAdditive();

        $admin = Role::findByName('admin');

        $this->assertTrue($admin->hasPermissionTo('stock.achat.gerer'));
        $this->assertFalse($admin->hasPermissionTo('flotte.vehicule.voir_affectes'));
    }

    public function test_ne_retire_jamais_une_permission_ajoutee_manuellement_a_un_role(): void
    {
        $service = app(PermissionService::class);
        $service->synchroniserDeFaconAdditive();

        // Simule une personnalisation faite depuis Admin > Rôles : le rôle
        // "gestionnaire" (parc, normalement limité à Flotte) reçoit en plus
        // une permission Stock, hors de sa config par défaut.
        $gestionnaire = Role::findByName('gestionnaire');
        $gestionnaire->givePermissionTo('stock.article.gerer');

        $service->synchroniserDeFaconAdditive();

        $gestionnaire->refresh();
        $this->assertTrue($gestionnaire->hasPermissionTo('stock.article.gerer'));
        $this->assertTrue($gestionnaire->hasPermissionTo('flotte.vehicule.voir_affectes'));
    }
}
