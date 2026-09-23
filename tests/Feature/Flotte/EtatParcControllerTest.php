<?php

namespace Tests\Feature\Flotte;

use App\Models\ModePaiement;
use App\Models\Parametre;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Services\Flotte\VersementService;
use Database\Seeders\ParametreSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StatutVehiculeSeeder;
use Database\Seeders\StockReferenceSeeder;
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

    /**
     * Empêche le filet de sécurité de statut journalier (et la bascule de
     * dette qu'il déclenche depuis) de s'exécuter au premier accès à une
     * page flotte après un travelTo() : les tests ci-dessous ne veulent
     * tester que le calcul du rapport, pas ce filet de sécurité.
     */
    private function suspendreFiletDeSecurite(): void
    {
        Parametre::updateOrCreate(
            ['cle' => 'flotte.statut_journalier.derniere_execution'],
            ['valeur' => now()->format('Y-m-d'), 'libelle' => 'x', 'groupe' => 'interne', 'ordre' => 0]
        );
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
        $this->suspendreFiletDeSecurite();

        // Au 2 janvier (avant le passage en dépannage), le véhicule était en circulation.
        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', [
            'date_debut' => '2026-01-02', 'date_fin' => '2026-01-02',
        ]));
        $response->assertOk();
        $parStatut = $response->viewData('vehiculesParStatut');
        $this->assertTrue($parStatut->get('en_circulation', collect())->contains('id', $vehicule->id));
        $this->assertFalse($parStatut->get('depannage', collect())->contains('id', $vehicule->id));

        // Au 4 janvier (après), il est en dépannage.
        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', [
            'date_debut' => '2026-01-04', 'date_fin' => '2026-01-04',
        ]));
        $response->assertOk();
        $parStatut = $response->viewData('vehiculesParStatut');
        $this->assertTrue($parStatut->get('depannage', collect())->contains('id', $vehicule->id));
        $this->assertFalse($parStatut->get('en_circulation', collect())->contains('id', $vehicule->id));
    }

    public function test_par_defaut_lintervalle_est_hier_hier(): void
    {
        $this->travelTo(Carbon::parse('2026-01-05 10:00'));
        $this->suspendreFiletDeSecurite();

        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index'));

        $response->assertOk();
        $this->assertSame('2026-01-04', $response->viewData('du')->format('Y-m-d'));
        $this->assertSame('2026-01-04', $response->viewData('au')->format('Y-m-d'));
    }

    public function test_dates_inversees_sont_remises_dans_lordre(): void
    {
        $this->travelTo(Carbon::parse('2026-01-05 10:00'));
        $this->suspendreFiletDeSecurite();

        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', [
            'date_debut' => '2026-01-04', 'date_fin' => '2026-01-01',
        ]));

        $response->assertOk();
        $this->assertSame('2026-01-01', $response->viewData('du')->format('Y-m-d'));
        $this->assertSame('2026-01-04', $response->viewData('au')->format('Y-m-d'));
    }

    public function test_vehicule_cree_apres_la_fin_de_periode_est_exclu(): void
    {
        $this->travelTo(Carbon::parse('2026-01-05 10:00'));
        $this->suspendreFiletDeSecurite();

        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();

        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', [
            'date_debut' => '2026-01-01', 'date_fin' => '2026-01-01',
        ]));

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
        $this->suspendreFiletDeSecurite();

        $response = $this->actingAs($gestionnaire)->get(route('flotte.etat-parc.index', [
            'date_debut' => '2026-01-04', 'date_fin' => '2026-01-04',
        ]));

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
        $this->suspendreFiletDeSecurite();

        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', [
            'date_debut' => '2026-01-04',
            'date_fin' => '2026-01-04',
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

    public function test_page_affiche_le_tableau_de_situation_financiere(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        User::factory()->create(['name' => 'Awa Koné'])->assignRole('gestionnaire');

        $this->actingAs($admin)->get(route('flotte.etat-parc.index'))
            ->assertOk()
            ->assertSee('Situation financière')
            ->assertSee('Awa Koné');
    }

    public function test_situation_financiere_calcule_attendu_deja_verse_et_reste_a_verser_a_la_date(): void
    {
        $this->seed(StockReferenceSeeder::class);
        $this->travelTo(Carbon::parse('2026-01-01 08:00'));

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 3000])->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        Vehicule::factory()->create([
            'statut_id' => $statutCirculation->id,
            'gestionnaire_id' => $gestionnaire->id,
            'recette_journaliere' => 20000,
        ]);

        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaire->id,
            'montant' => 12000,
            'mode_paiement_id' => ModePaiement::first()->id,
            'date_versement' => '2026-01-04',
            'user_id' => $admin->id,
        ]);

        $this->travelTo(Carbon::parse('2026-01-05 10:00'));
        $this->suspendreFiletDeSecurite();

        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', [
            'date_debut' => '2026-01-04', 'date_fin' => '2026-01-04',
        ]));

        $response->assertOk();
        $ligne = $response->viewData('situationFinanciere')->firstWhere('gestionnaire.id', $gestionnaire->id);
        $this->assertEquals(20000, $ligne['attendu']);
        $this->assertEquals(12000, $ligne['deja_verse']);
        $this->assertEquals(8000, $ligne['reste_a_verser']);
        $this->assertFalse($ligne['a_jour']);
        $this->assertEquals(3000, $ligne['solde_dette']);
    }

    public function test_situation_financiere_a_jour_quand_totalement_verse(): void
    {
        $this->seed(StockReferenceSeeder::class);
        $this->travelTo(Carbon::parse('2026-01-01 08:00'));

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        Vehicule::factory()->create([
            'statut_id' => $statutCirculation->id,
            'gestionnaire_id' => $gestionnaire->id,
            'recette_journaliere' => 15000,
        ]);

        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaire->id,
            'montant' => 15000,
            'mode_paiement_id' => ModePaiement::first()->id,
            'date_versement' => '2026-01-04',
            'user_id' => $admin->id,
        ]);

        $this->travelTo(Carbon::parse('2026-01-05 10:00'));
        $this->suspendreFiletDeSecurite();

        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', [
            'date_debut' => '2026-01-04', 'date_fin' => '2026-01-04',
        ]));

        $ligne = $response->viewData('situationFinanciere')->firstWhere('gestionnaire.id', $gestionnaire->id);
        $this->assertEquals(0, $ligne['reste_a_verser']);
        $this->assertTrue($ligne['a_jour']);
    }

    public function test_gestionnaire_ne_voit_que_sa_propre_situation_financiere(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 08:00'));

        $gestionnaireA = User::factory()->create()->assignRole('gestionnaire');
        $gestionnaireB = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $gestionnaireA->id]);
        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $gestionnaireB->id]);

        $this->travelTo(Carbon::parse('2026-01-05 10:00'));
        $this->suspendreFiletDeSecurite();

        $response = $this->actingAs($gestionnaireA)->get(route('flotte.etat-parc.index', [
            'date_debut' => '2026-01-04', 'date_fin' => '2026-01-04',
        ]));

        $situation = $response->viewData('situationFinanciere');
        $this->assertCount(1, $situation);
        $this->assertSame($gestionnaireA->id, $situation->first()['gestionnaire']->id);
    }

    public function test_admin_filtre_par_gestionnaire_limite_la_situation_financiere(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 08:00'));

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaireA = User::factory()->create()->assignRole('gestionnaire');
        $gestionnaireB = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $gestionnaireA->id]);
        Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $gestionnaireB->id]);

        $this->travelTo(Carbon::parse('2026-01-05 10:00'));
        $this->suspendreFiletDeSecurite();

        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', [
            'date_debut' => '2026-01-04',
            'date_fin' => '2026-01-04',
            'gestionnaire_id' => $gestionnaireA->id,
        ]));

        $situation = $response->viewData('situationFinanciere');
        $this->assertCount(1, $situation);
        $this->assertSame($gestionnaireA->id, $situation->first()['gestionnaire']->id);
    }

    public function test_situation_financiere_sagrege_jour_par_jour_sur_un_intervalle(): void
    {
        $this->seed(StockReferenceSeeder::class);
        $this->travelTo(Carbon::parse('2026-01-01 08:00'));

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        Vehicule::factory()->create([
            'statut_id' => $statutCirculation->id,
            'gestionnaire_id' => $gestionnaire->id,
            'recette_journaliere' => 10000,
        ]);

        // 3 jours en circulation (1, 2, 3 janvier) : attendu = 30 000.
        // Versé 10 000 le 1er (couvert), rien le 2 (manque 10 000), 15 000 le 3
        // (excédent de 5 000 qui ne doit PAS compenser le manque du 2).
        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaire->id,
            'montant' => 10000,
            'mode_paiement_id' => ModePaiement::first()->id,
            'date_versement' => '2026-01-01',
            'user_id' => $admin->id,
        ]);
        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaire->id,
            'montant' => 15000,
            'mode_paiement_id' => ModePaiement::first()->id,
            'date_versement' => '2026-01-03',
            'user_id' => $admin->id,
        ]);

        $this->travelTo(Carbon::parse('2026-01-05 10:00'));
        $this->suspendreFiletDeSecurite();

        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', [
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-01-03',
        ]));

        $response->assertOk();
        $ligne = $response->viewData('situationFinanciere')->firstWhere('gestionnaire.id', $gestionnaire->id);
        $this->assertEquals(30000, $ligne['attendu']);
        $this->assertEquals(25000, $ligne['deja_verse']);
        // Manque de 10 000 le 2 janvier, non compensé par l'excédent du 3.
        $this->assertEquals(10000, $ligne['reste_a_verser']);
        $this->assertFalse($ligne['a_jour']);
    }

    public function test_situation_financiere_intervalle_dun_seul_jour_egale_le_calcul_journalier(): void
    {
        $this->seed(StockReferenceSeeder::class);
        $this->travelTo(Carbon::parse('2026-01-01 08:00'));

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        Vehicule::factory()->create([
            'statut_id' => $statutCirculation->id,
            'gestionnaire_id' => $gestionnaire->id,
            'recette_journaliere' => 12000,
        ]);

        $this->travelTo(Carbon::parse('2026-01-05 10:00'));
        $this->suspendreFiletDeSecurite();

        $response = $this->actingAs($admin)->get(route('flotte.etat-parc.index', [
            'date_debut' => '2026-01-04', 'date_fin' => '2026-01-04',
        ]));

        $ligne = $response->viewData('situationFinanciere')->firstWhere('gestionnaire.id', $gestionnaire->id);
        $this->assertEquals(12000, $ligne['attendu']);
        $this->assertEquals(0, $ligne['deja_verse']);
        $this->assertEquals(12000, $ligne['reste_a_verser']);
    }
}
