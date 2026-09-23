<?php

namespace Tests\Feature\Flotte;

use App\Models\TypeOperation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypeOperationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypeOperationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TypeOperationSeeder::class);
    }

    public function test_admin_peut_creer_un_type_operation(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->postJson(route('flotte.operations.types.store'), [
            'code' => 'peinture',
            'libelle' => 'Peinture carrosserie',
            'periodicite_jours' => 730,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('types_operation', [
            'code' => 'peinture',
            'libelle' => 'Peinture carrosserie',
            'periodicite_jours' => 730,
            'actif' => true,
        ]);
    }

    public function test_code_doit_etre_unique(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->postJson(route('flotte.operations.types.store'), [
            'code' => 'vidange',
            'libelle' => 'Doublon',
        ])->assertStatus(422);
    }

    public function test_gestionnaire_stock_ne_peut_pas_gerer_les_types(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($gestionnaireStock)->postJson(route('flotte.operations.types.store'), [
            'code' => 'peinture',
            'libelle' => 'Peinture',
        ])->assertForbidden();
    }

    public function test_chef_mecanicien_ne_peut_pas_gerer_les_types(): void
    {
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($chefMecanicien)->postJson(route('flotte.operations.types.store'), [
            'code' => 'peinture',
            'libelle' => 'Peinture',
        ])->assertForbidden();
    }

    public function test_admin_peut_modifier_le_libelle_et_desactiver_un_type(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $type = TypeOperation::where('code', 'vidange')->firstOrFail();

        $response = $this->actingAs($admin)->putJson(route('flotte.operations.types.update', $type), [
            'libelle' => 'Vidange moteur',
            'periodicite_jours' => 100,
            'actif' => false,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('types_operation', [
            'id' => $type->id,
            'libelle' => 'Vidange moteur',
            'periodicite_jours' => 100,
            'actif' => false,
        ]);
    }
}
