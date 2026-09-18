<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BonCommandeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gestionnaire_stock_can_view_and_create_bon_commande(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $this->actingAs($user)->get(route('stock.bons-commande.index'))->assertOk();

        $response = $this->actingAs($user)->postJson(route('stock.bons-commande.store'), [
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now()->format('Y-m-d'),
            'lignes' => [
                ['article_id' => $article->id, 'quantite_commandee' => 5, 'prix_unitaire_estime' => 1000],
            ],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('bons_commande', ['fournisseur_id' => $fournisseur->id, 'statut' => 'en_attente']);
    }

    public function test_chef_mecanicien_cannot_create_bon_commande(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $this->actingAs($user)->postJson(route('stock.bons-commande.store'), [
            'fournisseur_id' => $fournisseur->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 1, 'prix_unitaire_estime' => 100]],
        ])->assertForbidden();
    }

    public function test_annuler_bon_commande_via_http(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $create = $this->actingAs($user)->postJson(route('stock.bons-commande.store'), [
            'fournisseur_id' => $fournisseur->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 1, 'prix_unitaire_estime' => 100]],
        ]);

        $bonCommandeId = $create->json('bonCommande.id');

        $this->actingAs($user)->postJson(route('stock.bons-commande.annuler', $bonCommandeId))
            ->assertOk();

        $this->assertDatabaseHas('bons_commande', ['id' => $bonCommandeId, 'statut' => 'annule']);
    }

    public function test_supprimer_bon_commande_via_http(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $create = $this->actingAs($user)->postJson(route('stock.bons-commande.store'), [
            'fournisseur_id' => $fournisseur->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 1, 'prix_unitaire_estime' => 100]],
        ]);

        $bonCommandeId = $create->json('bonCommande.id');

        $this->actingAs($user)->deleteJson(route('stock.bons-commande.destroy', $bonCommandeId))
            ->assertOk();

        $this->assertSoftDeleted('bons_commande', ['id' => $bonCommandeId]);
    }

    public function test_pdf_endpoint_returns_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $create = $this->actingAs($user)->postJson(route('stock.bons-commande.store'), [
            'fournisseur_id' => $fournisseur->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 1, 'prix_unitaire_estime' => 100]],
        ]);

        $bonCommandeId = $create->json('bonCommande.id');

        $this->actingAs($user)->get(route('stock.bons-commande.pdf', $bonCommandeId))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_excel_endpoint_returns_single_row_export(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $create = $this->actingAs($user)->postJson(route('stock.bons-commande.store'), [
            'fournisseur_id' => $fournisseur->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 1, 'prix_unitaire_estime' => 100]],
        ]);

        $bonCommandeId = $create->json('bonCommande.id');

        $this->actingAs($user)->get(route('stock.bons-commande.export.excel.single', $bonCommandeId))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_gestionnaire_stock_can_export_bons_commande_list_excel_and_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $this->actingAs($user)->postJson(route('stock.bons-commande.store'), [
            'fournisseur_id' => $fournisseur->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 1, 'prix_unitaire_estime' => 100]],
        ]);

        $this->actingAs($user)->get(route('stock.bons-commande.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($user)->get(route('stock.bons-commande.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_bons_commande_export_can_be_filtered_by_fournisseur(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseurRecherche = Fournisseur::factory()->create();
        $autreFournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $this->actingAs($user)->postJson(route('stock.bons-commande.store'), [
            'fournisseur_id' => $fournisseurRecherche->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 1, 'prix_unitaire_estime' => 100]],
        ]);
        $this->actingAs($user)->postJson(route('stock.bons-commande.store'), [
            'fournisseur_id' => $autreFournisseur->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 1, 'prix_unitaire_estime' => 100]],
        ]);

        $this->actingAs($user)->get(route('stock.bons-commande.export.excel', ['fournisseur_id' => $fournisseurRecherche->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_chef_mecanicien_cannot_export_bons_commande(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->get(route('stock.bons-commande.export.excel'))->assertForbidden();
        $this->actingAs($user)->get(route('stock.bons-commande.export.pdf'))->assertForbidden();
    }
}
