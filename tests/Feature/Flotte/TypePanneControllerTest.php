<?php

namespace Tests\Feature\Flotte;

use App\Models\TypePanne;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypePanneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypePanneControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TypePanneSeeder::class);
    }

    public function test_admin_peut_creer_un_type_de_panne(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->postJson(route('flotte.interventions.types.store'), [
            'libelle' => 'Climatisation',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('types_panne', ['code' => 'climatisation', 'libelle' => 'Climatisation', 'actif' => true]);
    }

    public function test_chef_mecanicien_ne_peut_pas_gerer_les_types(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($mecanicien)->postJson(route('flotte.interventions.types.store'), [
            'libelle' => 'Climatisation',
        ])->assertForbidden();
    }

    public function test_admin_peut_modifier_le_libelle_et_desactiver_un_type(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $type = TypePanne::where('code', 'moteur')->firstOrFail();

        $response = $this->actingAs($admin)->putJson(route('flotte.interventions.types.update', $type), [
            'libelle' => 'Moteur & transmission',
            'actif' => false,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('types_panne', [
            'id' => $type->id,
            'libelle' => 'Moteur & transmission',
            'actif' => false,
        ]);
    }
}
