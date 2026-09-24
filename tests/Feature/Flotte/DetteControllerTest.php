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

    public function test_gestionnaire_voit_directement_le_tableau_plutot_quune_carte(): void
    {
        $gestionnaire = User::factory()->create(['dette' => 5000])->assignRole('gestionnaire');

        HistoriqueDette::create([
            'gestionnaire_id' => $gestionnaire->id, 'gestionnaire_nom' => $gestionnaire->name,
            'type' => 'bascule', 'montant' => 5000, 'dette_avant' => 0, 'dette_apres' => 5000,
            'date_reference' => now()->subDay()->toDateString(), 'attendu' => 5000, 'deja_verse' => 0,
        ]);

        $response = $this->actingAs($gestionnaire)->get(route('flotte.dettes.index'));

        $response->assertOk()
            ->assertSee('Jours ayant généré de la dette')
            ->assertSee('Montant à verser')
            ->assertSee('Régler');
    }

    public function test_admin_voit_des_cartes_avec_bouton_detail_plutot_que_le_tableau(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        User::factory()->create(['dette' => 5000])->assignRole('gestionnaire');

        $this->actingAs($admin)->get(route('flotte.dettes.index'))
            ->assertOk()
            ->assertSeeText('Détail')
            ->assertSeeText('Solde dû');
    }

    public function test_role_sans_droit_dette_est_rejete(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($gestionnaireStock)->get(route('flotte.dettes.index'))->assertForbidden();
    }

    public function test_detail_expose_les_jours_avec_attendu_deja_verse_et_reste(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 70000])->assignRole('gestionnaire');

        HistoriqueDette::create([
            'gestionnaire_id' => $gestionnaire->id,
            'gestionnaire_nom' => $gestionnaire->name,
            'type' => 'bascule',
            'montant' => 70000,
            'dette_avant' => 0,
            'dette_apres' => 70000,
            'date_reference' => now()->subDay()->toDateString(),
            'attendu' => 120000,
            'deja_verse' => 50000,
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.gestionnaires.dette.detail', $gestionnaire));

        $response->assertOk();
        $this->assertCount(1, $response->json('jours'));
        $this->assertSame('120 000', $response->json('jours.0.attendu'));
        $this->assertSame('50 000', $response->json('jours.0.deja_verse'));
        $this->assertSame('70 000', $response->json('jours.0.reste'));
    }

    public function test_detail_expose_les_reglements_et_annulations(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 1000])->assignRole('gestionnaire');

        HistoriqueDette::create([
            'gestionnaire_id' => $gestionnaire->id, 'gestionnaire_nom' => $gestionnaire->name,
            'type' => 'reglement', 'montant' => 2000, 'dette_avant' => 3000, 'dette_apres' => 1000,
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.gestionnaires.dette.detail', $gestionnaire));

        $response->assertOk();
        $this->assertCount(1, $response->json('mouvements'));
        $this->assertSame('reglement', $response->json('mouvements.0.type'));
    }

    public function test_gestionnaire_ne_peut_pas_voir_le_detail_dun_autre(): void
    {
        $gestionnaireA = User::factory()->create()->assignRole('gestionnaire');
        $gestionnaireB = User::factory()->create(['dette' => 1000])->assignRole('gestionnaire');

        $this->actingAs($gestionnaireA)->getJson(route('flotte.gestionnaires.dette.detail', $gestionnaireB))
            ->assertForbidden();
    }

    public function test_admin_peut_voir_le_detail_de_nimporte_qui(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['dette' => 1000])->assignRole('gestionnaire');

        $this->actingAs($admin)->getJson(route('flotte.gestionnaires.dette.detail', $gestionnaire))->assertOk();
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

    public function test_reglement_ecrit_une_entree_dans_le_journal_audit(): void
    {
        $gestionnaire = User::factory()->create(['dette' => 5000, 'name' => 'Awa Coulibaly'])->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->postJson(route('flotte.gestionnaires.dette.regler', $gestionnaire), [
            'montant' => 2000,
        ])->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => User::class,
            'subject_id' => $gestionnaire->id,
            'causer_id' => $gestionnaire->id,
            'description' => 'Dette de « Awa Coulibaly » réglée pour 2000 FCFA.',
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
}
