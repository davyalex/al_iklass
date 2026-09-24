<?php

namespace Tests\Feature\Financement;

use App\Models\TypePreteur;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypePreteurSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypePreteurControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TypePreteurSeeder::class);
    }

    public function test_admin_peut_creer_un_type_de_preteur(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->postJson(route('financements.types.store'), [
            'libelle' => 'Coopérative',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('types_preteur', ['code' => 'cooperative', 'libelle' => 'Coopérative', 'actif' => true]);
    }

    public function test_gestionnaire_stock_ne_peut_pas_gerer_les_types_malgre_ses_autres_permissions_financements(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($gestionnaireStock)->postJson(route('financements.types.store'), [
            'libelle' => 'Coopérative',
        ])->assertForbidden();
    }

    public function test_chef_mecanicien_ne_peut_pas_gerer_les_types(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($mecanicien)->postJson(route('financements.types.store'), [
            'libelle' => 'Coopérative',
        ])->assertForbidden();
    }

    public function test_admin_peut_modifier_le_libelle_et_desactiver_un_type(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $type = TypePreteur::where('code', 'banque')->firstOrFail();

        $response = $this->actingAs($admin)->putJson(route('financements.types.update', $type), [
            'libelle' => 'Banque commerciale',
            'actif' => false,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('types_preteur', [
            'id' => $type->id,
            'libelle' => 'Banque commerciale',
            'actif' => false,
        ]);
    }
}
