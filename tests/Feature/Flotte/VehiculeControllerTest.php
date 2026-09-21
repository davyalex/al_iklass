<?php

namespace Tests\Feature\Flotte;

use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StatutVehiculeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiculeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(StatutVehiculeSeeder::class);
    }

    public function test_admin_can_create_vehicule_with_gestionnaire(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statut = StatutVehicule::where('code', 'en_circulation')->firstOrFail();

        $response = $this->actingAs($admin)->postJson(route('flotte.vehicules.store'), [
            'code' => 'AL-100',
            'libelle' => 'Bus 30 places - Test',
            'statut_id' => $statut->id,
            'recette_journaliere' => 20000,
            'gestionnaire_id' => $gestionnaire->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('vehicules', ['code' => 'AL-100', 'gestionnaire_id' => $gestionnaire->id]);
    }

    public function test_gestionnaire_cannot_create_vehicule(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statut = StatutVehicule::where('code', 'en_circulation')->firstOrFail();

        $response = $this->actingAs($gestionnaire)->postJson(route('flotte.vehicules.store'), [
            'code' => 'AL-101',
            'libelle' => 'Bus 30 places - Test',
            'statut_id' => $statut->id,
            'recette_journaliere' => 20000,
        ]);

        $response->assertForbidden();
    }

    public function test_gestionnaire_only_sees_vehicules_attribues_dans_lindex(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statut = StatutVehicule::where('code', 'en_circulation')->firstOrFail();

        $vehiculeAttribue = Vehicule::factory()->create(['statut_id' => $statut->id, 'gestionnaire_id' => $gestionnaire->id]);
        $vehiculeAutre = Vehicule::factory()->create(['statut_id' => $statut->id, 'gestionnaire_id' => null]);

        $response = $this->actingAs($gestionnaire)->get(route('flotte.vehicules.index'));

        $response->assertOk();
        $vehicules = $response->viewData('vehicules');

        $this->assertTrue($vehicules->contains('id', $vehiculeAttribue->id));
        $this->assertFalse($vehicules->contains('id', $vehiculeAutre->id));
    }

    public function test_gestionnaire_cannot_view_vehicule_not_attribue(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statut = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statut->id, 'gestionnaire_id' => null]);

        $response = $this->actingAs($gestionnaire)->getJson(route('flotte.vehicules.show', $vehicule));

        $response->assertForbidden();
    }

    public function test_creation_fails_if_gestionnaire_id_does_not_have_gestionnaire_role(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $statut = StatutVehicule::where('code', 'en_circulation')->firstOrFail();

        $response = $this->actingAs($admin)->postJson(route('flotte.vehicules.store'), [
            'code' => 'AL-102',
            'libelle' => 'Bus 30 places - Test',
            'statut_id' => $statut->id,
            'recette_journaliere' => 20000,
            'gestionnaire_id' => $chefMecanicien->id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('gestionnaire_id');
    }

    public function test_code_and_immatriculation_must_be_unique(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $statut = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        Vehicule::factory()->create(['code' => 'AL-200', 'immatriculation' => 'CI-1234-AB']);

        $response = $this->actingAs($admin)->postJson(route('flotte.vehicules.store'), [
            'code' => 'AL-200',
            'libelle' => 'Doublon code',
            'statut_id' => $statut->id,
            'recette_journaliere' => 10000,
        ]);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('code');

        $response = $this->actingAs($admin)->postJson(route('flotte.vehicules.store'), [
            'code' => 'AL-201',
            'libelle' => 'Doublon immatriculation',
            'immatriculation' => 'CI-1234-AB',
            'statut_id' => $statut->id,
            'recette_journaliere' => 10000,
        ]);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('immatriculation');
    }

    public function test_statut_id_is_required(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->postJson(route('flotte.vehicules.store'), [
            'code' => 'AL-300',
            'libelle' => 'Sans statut',
            'recette_journaliere' => 10000,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('statut_id');
    }

    public function test_updating_statut_to_maintenance_sets_actif_to_false(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $statutMaintenance = StatutVehicule::where('code', 'maintenance')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'actif' => true]);

        $response = $this->actingAs($admin)->putJson(route('flotte.vehicules.update', $vehicule), [
            'code' => $vehicule->code,
            'libelle' => $vehicule->libelle,
            'statut_id' => $statutMaintenance->id,
            'recette_journaliere' => $vehicule->recette_journaliere,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('vehicules', ['id' => $vehicule->id, 'statut_id' => $statutMaintenance->id, 'actif' => false]);
    }

    public function test_status_change_is_logged_in_historique(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutCirculation->id]);

        // La création elle-même journalise déjà une entrée (statut initial).
        $this->assertDatabaseCount('historique_statuts_vehicule', 1);

        $this->actingAs($admin)->putJson(route('flotte.vehicules.update', $vehicule), [
            'code' => $vehicule->code,
            'libelle' => $vehicule->libelle,
            'statut_id' => $statutDepannage->id,
            'recette_journaliere' => $vehicule->recette_journaliere,
        ])->assertOk();

        $this->assertDatabaseHas('historique_statuts_vehicule', [
            'vehicule_id' => $vehicule->id,
            'ancien_statut_code' => 'en_circulation',
            'nouveau_statut_code' => 'depannage',
            'user_id' => $admin->id,
        ]);
    }

    public function test_historique_endpoint_returns_mouvements_and_changements_statut(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $statutMaintenance = StatutVehicule::where('code', 'maintenance')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutCirculation->id]);
        $vehicule->update(['statut_id' => $statutMaintenance->id]);

        $response = $this->actingAs($admin)->getJson(route('flotte.vehicules.historique', $vehicule));

        $response->assertOk();
        $evenements = $response->json('evenements');
        $this->assertNotEmpty($evenements);
        $this->assertSame('changement_statut', $evenements[0]['type']);
    }

    public function test_admin_can_change_statut_quickly(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutCirculation->id]);

        $response = $this->actingAs($admin)->patchJson(route('flotte.vehicules.statut', $vehicule), [
            'statut_id' => $statutDepannage->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('vehicules', ['id' => $vehicule->id, 'statut_id' => $statutDepannage->id, 'actif' => false]);
    }

    public function test_gestionnaire_cannot_change_statut(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $gestionnaire->id]);

        $response = $this->actingAs($gestionnaire)->patchJson(route('flotte.vehicules.statut', $vehicule), [
            'statut_id' => $statutDepannage->id,
        ]);

        $response->assertForbidden();
    }

    public function test_index_kpis_reflect_statut_counts_and_recette_en_circulation(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $statutArret = StatutVehicule::where('code', 'arret')->firstOrFail();

        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'recette_journaliere' => 10000]);
        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'recette_journaliere' => 15000]);
        Vehicule::factory()->create(['statut_id' => $statutArret->id, 'recette_journaliere' => 99999]);

        $response = $this->actingAs($admin)->get(route('flotte.vehicules.index'));

        $response->assertOk();
        $kpis = $response->viewData('kpis');

        $this->assertSame(2, $kpis['par_statut']['en_circulation']);
        $this->assertSame(1, $kpis['par_statut']['arret']);
        $this->assertSame('25 000', $kpis['recette_en_circulation']);
    }
}
