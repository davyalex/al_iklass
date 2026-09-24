<?php

namespace Tests\Feature\Flotte;

use App\Models\Intervention;
use App\Models\StatutVehicule;
use App\Models\TypePanne;
use App\Models\User;
use App\Models\Vehicule;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StatutVehiculeSeeder;
use Database\Seeders\TypePanneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterventionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(StatutVehiculeSeeder::class);
        $this->seed(TypePanneSeeder::class);
    }

    public function test_admin_peut_afficher_la_page(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('flotte.interventions.index'))->assertOk();
    }

    public function test_role_sans_droit_est_rejete(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->get(route('flotte.interventions.index'))->assertForbidden();
    }

    public function test_chef_mecanicien_peut_declarer_une_panne(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $vehicule = Vehicule::factory()->create();
        $moteur = TypePanne::where('code', 'moteur')->firstOrFail();
        $depannage = StatutVehicule::where('code', 'depannage')->firstOrFail();

        $response = $this->actingAs($mecanicien)->postJson(route('flotte.interventions.store'), [
            'vehicule_id' => $vehicule->id,
            'type_panne_id' => $moteur->id,
            'description' => 'Fuite d\'huile moteur constatée au retour de tournée.',
            'statut_id' => $depannage->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('interventions', [
            'vehicule_id' => $vehicule->id,
            'vehicule_code' => $vehicule->code,
            'type_panne_id' => $moteur->id,
            'type_panne_libelle' => 'Moteur',
            'statut' => 'en_cours',
            'declaree_par_id' => $mecanicien->id,
        ]);
        $this->assertSame($depannage->id, $vehicule->fresh()->statut_id);
    }

    public function test_declarer_rejette_si_une_intervention_est_deja_en_cours(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $vehicule = Vehicule::factory()->create();
        $depannage = StatutVehicule::where('code', 'depannage')->firstOrFail();

        Intervention::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'description' => 'Premier problème', 'statut' => 'en_cours', 'date_debut' => now(),
            'declaree_par_id' => $mecanicien->id,
        ]);

        $response = $this->actingAs($mecanicien)->postJson(route('flotte.interventions.store'), [
            'vehicule_id' => $vehicule->id,
            'description' => 'Deuxième problème',
            'statut_id' => $depannage->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('interventions', 1);
    }

    public function test_declarer_rejette_si_le_statut_reste_en_circulation(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $vehicule = Vehicule::factory()->create();
        $enCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();

        $response = $this->actingAs($mecanicien)->postJson(route('flotte.interventions.store'), [
            'vehicule_id' => $vehicule->id,
            'description' => 'Bruit suspect',
            'statut_id' => $enCirculation->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_gestionnaire_stock_ne_peut_pas_declarer_de_panne(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $vehicule = Vehicule::factory()->create();
        $depannage = StatutVehicule::where('code', 'depannage')->firstOrFail();

        $this->actingAs($gestionnaireStock)->postJson(route('flotte.interventions.store'), [
            'vehicule_id' => $vehicule->id,
            'description' => 'Panne',
            'statut_id' => $depannage->id,
        ])->assertForbidden();
    }

    public function test_detail_expose_lintervention_active_et_lhistorique(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();

        Intervention::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'description' => 'En cours', 'statut' => 'en_cours', 'date_debut' => now(),
            'declaree_par_id' => $admin->id,
        ]);

        Intervention::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'description' => 'Ancienne panne', 'statut' => 'terminee',
            'date_debut' => now()->subDays(10), 'date_fin' => now()->subDays(8),
            'rapport' => 'Réparée', 'declaree_par_id' => $admin->id, 'cloturee_par_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.interventions.detail', $vehicule));

        $response->assertOk();
        $this->assertNotNull($response->json('active'));
        $this->assertSame('En cours', $response->json('active.description'));
        $this->assertCount(1, $response->json('historique'));
    }

    public function test_remise_en_circulation_admin_cloture_lintervention_en_cours_avec_le_rapport(): void
    {
        // Le chef mécanicien n'a plus accès à la Flotte (voir ci-dessous) :
        // seul l'admin passe encore par cette route ; il partage la même
        // mécanique de clôture (InterventionService::cloturer) que le
        // chef mécanicien depuis Interventions.
        $admin = User::factory()->create()->assignRole('admin');
        $depannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $depannage->id]);

        $intervention = Intervention::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'description' => 'Fuite moteur', 'statut' => 'en_cours', 'date_debut' => now(),
            'declaree_par_id' => $admin->id,
        ]);

        $this->actingAs($admin)->postJson(route('flotte.vehicules.remise-circulation', $vehicule), [
            'rapport' => 'Joint de culasse changé, essai routier concluant.',
        ])->assertOk();

        $intervention->refresh();
        $this->assertSame('terminee', $intervention->statut);
        $this->assertSame('Joint de culasse changé, essai routier concluant.', $intervention->rapport);
        $this->assertSame($admin->id, $intervention->cloturee_par_id);
        $this->assertNotNull($intervention->date_fin);
    }

    public function test_remise_en_circulation_sans_intervention_en_cours_fonctionne_normalement(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $depannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $depannage->id]);

        $this->actingAs($admin)->postJson(route('flotte.vehicules.remise-circulation', $vehicule), [
            'rapport' => 'Simple correction de statut, pas de panne réelle.',
        ])->assertOk();

        $this->assertDatabaseCount('interventions', 0);
        $enCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $this->assertSame($enCirculation->id, $vehicule->fresh()->statut_id);
    }

    public function test_chef_mecanicien_peut_cloturer_depuis_le_menu_interventions(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $depannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $depannage->id]);

        $intervention = Intervention::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'description' => 'Fuite moteur', 'statut' => 'en_cours', 'date_debut' => now()->subDays(2),
            'declaree_par_id' => $mecanicien->id,
        ]);

        $response = $this->actingAs($mecanicien)->postJson(route('flotte.interventions.cloturer', $intervention), [
            'rapport' => 'Joint de culasse changé, essai routier concluant.',
            'date_fin' => now()->toDateString(),
        ]);

        $response->assertOk();

        $intervention->refresh();
        $this->assertSame('terminee', $intervention->statut);
        $this->assertSame('Joint de culasse changé, essai routier concluant.', $intervention->rapport);
        $this->assertSame($mecanicien->id, $intervention->cloturee_par_id);

        $enCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $this->assertSame($enCirculation->id, $vehicule->fresh()->statut_id);
    }

    public function test_cloturer_rejette_une_intervention_deja_terminee(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $vehicule = Vehicule::factory()->create();

        $intervention = Intervention::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'description' => 'Déjà réparée', 'statut' => 'terminee',
            'date_debut' => now()->subDays(5), 'date_fin' => now()->subDays(3),
            'rapport' => 'Réparée', 'declaree_par_id' => $mecanicien->id, 'cloturee_par_id' => $mecanicien->id,
        ]);

        $this->actingAs($mecanicien)->postJson(route('flotte.interventions.cloturer', $intervention), [
            'rapport' => 'Nouveau rapport',
        ])->assertStatus(422);
    }

    public function test_chef_mecanicien_na_plus_acces_a_la_flotte_ni_aux_operations(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        Vehicule::factory()->create();

        $this->actingAs($mecanicien)->get(route('flotte.vehicules.index'))->assertForbidden();
        $this->actingAs($mecanicien)->get(route('flotte.operations.index'))->assertForbidden();
    }
}
