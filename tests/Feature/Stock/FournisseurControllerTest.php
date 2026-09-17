<?php

namespace Tests\Feature\Stock;

use App\Models\Fournisseur;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FournisseurControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gestionnaire_stock_can_create_and_update_fournisseur(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $create = $this->actingAs($user)->postJson(route('stock.fournisseurs.store'), [
            'nom' => 'Pièces Abidjan SARL',
            'telephone' => '0102030405',
        ]);
        $create->assertCreated();

        $fournisseur = Fournisseur::where('nom', 'Pièces Abidjan SARL')->firstOrFail();

        $this->actingAs($user)->putJson(route('stock.fournisseurs.update', $fournisseur), [
            'nom' => 'Pièces Abidjan SARL',
            'telephone' => '0709080706',
        ])->assertOk();

        $this->assertSame('0709080706', $fournisseur->fresh()->telephone);
    }

    public function test_chef_mecanicien_cannot_manage_fournisseurs(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->postJson(route('stock.fournisseurs.store'), [
            'nom' => 'Test',
        ])->assertForbidden();
    }

    public function test_destroy_soft_deletes_fournisseur(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $fournisseur = Fournisseur::factory()->create();

        $this->actingAs($user)->deleteJson(route('stock.fournisseurs.destroy', $fournisseur))
            ->assertOk();

        $this->assertSoftDeleted($fournisseur);
    }
}
