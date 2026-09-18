<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_and_gestionnaire_stock_have_expected_stock_permissions(): void
    {
        $stockPermissions = [
            'stock.tableau_bord.voir',
            'stock.article.gerer',
            'stock.fournisseur.gerer',
            'stock.achat.gerer',
            'stock.paiement.gerer',
            'stock.sortie.interne',
            'stock.sortie.vente',
        ];

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');

        foreach ([...$stockPermissions, 'stock.demande.creer'] as $permission) {
            $this->assertTrue($admin->can($permission), "admin devrait avoir {$permission}");
        }

        foreach ($stockPermissions as $permission) {
            $this->assertTrue($gestionnaireStock->can($permission), "gestionnaire_stock devrait avoir {$permission}");
        }

        $this->assertFalse($gestionnaireStock->can('stock.demande.creer'));
    }

    public function test_chef_mecanicien_can_only_create_demandes(): void
    {
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');

        $this->assertTrue($chefMecanicien->can('stock.demande.creer'));
        $this->assertFalse($chefMecanicien->can('stock.article.gerer'));
        $this->assertFalse($chefMecanicien->can('stock.achat.gerer'));
    }

    public function test_gestionnaire_has_no_stock_permissions(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $this->assertFalse($gestionnaire->can('stock.tableau_bord.voir'));
        $this->assertFalse($gestionnaire->can('stock.article.gerer'));
    }

    public function test_superadmin_bypasses_every_permission_check(): void
    {
        $superadmin = User::factory()->create()->assignRole('superadmin');

        $this->assertTrue($superadmin->can('stock.article.gerer'));
        $this->assertTrue($superadmin->can('stock.demande.creer'));
        $this->assertTrue($superadmin->can('some.permission.that.does.not.exist'));
    }

    public function test_admin_user_seeder_creates_superadmin_with_random_password(): void
    {
        $this->seed(AdminUserSeeder::class);

        $user = User::where('email', 'alexkouamelan96@gmail.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('superadmin'));

        // Rejouer le seeder ne doit pas dupliquer le compte.
        $this->seed(AdminUserSeeder::class);
        $this->assertSame(1, User::where('email', 'alexkouamelan96@gmail.com')->count());
    }
}
