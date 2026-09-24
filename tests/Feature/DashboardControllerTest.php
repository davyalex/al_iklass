<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Caisse;
use App\Models\Intervention;
use App\Models\ModePaiement;
use App\Models\OperationProgrammee;
use App\Models\Preteur;
use App\Models\StatutVehicule;
use App\Models\TypeOperation;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Versement;
use App\Services\Financement\FinancementService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StatutVehiculeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(StatutVehiculeSeeder::class);

        Caisse::firstOrCreate(['type' => 'versements'], ['type' => 'versements', 'libelle' => 'Versements']);
        Caisse::firstOrCreate(['type' => 'emprunt'], ['type' => 'emprunt', 'libelle' => 'Emprunts / financements']);
    }

    public function test_admin_sees_the_admin_dashboard(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard-admin');
    }

    /**
     * @return array<string, mixed>
     */
    private function donneesDeVue(TestResponse $response): array
    {
        return $response->original->getData();
    }

    public function test_gestionnaire_sees_the_modular_home_dashboard(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $response = $this->actingAs($gestionnaire)->get(route('dashboard'));
        $response->assertOk()->assertViewIs('dashboard-home');

        $donnees = $this->donneesDeVue($response);
        $this->assertArrayHasKey('parcGestionnaire', $donnees);
        $this->assertArrayNotHasKey('stock', $donnees);
        $this->assertArrayNotHasKey('interventions', $donnees);
    }

    public function test_gestionnaire_stock_sees_the_modular_home_dashboard(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');

        $response = $this->actingAs($gestionnaireStock)->get(route('dashboard'));
        $response->assertOk()->assertViewIs('dashboard-home');

        $donnees = $this->donneesDeVue($response);
        $this->assertArrayHasKey('stock', $donnees);
        $this->assertArrayHasKey('operations', $donnees);
        $this->assertArrayNotHasKey('parcGestionnaire', $donnees);
        $this->assertArrayNotHasKey('interventions', $donnees);
    }

    public function test_chef_mecanicien_sees_the_modular_home_dashboard(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');

        $response = $this->actingAs($mecanicien)->get(route('dashboard'));
        $response->assertOk()->assertViewIs('dashboard-home');

        $donnees = $this->donneesDeVue($response);
        $this->assertArrayHasKey('interventions', $donnees);
        $this->assertArrayNotHasKey('stock', $donnees);
        $this->assertArrayNotHasKey('parcGestionnaire', $donnees);
    }

    public function test_une_permission_supplementaire_accordee_a_un_role_par_defaut_fait_apparaitre_sa_section(): void
    {
        // Reproduit le scénario signalé : un admin accorde à gestionnaire_stock
        // une permission hors de son périmètre par défaut (ici interventions.voir)
        // depuis Admin > Rôles — la section correspondante doit apparaître sans
        // qu'on ait besoin de toucher au contrôleur ni de se reconnecter.
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $gestionnaireStock->givePermissionTo('interventions.voir');

        $response = $this->actingAs($gestionnaireStock)->get(route('dashboard'));
        $response->assertOk()->assertViewIs('dashboard-home');

        $donnees = $this->donneesDeVue($response);
        $this->assertArrayHasKey('stock', $donnees);
        $this->assertArrayHasKey('interventions', $donnees);
    }

    public function test_utilisateur_sans_permission_voit_le_tableau_de_bord_simple(): void
    {
        $utilisateur = User::factory()->create();

        $this->actingAs($utilisateur)->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard');
    }

    public function test_parc_gestionnaire_dashboard_est_borne_a_ses_propres_vehicules(): void
    {
        $enCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $arret = StatutVehicule::where('code', 'arret')->firstOrFail();

        $gestionnaire = User::factory()->create(['dette' => 1500])->assignRole('gestionnaire');
        $autreGestionnaire = User::factory()->create()->assignRole('gestionnaire');

        Vehicule::factory()->create(['statut_id' => $enCirculation->id, 'gestionnaire_id' => $gestionnaire->id, 'recette_journaliere' => 8000]);
        Vehicule::factory()->create(['statut_id' => $arret->id, 'gestionnaire_id' => $gestionnaire->id]);
        // Véhicule d'un autre gestionnaire : ne doit apparaître dans aucun KPI ci-dessous.
        Vehicule::factory()->create(['statut_id' => $enCirculation->id, 'gestionnaire_id' => $autreGestionnaire->id, 'recette_journaliere' => 99999]);

        $response = $this->actingAs($gestionnaire)->get(route('dashboard'));

        $response->assertOk();
        $parc = $response->viewData('parcGestionnaire');
        $this->assertSame(2, $parc['total']);
        $this->assertSame(1, $parc['disponibles']);

        $recette = $response->viewData('recetteDuJourGestionnaire');
        $this->assertEquals(8000, $recette['attendu']);

        $this->assertEquals(1500, $response->viewData('detteGestionnaire'));
    }

    public function test_entretien_dashboard_expose_les_interventions_en_cours(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $vehicule = Vehicule::factory()->create();

        Intervention::create([
            'vehicule_id' => $vehicule->id,
            'vehicule_code' => $vehicule->code,
            'description' => 'Panne moteur',
            'statut' => 'en_cours',
            'date_debut' => now(),
            'declaree_par_id' => $mecanicien->id,
        ]);

        $response = $this->actingAs($mecanicien)->get(route('dashboard'));

        $response->assertOk();
        $interventions = $response->viewData('interventions');
        $this->assertSame(1, $interventions['en_cours']);
        $this->assertSame(1, $interventions['ce_mois']);
        $this->assertCount(1, $interventions['liste_en_cours']);
    }

    public function test_stock_dashboard_expose_les_operations_en_retard(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $vehicule = Vehicule::factory()->create();
        $type = TypeOperation::create(['code' => 'vidange', 'libelle' => 'Vidange', 'periodicite_jours' => 90, 'actif' => true]);

        OperationProgrammee::create([
            'vehicule_id' => $vehicule->id,
            'vehicule_code' => $vehicule->code,
            'type_operation_id' => $type->id,
            'type_operation_code' => $type->code,
            'type_operation_libelle' => $type->libelle,
            'date_echeance' => now()->subDay(),
            'rappel_jours' => 15,
            'periodicite_jours' => 90,
            'statut' => 'planifiee',
            'user_id' => $gestionnaireStock->id,
        ]);

        $response = $this->actingAs($gestionnaireStock)->get(route('dashboard'));

        $response->assertOk();
        $operations = $response->viewData('operations');
        $this->assertSame(1, $operations['operations_en_retard']);
        $this->assertSame(0, $operations['operations_a_venir']);
    }

    public function test_parc_kpis_are_computed_correctly(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $enCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $arret = StatutVehicule::where('code', 'arret')->firstOrFail();

        Vehicule::factory()->count(3)->create(['statut_id' => $enCirculation->id]);
        Vehicule::factory()->count(1)->create(['statut_id' => $arret->id]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $parc = $response->viewData('parc');
        $this->assertSame(4, $parc['total']);
        $this->assertSame(3, $parc['disponibles']);
        $this->assertEquals(75, $parc['taux_disponibilite']);
    }

    public function test_recette_du_jour_and_dette_kpis_are_computed_correctly(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $enCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $gestionnaire = User::factory()->create(['dette' => 5000])->assignRole('gestionnaire');
        $vehicule = Vehicule::factory()->create([
            'statut_id' => $enCirculation->id,
            'recette_journaliere' => 10000,
            'gestionnaire_id' => $gestionnaire->id,
        ]);

        Versement::create([
            'gestionnaire_id' => $gestionnaire->id,
            'gestionnaire_nom' => $gestionnaire->name,
            'vehicule_id' => $vehicule->id,
            'vehicule_code' => $vehicule->code,
            'montant' => 4000,
            'mode_paiement_id' => $mode->id,
            'date_versement' => now(),
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $recette = $response->viewData('recetteDuJour');
        $this->assertEquals(10000, $recette['attendu']);
        $this->assertEquals(4000, $recette['verse']);
        $this->assertEquals(40, $recette['taux']);

        $dette = $response->viewData('dette');
        $this->assertEquals(5000, $dette['total']);
        $this->assertSame(1, $dette['nombre_gestionnaires']);
    }

    public function test_stock_kpis_flag_articles_en_alerte(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        Article::factory()->create(['quantite_stock' => 2, 'seuil_alerte' => 5, 'prix_achat' => 1000, 'actif' => true]);
        Article::factory()->create(['quantite_stock' => 50, 'seuil_alerte' => 5, 'prix_achat' => 500, 'actif' => true]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $stock = $response->viewData('stock');
        $this->assertSame(1, $stock['nombre_alertes']);
        $this->assertEquals(2000 + 25000, $stock['valeur_totale']);
    }

    public function test_financements_kpis_are_computed_correctly(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $preteur = Preteur::factory()->create();
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $financement = app(FinancementService::class)->declarer([
            'preteur_id' => $preteur->id,
            'montant_total' => 100000,
            'user_id' => $admin->id,
        ]);

        app(FinancementService::class)->rembourser([
            'financement_id' => $financement->id,
            'montant' => 30000,
            'mode_paiement_id' => $mode->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $financements = $response->viewData('financements');
        $this->assertEquals(70000, $financements['restant_du']);
        $this->assertSame(1, $financements['nombre_en_cours']);
    }

    public function test_caisses_soldes_reflect_mouvements(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $preteur = Preteur::factory()->create();

        app(FinancementService::class)->declarer([
            'preteur_id' => $preteur->id,
            'montant_total' => 50000,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $caisses = collect($response->viewData('caisses'))->keyBy('libelle');
        $this->assertEquals(50000, $caisses['Emprunts / financements']['solde']);
    }
}
