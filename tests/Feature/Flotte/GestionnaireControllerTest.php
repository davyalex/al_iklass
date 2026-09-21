<?php

namespace Tests\Feature\Flotte;

use App\Models\ModePaiement;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Services\Flotte\VersementService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StatutVehiculeSeeder;
use Database\Seeders\StockReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionnaireControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(StatutVehiculeSeeder::class);
    }

    public function test_admin_sees_vehicules_grouped_by_gestionnaire(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $gestionnaire->id]);

        $response = $this->actingAs($admin)->get(route('flotte.gestionnaires.index'));

        $response->assertOk();
        $gestionnaires = $response->viewData('gestionnaires');

        $this->assertTrue($gestionnaires->contains('id', $gestionnaire->id));
        $this->assertTrue($gestionnaires->firstWhere('id', $gestionnaire->id)->vehiculesAttribues->contains('id', $vehicule->id));
    }

    public function test_gestionnaire_role_cannot_access_gestionnaires_page(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $response = $this->actingAs($gestionnaire)->get(route('flotte.gestionnaires.index'));

        $response->assertForbidden();
    }

    public function test_kpis_match_all_vehicules_regardless_of_gestionnaire(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $gestionnaireA = User::factory()->create()->assignRole('gestionnaire');
        $gestionnaireB = User::factory()->create()->assignRole('gestionnaire');

        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'recette_journaliere' => 10000, 'gestionnaire_id' => $gestionnaireA->id]);
        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'recette_journaliere' => 15000, 'gestionnaire_id' => $gestionnaireB->id]);
        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'recette_journaliere' => 5000]); // non affecté

        $response = $this->actingAs($admin)->get(route('flotte.gestionnaires.index'));

        $response->assertOk();
        $kpis = $response->viewData('kpis');

        $this->assertSame(3, $kpis['par_statut']['en_circulation']);
        $this->assertSame('30 000', $kpis['recette_en_circulation']);
    }

    public function test_page_shows_recette_a_verser_for_en_circulation_vehicules_only(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['name' => 'Awa Koné'])->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $statutArret = StatutVehicule::where('code', 'arret')->firstOrFail();

        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'recette_journaliere' => 12000, 'gestionnaire_id' => $gestionnaire->id]);
        Vehicule::factory()->create(['statut_id' => $statutArret->id, 'recette_journaliere' => 99999, 'gestionnaire_id' => $gestionnaire->id]);

        $response = $this->actingAs($admin)->get(route('flotte.gestionnaires.index'));

        $response->assertOk();
        $response->assertSee('12 000');
        $response->assertDontSee('99 999');
    }

    public function test_deja_verse_and_reste_a_verser_reflect_todays_versements(): void
    {
        $this->seed(StockReferenceSeeder::class);

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'recette_journaliere' => 20000, 'gestionnaire_id' => $gestionnaire->id]);

        $modePaiement = ModePaiement::first();
        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaire->id,
            'montant' => 8000,
            'mode_paiement_id' => $modePaiement->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('flotte.gestionnaires.index'));

        $response->assertOk();
        // Attendu 20 000, déjà versé 8 000, reste 12 000.
        $response->assertSeeText('8 000');
        $response->assertSeeText('12 000');
    }

    public function test_compte_endpoint_returns_kpis_and_recent_versements(): void
    {
        $this->seed(StockReferenceSeeder::class);

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['name' => 'Awa Koné', 'dette' => 5000])->assignRole('gestionnaire');
        $modePaiement = ModePaiement::first();

        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaire->id,
            'montant' => 10000,
            'mode_paiement_id' => $modePaiement->id,
            'date_versement' => now()->format('Y-m-d'),
            'user_id' => $admin->id,
        ]);
        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaire->id,
            'montant' => 7000,
            'mode_paiement_id' => $modePaiement->id,
            'date_versement' => now()->subMonths(2)->format('Y-m-d'),
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.gestionnaires.compte', $gestionnaire));

        $response->assertOk();
        $response->assertJsonPath('gestionnaire.name', 'Awa Koné');
        $response->assertJsonPath('kpis.total_tout_temps', '17 000');
        $response->assertJsonPath('kpis.nombre_versements', 2);
        $response->assertJsonPath('kpis.solde_du', '5 000');
        $this->assertCount(2, $response->json('versements_recents'));
    }

    public function test_compte_endpoint_rejects_non_gestionnaire_user(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($admin)->getJson(route('flotte.gestionnaires.compte', $chefMecanicien))->assertNotFound();
    }

    public function test_admin_can_create_gestionnaire_from_this_page(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->postJson(route('admin.users.store'), [
            'name' => 'Nouveau Gestionnaire',
            'username' => 'nouveau-gestionnaire',
            'telephone' => '0700000099',
            'role' => 'gestionnaire',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['username' => 'nouveau-gestionnaire']);
        $this->assertTrue(User::where('username', 'nouveau-gestionnaire')->first()->hasRole('gestionnaire'));
    }
}
