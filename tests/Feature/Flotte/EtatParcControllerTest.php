<?php

namespace Tests\Feature\Flotte;

use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use Database\Seeders\ParametreSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StatutVehiculeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EtatParcControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(StatutVehiculeSeeder::class);
        $this->seed(ParametreSeeder::class);
    }

    public function test_admin_voit_les_vehicules_groupes_par_statut_dune_date_passee(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 08:00'));

        $admin = User::factory()->create()->assignRole('admin');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();

        // Créé et en circulation depuis le 1er, puis passé en dépannage le 3.
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutCirculation->id]);

        $this->travelTo(Carbon::parse('2026-01-03 09:00'));
        $vehicule->update(['statut_id' => $statutDepannage->id]);

        $this->travelTo(Carbon::parse('2026-01-05 10:00'));

        // Au 2 janvier (avant le passage en dépannage), le véhicule était en circulation.
        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', ['date' => '2026-01-02']));
        $response->assertOk();
        $parStatut = $response->viewData('vehiculesParStatut');
        $this->assertTrue($parStatut->get('en_circulation', collect())->contains('id', $vehicule->id));
        $this->assertFalse($parStatut->get('depannage', collect())->contains('id', $vehicule->id));

        // Au 4 janvier (après), il est en dépannage.
        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', ['date' => '2026-01-04']));
        $response->assertOk();
        $parStatut = $response->viewData('vehiculesParStatut');
        $this->assertTrue($parStatut->get('depannage', collect())->contains('id', $vehicule->id));
        $this->assertFalse($parStatut->get('en_circulation', collect())->contains('id', $vehicule->id));
    }

    public function test_par_defaut_la_date_est_hier(): void
    {
        $this->travelTo(Carbon::parse('2026-01-05 10:00'));

        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index'));

        $response->assertOk();
        $this->assertSame('2026-01-04', $response->viewData('date')->format('Y-m-d'));
    }

    public function test_vehicule_cree_apres_la_date_demandee_est_exclu(): void
    {
        $this->travelTo(Carbon::parse('2026-01-05 10:00'));

        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();

        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', ['date' => '2026-01-01']));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('vehiculesInexistants'));
        $parStatut = $response->viewData('vehiculesParStatut');
        $this->assertFalse($parStatut->flatten()->contains('id', $vehicule->id));
    }

    public function test_gestionnaire_ne_voit_que_ses_propres_vehicules(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 08:00'));

        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $vehiculeAffecte = Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $gestionnaire->id]);
        $vehiculeAutre = Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => null]);

        $this->travelTo(Carbon::parse('2026-01-05 10:00'));

        $response = $this->actingAs($gestionnaire)->get(route('flotte.etat-parc.index', ['date' => '2026-01-04']));

        $response->assertOk();
        $parStatut = $response->viewData('vehiculesParStatut');
        $this->assertTrue($parStatut->get('en_circulation', collect())->contains('id', $vehiculeAffecte->id));
        $this->assertFalse($parStatut->get('en_circulation', collect())->contains('id', $vehiculeAutre->id));
    }

    public function test_admin_peut_filtrer_par_gestionnaire(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 08:00'));

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaireA = User::factory()->create()->assignRole('gestionnaire');
        $gestionnaireB = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $vehiculeA = Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $gestionnaireA->id]);
        $vehiculeB = Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $gestionnaireB->id]);

        $this->travelTo(Carbon::parse('2026-01-05 10:00'));

        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', [
            'date' => '2026-01-04',
            'gestionnaire_id' => $gestionnaireA->id,
        ]));

        $response->assertOk();
        $parStatut = $response->viewData('vehiculesParStatut');
        $this->assertTrue($parStatut->get('en_circulation', collect())->contains('id', $vehiculeA->id));
        $this->assertFalse($parStatut->get('en_circulation', collect())->contains('id', $vehiculeB->id));
    }

    public function test_role_sans_acces_flotte_est_rejete(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($gestionnaireStock)->get(route('flotte.etat-parc.index'))->assertForbidden();
    }
}
