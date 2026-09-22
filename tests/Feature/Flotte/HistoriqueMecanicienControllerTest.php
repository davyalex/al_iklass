<?php

namespace Tests\Feature\Flotte;

use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use Database\Seeders\ParametreSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StatutVehiculeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoriqueMecanicienControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(StatutVehiculeSeeder::class);
        $this->seed(ParametreSeeder::class);
    }

    public function test_gestionnaire_parc_ne_peut_pas_acceder_a_la_page(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->get(route('flotte.mon-historique.index'))->assertForbidden();
    }

    public function test_chef_mecanicien_voit_uniquement_ses_propres_changements_de_statut(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $autreMecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutDepannage->id]);
        $autreVehicule = Vehicule::factory()->create(['statut_id' => $statutDepannage->id]);

        $this->actingAs($mecanicien)->postJson(route('flotte.vehicules.remise-circulation', $vehicule), [
            'rapport' => 'Freins changés.',
        ])->assertOk();

        $this->actingAs($autreMecanicien)->postJson(route('flotte.vehicules.remise-circulation', $autreVehicule), [
            'rapport' => 'Vidange faite.',
        ])->assertOk();

        $response = $this->actingAs($mecanicien)->getJson(route('flotte.mon-historique.statuts'));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
        $this->assertSame('Freins changés.', $response->json('data.0.commentaire'));
    }

    public function test_kpis_comptent_les_depannages_traites_du_mois(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutDepannage->id]);

        $this->actingAs($mecanicien)->postJson(route('flotte.vehicules.remise-circulation', $vehicule), [
            'rapport' => 'Réparé.',
        ])->assertOk();

        $response = $this->actingAs($mecanicien)->getJson(route('flotte.mon-historique.kpis'));

        $response->assertOk();
        $this->assertSame(1, $response->json('depannages_mois'));
    }
}
