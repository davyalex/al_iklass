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
            'libelle' => 'Peinture carrosserie',
            'periodicite_jours' => 730,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('types_operation', [
            'code' => 'peinture_carrosserie',
            'libelle' => 'Peinture carrosserie',
            'periodicite_jours' => 730,
            'actif' => true,
        ]);
    }

    public function test_le_code_est_derive_du_libelle_sans_etre_saisi(): void
    {
        // Aucun champ "code" n'est exposé à l'utilisateur : il est dérivé du
        // libellé, avec un suffixe numérique en cas de doublon.
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->postJson(route('flotte.operations.types.store'), ['libelle' => 'Peinture']);
        $this->actingAs($admin)->postJson(route('flotte.operations.types.store'), ['libelle' => 'Peinture']);

        $this->assertDatabaseHas('types_operation', ['libelle' => 'Peinture', 'code' => 'peinture']);
        $this->assertDatabaseHas('types_operation', ['libelle' => 'Peinture', 'code' => 'peinture_2']);
    }

    public function test_periodicite_nest_pas_obligatoire(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->postJson(route('flotte.operations.types.store'), ['libelle' => 'Contrôle freins'])
            ->assertCreated();

        $this->assertDatabaseHas('types_operation', ['libelle' => 'Contrôle freins', 'periodicite_jours' => null]);
    }

    public function test_gestionnaire_stock_ne_peut_pas_gerer_les_types(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($gestionnaireStock)->postJson(route('flotte.operations.types.store'), [
            'libelle' => 'Peinture',
        ])->assertForbidden();
    }

    public function test_chef_mecanicien_ne_peut_pas_gerer_les_types(): void
    {
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($chefMecanicien)->postJson(route('flotte.operations.types.store'), [
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
