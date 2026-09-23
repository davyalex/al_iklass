<?php

namespace Tests\Feature\Flotte;

use App\Models\HistoriqueDette;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StockReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(StockReferenceSeeder::class);
    }

    public function test_admin_peut_afficher_la_page(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        User::factory()->create(['name' => 'Awa Koné', 'dette' => 5000])->assignRole('gestionnaire');

        $this->actingAs($admin)->get(route('flotte.dettes.index'))
            ->assertOk()
            ->assertSee('Awa Koné')
            ->assertSee('Régler')
            ->assertSee('Annuler');
    }

    public function test_gestionnaire_peut_afficher_la_page_avec_sa_propre_dette(): void
    {
        $gestionnaire = User::factory()->create(['name' => 'Awa Koné', 'dette' => 5000])->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->get(route('flotte.dettes.index'))
            ->assertOk()
            ->assertSee('Awa Koné')
            ->assertSee('Régler');
    }

    public function test_role_sans_droit_dette_est_rejete(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($gestionnaireStock)->get(route('flotte.dettes.index'))->assertForbidden();
    }

    public function test_admin_voit_lhistorique_de_tous_les_gestionnaires(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaireA = User::factory()->create()->assignRole('gestionnaire');
        $gestionnaireB = User::factory()->create()->assignRole('gestionnaire');

        HistoriqueDette::create(['gestionnaire_id' => $gestionnaireA->id, 'gestionnaire_nom' => $gestionnaireA->name, 'type' => 'bascule', 'montant' => 1000, 'dette_avant' => 0, 'dette_apres' => 1000]);
        HistoriqueDette::create(['gestionnaire_id' => $gestionnaireB->id, 'gestionnaire_nom' => $gestionnaireB->name, 'type' => 'bascule', 'montant' => 2000, 'dette_avant' => 0, 'dette_apres' => 2000]);

        $response = $this->actingAs($admin)->getJson(route('flotte.dettes.data'));

        $response->assertOk();
        $this->assertSame(2, $response->json('recordsFiltered'));
    }

    public function test_gestionnaire_ne_voit_que_son_propre_historique(): void
    {
        $gestionnaireA = User::factory()->create()->assignRole('gestionnaire');
        $gestionnaireB = User::factory()->create()->assignRole('gestionnaire');

        HistoriqueDette::create(['gestionnaire_id' => $gestionnaireA->id, 'gestionnaire_nom' => $gestionnaireA->name, 'type' => 'bascule', 'montant' => 1000, 'dette_avant' => 0, 'dette_apres' => 1000]);
        HistoriqueDette::create(['gestionnaire_id' => $gestionnaireB->id, 'gestionnaire_nom' => $gestionnaireB->name, 'type' => 'bascule', 'montant' => 2000, 'dette_avant' => 0, 'dette_apres' => 2000]);

        $response = $this->actingAs($gestionnaireA)->getJson(route('flotte.dettes.data'));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_gestionnaire_peut_regler_partiellement_sa_propre_dette(): void
    {
        $gestionnaire = User::factory()->create(['dette' => 5000])->assignRole('gestionnaire');

        $response = $this->actingAs($gestionnaire)->postJson(route('flotte.gestionnaires.dette.regler', $gestionnaire), [
            'montant' => 2000,
        ]);

        $response->assertOk();
        $response->assertJsonPath('solde_du_brut', 3000);
        $this->assertEquals(3000, (float) $gestionnaire->fresh()->dette);

        $this->assertDatabaseHas('historique_dettes', [
            'gestionnaire_id' => $gestionnaire->id,
            'type' => 'reglement',
            'montant' => 2000,
            'dette_avant' => 5000,
            'dette_apres' => 3000,
            'user_id' => $gestionnaire->id,
        ]);

        $this->assertDatabaseHas('mouvements_caisse', [
            'sens' => 'entree',
            'montant' => 2000,
            'origine_type' => HistoriqueDette::class,
            'user_id' => $gestionnaire->id,
        ]);
    }

    public function test_gestionnaire_ne_peut_pas_regler_la_dette_dun_autre(): void
    {
        $gestionnaireA = User::factory()->create()->assignRole('gestionnaire');
        $gestionnaireB = User::factory()->create(['dette' => 5000])->assignRole('gestionnaire');

        $this->actingAs($gestionnaireA)->postJson(route('flotte.gestionnaires.dette.regler', $gestionnaireB), [
            'montant' => 1000,
        ])->assertForbidden();

        $this->assertEquals(5000, (float) $gestionnaireB->fresh()->dette);
    }

    public function test_admin_peut_regler_au_nom_dun_gestionnaire(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 5000])->assignRole('gestionnaire');

        $response = $this->actingAs($admin)->postJson(route('flotte.gestionnaires.dette.regler', $gestionnaire), [
            'montant' => 5000,
            'motif' => 'Payé en espèces au bureau',
        ]);

        $response->assertOk();
        $this->assertEquals(0, (float) $gestionnaire->fresh()->dette);
        $this->assertDatabaseHas('historique_dettes', [
            'gestionnaire_id' => $gestionnaire->id,
            'type' => 'reglement',
            'motif' => 'Payé en espèces au bureau',
            'user_id' => $admin->id,
        ]);
    }

    public function test_reglement_superieur_a_la_dette_actuelle_est_rejete(): void
    {
        $gestionnaire = User::factory()->create(['dette' => 1000])->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->postJson(route('flotte.gestionnaires.dette.regler', $gestionnaire), [
            'montant' => 2000,
        ])->assertStatus(422);

        $this->assertEquals(1000, (float) $gestionnaire->fresh()->dette);
    }

    public function test_kpis_comptent_les_gestionnaires_en_dette_et_le_total_du(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        User::factory()->create(['dette' => 3000])->assignRole('gestionnaire');
        User::factory()->create(['dette' => 7000])->assignRole('gestionnaire');
        User::factory()->create(['dette' => 0])->assignRole('gestionnaire');

        $response = $this->actingAs($admin)->getJson(route('flotte.dettes.kpis'));

        $response->assertOk();
        $this->assertSame(2, $response->json('gestionnaires_en_dette'));
        $this->assertEquals(10000, $response->json('total_du'));
    }

    public function test_kpis_dun_gestionnaire_ne_reflete_que_sa_propre_dette(): void
    {
        $gestionnaire = User::factory()->create(['dette' => 3000])->assignRole('gestionnaire');
        User::factory()->create(['dette' => 7000])->assignRole('gestionnaire');

        $response = $this->actingAs($gestionnaire)->getJson(route('flotte.dettes.kpis'));

        $response->assertOk();
        $this->assertSame(1, $response->json('gestionnaires_en_dette'));
        $this->assertEquals(3000, $response->json('total_du'));
    }

    public function test_export_excel_et_pdf(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        HistoriqueDette::create(['gestionnaire_id' => $gestionnaire->id, 'gestionnaire_nom' => $gestionnaire->name, 'type' => 'bascule', 'montant' => 1000, 'dette_avant' => 0, 'dette_apres' => 1000]);

        $this->actingAs($admin)->get(route('flotte.dettes.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($admin)->get(route('flotte.dettes.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
