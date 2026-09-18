<?php

namespace Tests\Feature\Stock;

use App\Models\Achat;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\User;
use App\Services\Stock\BonCommandeService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchatControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gestionnaire_stock_can_view_achats_index_and_data(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($user)->get(route('stock.achats.index'))->assertOk();
        $this->actingAs($user)->getJson(route('stock.achats.data'))
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    }

    public function test_gestionnaire_stock_can_create_achat_via_controller(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 0]);

        $response = $this->actingAs($user)->postJson(route('stock.achats.store'), [
            'fournisseur_id' => $fournisseur->id,
            'date_achat' => now()->format('Y-m-d'),
            'montant_paye' => 1000,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 2, 'prix_unitaire' => 500],
            ],
        ]);

        $response->assertCreated();
        $this->assertSame(2, $article->fresh()->quantite_stock);
        $this->assertDatabaseHas('achats', ['fournisseur_id' => $fournisseur->id, 'montant_total' => 1000, 'statut_paiement' => 'comptant']);
    }

    public function test_chef_mecanicien_cannot_create_achat(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $this->actingAs($user)->postJson(route('stock.achats.store'), [
            'fournisseur_id' => $fournisseur->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1, 'prix_unitaire' => 100]],
        ])->assertForbidden();
    }

    public function test_store_rejects_empty_lignes(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();

        $this->actingAs($user)->postJson(route('stock.achats.store'), [
            'fournisseur_id' => $fournisseur->id,
            'lignes' => [],
        ])->assertUnprocessable();
    }

    public function test_data_can_be_filtered_by_fournisseur_and_statut(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseurRecherche = Fournisseur::factory()->create();

        Achat::factory()->create([
            'fournisseur_id' => $fournisseurRecherche->id,
            'statut_paiement' => 'comptant',
        ]);
        Achat::factory()->create([
            'statut_paiement' => 'credit',
        ]);

        $response = $this->actingAs($user)->getJson(route('stock.achats.data', [
            'fournisseur_id' => $fournisseurRecherche->id,
            'statut_paiement' => 'comptant',
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_data_can_be_filtered_by_date_range(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        Achat::factory()->create(['date_achat' => now()->subDays(10)]);
        Achat::factory()->create(['date_achat' => now()]);

        $response = $this->actingAs($user)->getJson(route('stock.achats.data', [
            'date_debut' => now()->subDay()->format('Y-m-d'),
            'date_fin' => now()->addDay()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_gestionnaire_stock_can_export_achats_excel_and_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Achat::factory()->create();

        $this->actingAs($user)->get(route('stock.achats.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($user)->get(route('stock.achats.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_chef_mecanicien_cannot_export_achats(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->get(route('stock.achats.export.excel'))->assertForbidden();
        $this->actingAs($user)->get(route('stock.achats.export.pdf'))->assertForbidden();
    }

    public function test_pdf_detail_endpoint_returns_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $achat = Achat::factory()->create();

        $this->actingAs($user)->get(route('stock.achats.pdf', $achat))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_detail_download_param_forces_attachment(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $achat = Achat::factory()->create();

        $response = $this->actingAs($user)->get(route('stock.achats.pdf', $achat).'?download=1');

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    public function test_excel_detail_endpoint_returns_single_row_export(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $achat = Achat::factory()->create();

        $this->actingAs($user)->get(route('stock.achats.export.excel.single', $achat))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_kpis_are_computed_for_the_index_view(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        Achat::factory()->create([
            'date_achat' => now(),
            'montant_total' => 1000,
            'montant_paye' => 400,
            'montant_restant' => 600,
        ]);
        Achat::factory()->create([
            'date_achat' => now()->subMonth(),
            'montant_total' => 5000,
            'montant_paye' => 0,
            'montant_restant' => 5000,
        ]);

        $response = $this->actingAs($user)->get(route('stock.achats.index'));

        $response->assertOk();
        $kpis = $response->viewData('kpis');
        $this->assertEquals(1000, $kpis['jour']);
        $this->assertEquals(1000, $kpis['mois']);
        $this->assertEquals(400, $kpis['paye_mois']);
        $this->assertEquals(600, $kpis['restant_mois']);
    }

    public function test_reception_liee_a_un_bon_de_commande_rejette_un_article_non_commande(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();
        $articleCommande = Article::factory()->create();
        $articleNonCommande = Article::factory()->create(['quantite_stock' => 0]);
        $bonCommande = app(BonCommandeService::class)->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [['article_id' => $articleCommande->id, 'quantite_commandee' => 5, 'prix_unitaire_estime' => 200]],
        ]);

        $response = $this->actingAs($user)->postJson(route('stock.achats.store'), [
            'fournisseur_id' => $fournisseur->id,
            'bon_commande_id' => $bonCommande->id,
            'date_achat' => now()->format('Y-m-d'),
            'lignes' => [
                ['article_id' => $articleNonCommande->id, 'quantite' => 1, 'prix_unitaire' => 200],
            ],
        ]);

        $response->assertUnprocessable();
        $this->assertSame(0, $articleNonCommande->fresh()->quantite_stock);
    }

    public function test_reception_liee_a_un_bon_de_commande_rejette_une_quantite_superieure_au_restant(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 0]);
        $bonCommande = app(BonCommandeService::class)->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 5, 'prix_unitaire_estime' => 200]],
        ]);
        $ligneId = $bonCommande->lignes->first()->id;

        $response = $this->actingAs($user)->postJson(route('stock.achats.store'), [
            'fournisseur_id' => $fournisseur->id,
            'bon_commande_id' => $bonCommande->id,
            'date_achat' => now()->format('Y-m-d'),
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 6, 'prix_unitaire' => 200, 'bon_commande_ligne_id' => $ligneId],
            ],
        ]);

        $response->assertUnprocessable();
        $this->assertSame(0, $article->fresh()->quantite_stock);
    }

    public function test_reception_liee_a_un_bon_de_commande_accepte_une_reception_partielle_valide(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 0]);
        $bonCommande = app(BonCommandeService::class)->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 5, 'prix_unitaire_estime' => 200]],
        ]);
        $ligneId = $bonCommande->lignes->first()->id;

        $response = $this->actingAs($user)->postJson(route('stock.achats.store'), [
            'fournisseur_id' => $fournisseur->id,
            'bon_commande_id' => $bonCommande->id,
            'date_achat' => now()->format('Y-m-d'),
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 3, 'prix_unitaire' => 200, 'bon_commande_ligne_id' => $ligneId],
            ],
        ]);

        $response->assertCreated();
        $this->assertSame(3, $article->fresh()->quantite_stock);
        $this->assertSame(3, $bonCommande->fresh()->lignes->first()->quantite_recue);
    }

    public function test_reception_valide_fonctionne_quand_les_identifiants_arrivent_en_chaines(): void
    {
        // Le vrai formulaire HTML de réception envoie ses champs en
        // application/x-www-form-urlencoded : toutes les valeurs, y compris les
        // identifiants numériques, arrivent donc comme des chaînes — contrairement à
        // postJson() avec des entiers PHP littéraux, qui masquait un bug de comparaison
        // stricte (!==) entre bon_commande_id de l'achat et celui de la ligne.
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 0]);
        $bonCommande = app(BonCommandeService::class)->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 5, 'prix_unitaire_estime' => 200]],
        ]);
        $ligneId = $bonCommande->lignes->first()->id;

        $response = $this->actingAs($user)->postJson(route('stock.achats.store'), [
            'fournisseur_id' => (string) $fournisseur->id,
            'bon_commande_id' => (string) $bonCommande->id,
            'date_achat' => now()->format('Y-m-d'),
            'lignes' => [
                ['article_id' => (string) $article->id, 'quantite' => '3', 'prix_unitaire' => '200', 'bon_commande_ligne_id' => (string) $ligneId],
            ],
        ]);

        $response->assertCreated();
        $this->assertSame(3, $article->fresh()->quantite_stock);
        $this->assertSame(3, $bonCommande->fresh()->lignes->first()->quantite_recue);
    }
}
