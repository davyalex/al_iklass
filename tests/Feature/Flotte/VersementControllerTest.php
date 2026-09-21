<?php

namespace Tests\Feature\Flotte;

use App\Models\Caisse;
use App\Models\ModePaiement;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Services\Flotte\VersementService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StatutVehiculeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        Caisse::firstOrCreate(['type' => 'versements'], ['libelle' => 'Versements gestionnaires']);
    }

    public function test_admin_can_record_a_versement(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $modePaiement = ModePaiement::create(['code' => 'especes', 'libelle' => 'Espèces', 'actif' => true]);

        $response = $this->actingAs($admin)->postJson(route('flotte.versements.store'), [
            'gestionnaire_id' => $gestionnaire->id,
            'montant' => 15000,
            'mode_paiement_id' => $modePaiement->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('versements', [
            'gestionnaire_id' => $gestionnaire->id,
            'gestionnaire_nom' => $gestionnaire->name,
            'montant' => 15000,
            'user_id' => $admin->id,
        ]);
    }

    public function test_versement_writes_a_mouvement_caisse_entree(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $modePaiement = ModePaiement::create(['code' => 'wave', 'libelle' => 'Wave', 'actif' => true]);
        $caisse = Caisse::where('type', 'versements')->firstOrFail();

        $this->actingAs($admin)->postJson(route('flotte.versements.store'), [
            'gestionnaire_id' => $gestionnaire->id,
            'montant' => 20000,
            'mode_paiement_id' => $modePaiement->id,
        ])->assertCreated();

        $this->assertDatabaseHas('mouvements_caisse', [
            'caisse_id' => $caisse->id,
            'sens' => 'entree',
            'montant' => 20000,
            'user_id' => $admin->id,
        ]);
    }

    public function test_gestionnaire_cannot_record_a_versement(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $autre = User::factory()->create()->assignRole('gestionnaire');
        $modePaiement = ModePaiement::create(['code' => 'especes', 'libelle' => 'Espèces', 'actif' => true]);

        $this->actingAs($gestionnaire)->postJson(route('flotte.versements.store'), [
            'gestionnaire_id' => $autre->id,
            'montant' => 10000,
            'mode_paiement_id' => $modePaiement->id,
        ])->assertForbidden();
    }

    public function test_admin_can_record_a_versement_for_a_specific_vehicule(): void
    {
        $this->seed(StatutVehiculeSeeder::class);

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $gestionnaire->id]);
        $modePaiement = ModePaiement::create(['code' => 'especes', 'libelle' => 'Espèces', 'actif' => true]);

        $response = $this->actingAs($admin)->postJson(route('flotte.versements.store'), [
            'gestionnaire_id' => $gestionnaire->id,
            'vehicule_id' => $vehicule->id,
            'montant' => 15000,
            'mode_paiement_id' => $modePaiement->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('versements', [
            'gestionnaire_id' => $gestionnaire->id,
            'vehicule_id' => $vehicule->id,
            'vehicule_code' => $vehicule->code,
        ]);
    }

    public function test_vehicule_id_must_belong_to_the_selected_gestionnaire(): void
    {
        $this->seed(StatutVehiculeSeeder::class);

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $autreGestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $vehiculeDuneAutrePersonne = Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $autreGestionnaire->id]);
        $modePaiement = ModePaiement::create(['code' => 'especes', 'libelle' => 'Espèces', 'actif' => true]);

        $response = $this->actingAs($admin)->postJson(route('flotte.versements.store'), [
            'gestionnaire_id' => $gestionnaire->id,
            'vehicule_id' => $vehiculeDuneAutrePersonne->id,
            'montant' => 15000,
            'mode_paiement_id' => $modePaiement->id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('vehicule_id');
    }

    public function test_gestionnaire_id_must_belong_to_a_gestionnaire_role_user(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $modePaiement = ModePaiement::create(['code' => 'especes', 'libelle' => 'Espèces', 'actif' => true]);

        $response = $this->actingAs($admin)->postJson(route('flotte.versements.store'), [
            'gestionnaire_id' => $chefMecanicien->id,
            'montant' => 10000,
            'mode_paiement_id' => $modePaiement->id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('gestionnaire_id');
    }

    public function test_admin_can_view_versements_index(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('flotte.versements.index'))->assertOk();
    }

    public function test_gestionnaire_cannot_view_versements_index(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->get(route('flotte.versements.index'))->assertForbidden();
    }

    public function test_data_endpoint_can_be_filtered_by_gestionnaire(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaireA = User::factory()->create()->assignRole('gestionnaire');
        $gestionnaireB = User::factory()->create()->assignRole('gestionnaire');
        $modePaiement = ModePaiement::create(['code' => 'especes', 'libelle' => 'Espèces', 'actif' => true]);

        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaireA->id, 'montant' => 5000, 'mode_paiement_id' => $modePaiement->id, 'user_id' => $admin->id,
        ]);
        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaireB->id, 'montant' => 8000, 'mode_paiement_id' => $modePaiement->id, 'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.versements.data', [
            'gestionnaire_id' => $gestionnaireA->id,
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_data_endpoint_can_be_filtered_by_date_range(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $modePaiement = ModePaiement::create(['code' => 'especes', 'libelle' => 'Espèces', 'actif' => true]);

        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaire->id, 'montant' => 5000, 'mode_paiement_id' => $modePaiement->id,
            'date_versement' => now()->subDays(10)->format('Y-m-d'), 'user_id' => $admin->id,
        ]);
        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaire->id, 'montant' => 8000, 'mode_paiement_id' => $modePaiement->id,
            'date_versement' => now()->format('Y-m-d'), 'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.versements.data', [
            'date_debut' => now()->subDay()->format('Y-m-d'),
            'date_fin' => now()->addDay()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_kpis_endpoint_scoped_to_a_gestionnaire(): void
    {
        $this->seed(StatutVehiculeSeeder::class);

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 3000])->assignRole('gestionnaire');
        $autre = User::factory()->create(['dette' => 9000])->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $modePaiement = ModePaiement::create(['code' => 'especes', 'libelle' => 'Espèces', 'actif' => true]);

        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'recette_journaliere' => 20000, 'gestionnaire_id' => $gestionnaire->id]);
        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'recette_journaliere' => 99999, 'gestionnaire_id' => $autre->id]);

        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaire->id, 'montant' => 6000, 'mode_paiement_id' => $modePaiement->id, 'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.versements.kpis', ['gestionnaire_id' => $gestionnaire->id]));

        $response->assertOk();
        $response->assertJsonPath('recette_journaliere', '20 000');
        $response->assertJsonPath('deja_verse_jour', '6 000');
        $response->assertJsonPath('reste_a_verser_jour', '14 000');
        $response->assertJsonPath('montant_du', '3 000');
    }

    public function test_kpis_endpoint_aggregates_all_gestionnaires_when_unfiltered(): void
    {
        $this->seed(StatutVehiculeSeeder::class);

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaireA = User::factory()->create(['dette' => 3000])->assignRole('gestionnaire');
        $gestionnaireB = User::factory()->create(['dette' => 9000])->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();

        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'recette_journaliere' => 20000, 'gestionnaire_id' => $gestionnaireA->id]);
        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'recette_journaliere' => 15000, 'gestionnaire_id' => $gestionnaireB->id]);

        $response = $this->actingAs($admin)->getJson(route('flotte.versements.kpis'));

        $response->assertOk();
        $response->assertJsonPath('recette_journaliere', '35 000');
        $response->assertJsonPath('montant_du', '12 000');
    }

    public function test_admin_can_export_versements_excel_and_pdf(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $modePaiement = ModePaiement::create(['code' => 'especes', 'libelle' => 'Espèces', 'actif' => true]);

        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaire->id, 'montant' => 5000, 'mode_paiement_id' => $modePaiement->id, 'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('flotte.versements.export.excel'))->assertOk();
        $this->actingAs($admin)->get(route('flotte.versements.export.pdf'))->assertOk();
    }
}
