<?php

namespace Tests\Feature\Flotte;

use App\Models\HistoriqueDette;
use App\Models\ModePaiement;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Services\Flotte\VersementService;
use Database\Seeders\ParametreSeeder;
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
        // Marque la réinitialisation quotidienne comme déjà faite aujourd'hui :
        // sans ça, le filet de sécurité (middleware sur les routes flotte.*)
        // remettrait tous les véhicules en circulation au premier accès à une
        // page flotte dans ce test, avant même les assertions sur leurs statuts.
        $this->seed(ParametreSeeder::class);
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

    public function test_page_affiche_le_bouton_annuler_dette_pour_un_admin(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        User::factory()->create(['dette' => 1000])->assignRole('gestionnaire');

        $this->actingAs($admin)->get(route('flotte.gestionnaires.index'))
            ->assertOk()
            ->assertSee('Annuler la dette');
    }

    public function test_admin_can_annuler_dette_partiellement(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 5000])->assignRole('gestionnaire');

        $response = $this->actingAs($admin)->postJson(route('flotte.gestionnaires.dette.annuler', $gestionnaire), [
            'montant' => 2000,
            'motif' => 'Erreur de saisie constatée',
        ]);

        $response->assertOk();
        $response->assertJsonPath('solde_du_brut', 3000);
        $this->assertEquals(3000, (float) $gestionnaire->fresh()->dette);

        $this->assertDatabaseHas('historique_dettes', [
            'gestionnaire_id' => $gestionnaire->id,
            'type' => 'annulation',
            'montant' => 2000,
            'dette_avant' => 5000,
            'dette_apres' => 3000,
            'motif' => 'Erreur de saisie constatée',
            'user_id' => $admin->id,
        ]);
    }

    public function test_annulation_de_dette_ecrit_une_entree_dans_le_journal_audit(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 5000, 'name' => 'Jean Kouassi'])->assignRole('gestionnaire');

        $this->actingAs($admin)->postJson(route('flotte.gestionnaires.dette.annuler', $gestionnaire), [
            'montant' => 2000,
            'motif' => 'Erreur de saisie constatée',
        ])->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => User::class,
            'subject_id' => $gestionnaire->id,
            'causer_id' => $admin->id,
            'description' => 'Dette de « Jean Kouassi » annulée pour 2000 FCFA — motif : Erreur de saisie constatée',
        ]);
    }

    public function test_annulation_superieure_a_la_dette_actuelle_est_rejetee(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 1000])->assignRole('gestionnaire');

        $response = $this->actingAs($admin)->postJson(route('flotte.gestionnaires.dette.annuler', $gestionnaire), [
            'montant' => 2000,
            'motif' => 'Test',
        ]);

        $response->assertStatus(422);
        $this->assertEquals(1000, (float) $gestionnaire->fresh()->dette);
        $this->assertDatabaseCount('historique_dettes', 0);
    }

    public function test_annulation_sans_motif_est_rejetee(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 1000])->assignRole('gestionnaire');

        $this->actingAs($admin)->postJson(route('flotte.gestionnaires.dette.annuler', $gestionnaire), [
            'montant' => 500,
        ])->assertUnprocessable()->assertJsonValidationErrors('motif');
    }

    public function test_gestionnaire_seul_ne_peut_pas_annuler_de_dette(): void
    {
        $gestionnaire = User::factory()->create(['dette' => 1000])->assignRole('gestionnaire');
        $autreGestionnaire = User::factory()->create(['dette' => 1000])->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->postJson(route('flotte.gestionnaires.dette.annuler', $autreGestionnaire), [
            'montant' => 500,
            'motif' => 'Test',
        ])->assertForbidden();
    }

    public function test_annulation_de_dette_necrit_aucun_mouvement_de_caisse(): void
    {
        $this->seed(StockReferenceSeeder::class);

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 5000])->assignRole('gestionnaire');

        $this->actingAs($admin)->postJson(route('flotte.gestionnaires.dette.annuler', $gestionnaire), [
            'montant' => 5000,
            'motif' => 'Annulation totale',
        ])->assertOk();

        $this->assertDatabaseCount('mouvements_caisse', 0);
    }

    public function test_compte_endpoint_expose_lhistorique_de_la_dette(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 5000])->assignRole('gestionnaire');

        HistoriqueDette::create([
            'gestionnaire_id' => $gestionnaire->id,
            'gestionnaire_nom' => $gestionnaire->name,
            'type' => 'bascule',
            'montant' => 5000,
            'dette_avant' => 0,
            'dette_apres' => 5000,
            'date_reference' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.gestionnaires.compte', $gestionnaire));

        $response->assertOk();
        $this->assertCount(1, $response->json('historique_dette'));
        $this->assertSame('bascule', $response->json('historique_dette.0.type'));
    }

    public function test_compte_endpoint_affiche_la_date_de_reference_pour_une_bascule(): void
    {
        // Une bascule est écrite par le job nocturne (created_at ≈ maintenant),
        // mais concerne le manque à verser d'hier (date_reference) : c'est
        // cette dernière qui doit être affichée, sinon on ne sait plus de
        // quel jour la dette provient.
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 5000])->assignRole('gestionnaire');

        HistoriqueDette::create([
            'gestionnaire_id' => $gestionnaire->id,
            'gestionnaire_nom' => $gestionnaire->name,
            'type' => 'bascule',
            'montant' => 5000,
            'dette_avant' => 0,
            'dette_apres' => 5000,
            'date_reference' => '2026-01-01',
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.gestionnaires.compte', $gestionnaire));

        $response->assertOk();
        $this->assertSame('01/01/2026', $response->json('historique_dette.0.date'));
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
