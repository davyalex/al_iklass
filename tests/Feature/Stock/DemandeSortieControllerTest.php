<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\DemandeSortie;
use App\Models\User;
use App\Models\Vehicule;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandeSortieControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_chef_mecanicien_peut_afficher_la_page_demandes(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        Vehicule::factory()->create();
        Article::factory()->create(['actif' => true]);

        $this->actingAs($mecanicien)->get(route('stock.demandes.index'))
            ->assertOk()
            ->assertSee('Nouvelle demande')
            ->assertDontSee('Valider (créer la sortie)');
    }

    public function test_gestionnaire_stock_peut_afficher_la_page_demandes_avec_actions_de_traitement(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($gestionnaireStock)->get(route('stock.demandes.index'))
            ->assertOk()
            ->assertSee('Valider (créer la sortie)')
            ->assertDontSee('Nouvelle demande');
    }

    public function test_chef_mecanicien_can_create_demande(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $article1 = Article::factory()->create(['quantite_stock' => 10]);
        $article2 = Article::factory()->create(['quantite_stock' => 10]);
        $vehicule = Vehicule::factory()->create();

        $response = $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'motif' => 'Freins',
            'lignes' => [
                ['article_id' => $article1->id, 'quantite' => 2],
                ['article_id' => $article2->id, 'quantite' => 1],
            ],
        ]);

        $response->assertCreated();

        $demande = DemandeSortie::first();
        $this->assertSame('en_attente', $demande->statut);
        $this->assertSame($mecanicien->id, $demande->demandeur_id);
        $this->assertSame(2, $demande->lignes()->count());

        // Aucun stock décrémenté à la simple création de la demande.
        $this->assertSame(10, $article1->fresh()->quantite_stock);
    }

    public function test_gestionnaire_parc_cannot_create_demande(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $vehicule = Vehicule::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $this->actingAs($gestionnaire)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ])->assertForbidden();
    }

    public function test_chef_mecanicien_voit_uniquement_ses_propres_demandes(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $autreMecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $vehicule = Vehicule::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ])->assertCreated();

        $this->actingAs($autreMecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ])->assertCreated();

        $response = $this->actingAs($mecanicien)->getJson(route('stock.demandes.data'));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_gestionnaire_stock_voit_toutes_les_demandes(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $vehicule = Vehicule::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ])->assertCreated();

        $response = $this->actingAs($gestionnaireStock)->getJson(route('stock.demandes.data'));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_chef_mecanicien_ne_peut_pas_voir_la_demande_dun_autre(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $autreMecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $vehicule = Vehicule::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ])->assertCreated();

        $demande = DemandeSortie::first();

        $this->actingAs($autreMecanicien)->getJson(route('stock.demandes.show', $demande))->assertForbidden();
    }

    public function test_gestionnaire_stock_can_valider_une_demande_et_cree_la_sortie(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $vehicule = Vehicule::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10, 'prix_achat' => 1000]);

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'motif' => 'Réparation freins',
            'lignes' => [['article_id' => $article->id, 'quantite' => 3]],
        ])->assertCreated();

        $demande = DemandeSortie::first();

        $response = $this->actingAs($gestionnaireStock)->postJson(route('stock.demandes.valider', $demande));

        $response->assertOk();

        $demande->refresh();
        $this->assertSame('validee', $demande->statut);
        $this->assertSame($gestionnaireStock->id, $demande->traite_par_id);
        $this->assertNotNull($demande->sortie_id);
        $this->assertSame(7, $article->fresh()->quantite_stock);
        $this->assertSame($vehicule->id, $demande->sortie->vehicule_id);
    }

    public function test_valider_echoue_avec_422_si_stock_insuffisant_et_ne_change_pas_le_statut(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $vehicule = Vehicule::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 1]);

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 5]],
        ])->assertCreated();

        $demande = DemandeSortie::first();

        $response = $this->actingAs($gestionnaireStock)->postJson(route('stock.demandes.valider', $demande));

        $response->assertStatus(422)->assertJson(['insufficient_stock' => true]);
        $this->assertSame('en_attente', $demande->fresh()->statut);
        $this->assertSame(1, $article->fresh()->quantite_stock);
    }

    public function test_gestionnaire_stock_can_rejeter_une_demande_avec_motif(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $vehicule = Vehicule::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ])->assertCreated();

        $demande = DemandeSortie::first();

        $response = $this->actingAs($gestionnaireStock)->postJson(route('stock.demandes.rejeter', $demande), [
            'motif' => 'Article indisponible',
        ]);

        $response->assertOk();

        $demande->refresh();
        $this->assertSame('rejetee', $demande->statut);
        $this->assertSame('Article indisponible', $demande->commentaire_traitement);
        $this->assertSame(10, $article->fresh()->quantite_stock);
    }

    public function test_rejeter_sans_motif_est_rejete_par_la_validation(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $vehicule = Vehicule::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ])->assertCreated();

        $demande = DemandeSortie::first();

        $this->actingAs($gestionnaireStock)->postJson(route('stock.demandes.rejeter', $demande), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('motif');
    }

    public function test_chef_mecanicien_cannot_valider_ou_rejeter(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $vehicule = Vehicule::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ])->assertCreated();

        $demande = DemandeSortie::first();

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.valider', $demande))->assertForbidden();
        $this->actingAs($mecanicien)->postJson(route('stock.demandes.rejeter', $demande), ['motif' => 'x'])->assertForbidden();
    }

    public function test_une_demande_deja_traitee_ne_peut_pas_etre_retraitee(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $vehicule = Vehicule::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ])->assertCreated();

        $demande = DemandeSortie::first();

        $this->actingAs($gestionnaireStock)->postJson(route('stock.demandes.valider', $demande))->assertOk();

        $this->actingAs($gestionnaireStock)->postJson(route('stock.demandes.rejeter', $demande), ['motif' => 'trop tard'])
            ->assertStatus(422);
    }

    public function test_kpis_comptent_en_attente_validees_et_rejetees(): void
    {
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $vehicule = Vehicule::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ])->assertCreated();
        $demandeAValider = DemandeSortie::first();

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ])->assertCreated();
        $demandeARejeter = DemandeSortie::latest('id')->first();

        $this->actingAs($mecanicien)->postJson(route('stock.demandes.store'), [
            'vehicule_id' => $vehicule->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ])->assertCreated();

        $this->actingAs($gestionnaireStock)->postJson(route('stock.demandes.valider', $demandeAValider))->assertOk();
        $this->actingAs($gestionnaireStock)->postJson(route('stock.demandes.rejeter', $demandeARejeter), ['motif' => 'x'])->assertOk();

        $response = $this->actingAs($gestionnaireStock)->getJson(route('stock.demandes.kpis'));

        $response->assertOk();
        $this->assertSame(1, $response->json('en_attente'));
        $this->assertSame(1, $response->json('validees_mois'));
        $this->assertSame(1, $response->json('rejetees_mois'));
    }
}
