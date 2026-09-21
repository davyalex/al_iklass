<?php

namespace Tests\Feature\Stock;

use App\Models\Achat;
use App\Models\Article;
use App\Models\CategorieArticle;
use App\Models\MouvementStock;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtatStockControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gestionnaire_stock_can_view_etat_stock_index(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($user)
            ->get(route('stock.etat-stock.index'))
            ->assertOk()
            ->assertViewIs('stock.etat-stock.index');
    }

    public function test_gestionnaire_without_permission_cannot_view_etat_stock_index(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($user)
            ->get(route('stock.etat-stock.index'))
            ->assertForbidden();
    }

    public function test_data_endpoint_returns_cout_moyen_achat_per_unit(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create(['nom' => 'Filtre à huile', 'quantite_stock' => 10, 'prix_achat' => 1500]);

        $response = $this->actingAs($user)->getJson(route('stock.etat-stock.data'));

        $response->assertOk();
        $response->assertJsonFragment(['prix_achat' => '1 500 FCFA']);
    }

    public function test_data_endpoint_flags_articles_below_threshold_as_en_alerte(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create(['nom' => 'Article en alerte', 'quantite_stock' => 1, 'seuil_alerte' => 5]);
        Article::factory()->create(['nom' => 'Article normal', 'quantite_stock' => 50, 'seuil_alerte' => 5]);

        $response = $this->actingAs($user)->getJson(route('stock.etat-stock.data'));

        $response->assertOk();
        $response->assertJsonFragment(['nom' => 'Article en alerte', 'en_alerte' => true]);
        $response->assertJsonFragment(['nom' => 'Article normal', 'en_alerte' => false]);
    }

    public function test_articles_en_alerte_are_listed_before_the_others(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        // Noms choisis pour que l'ordre alphabétique seul placerait l'article
        // normal avant celui en alerte : le tri par alerte doit primer.
        Article::factory()->create(['nom' => 'Zebre en alerte', 'quantite_stock' => 1, 'seuil_alerte' => 5]);
        Article::factory()->create(['nom' => 'Alpha normal', 'quantite_stock' => 50, 'seuil_alerte' => 5]);

        $response = $this->actingAs($user)->getJson(route('stock.etat-stock.data', ['order' => [['column' => 1, 'dir' => 'asc']], 'columns' => [
            ['data' => 'reference'], ['data' => 'nom'], ['data' => 'categorie_libelle'], ['data' => 'unite_libelle'],
            ['data' => 'quantite_stock'], ['data' => 'seuil_alerte'], ['data' => 'prix_achat'], ['data' => 'statut_badge'],
        ]]));

        $response->assertOk();
        $noms = array_column($response->json('data'), 'nom');
        $this->assertSame(['Zebre en alerte', 'Alpha normal'], $noms);
    }

    public function test_en_alerte_filter_only_shows_articles_below_threshold(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $enAlerte = Article::factory()->create(['nom' => 'Article en alerte', 'quantite_stock' => 1, 'seuil_alerte' => 5]);
        $normal = Article::factory()->create(['nom' => 'Article normal', 'quantite_stock' => 50, 'seuil_alerte' => 5]);

        $response = $this->actingAs($user)->getJson(route('stock.etat-stock.data', ['en_alerte' => 1]));

        $response->assertOk();
        $response->assertJsonFragment(['nom' => $enAlerte->nom]);
        $response->assertJsonMissing(['nom' => $normal->nom]);
    }

    public function test_article_id_filter_shows_only_the_selected_product(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $cible = Article::factory()->create(['nom' => 'Filtre à huile']);
        $autre = Article::factory()->create(['nom' => 'Bougie']);

        $response = $this->actingAs($user)->getJson(route('stock.etat-stock.data', ['article_id' => $cible->id]));

        $response->assertOk();
        $response->assertJsonFragment(['nom' => $cible->nom]);
        $response->assertJsonMissing(['nom' => $autre->nom]);
    }

    public function test_kpis_expose_total_pieces_disponibles(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create(['quantite_stock' => 10, 'actif' => true]);
        Article::factory()->create(['quantite_stock' => 5, 'actif' => true]);
        Article::factory()->create(['quantite_stock' => 100, 'actif' => false]);

        $response = $this->actingAs($user)->get(route('stock.etat-stock.index'));

        $response->assertOk();
        $response->assertViewHas('kpis', fn ($kpis) => $kpis['total_pieces'] === 15);
    }

    public function test_article_id_filter_shows_only_the_selected_product_categorie(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $categorieCible = CategorieArticle::create(['code' => 'CAT0001', 'libelle' => 'Moteur', 'actif' => true]);
        $cible = Article::factory()->create(['nom' => 'Filtre à huile', 'categorie_id' => $categorieCible->id]);
        $autre = Article::factory()->create(['nom' => 'Bougie']);

        $response = $this->actingAs($user)->getJson(route('stock.etat-stock.data', ['categorie_id' => $categorieCible->id]));

        $response->assertOk();
        $response->assertJsonFragment(['nom' => $cible->nom]);
        $response->assertJsonMissing(['nom' => $autre->nom]);
    }

    public function test_kpis_default_to_the_current_month_and_react_to_period_filter(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create(['prix_achat' => 1000]);

        // Achat ce mois-ci : compte dans le KPI par défaut.
        Achat::factory()->create(['date_achat' => now(), 'montant_total' => 5000]);
        // Achat hors mois courant : exclu par défaut.
        Achat::factory()->create(['date_achat' => now()->subMonths(2), 'montant_total' => 9000]);

        MouvementStock::create([
            'article_id' => $article->id, 'article_reference' => $article->reference, 'article_nom' => $article->nom,
            'type' => 'entree', 'quantite' => 10, 'prix_unitaire' => 1000, 'user_id' => $user->id, 'date_mouvement' => now(),
        ]);
        MouvementStock::create([
            'article_id' => $article->id, 'article_reference' => $article->reference, 'article_nom' => $article->nom,
            'type' => 'sortie', 'nature' => 'interne', 'quantite' => 3, 'prix_unitaire' => 1000, 'user_id' => $user->id, 'date_mouvement' => now(),
        ]);
        MouvementStock::create([
            'article_id' => $article->id, 'article_reference' => $article->reference, 'article_nom' => $article->nom,
            'type' => 'sortie', 'nature' => 'externe', 'quantite' => 2, 'prix_unitaire' => 1000, 'prix_vente' => 1500, 'user_id' => $user->id, 'date_mouvement' => now(),
        ]);

        $reponseParDefaut = $this->actingAs($user)->getJson(route('stock.etat-stock.kpis'));

        $reponseParDefaut->assertOk();
        $this->assertEquals(5000, $reponseParDefaut->json('achats_periode'));
        $this->assertEquals(3000, $reponseParDefaut->json('stock_utilise_interne'));
        $this->assertEquals(3000, $reponseParDefaut->json('montant_vendu_externe'));
        $this->assertSame(1, $reponseParDefaut->json('entrees_count'));
        $this->assertSame(1, $reponseParDefaut->json('sorties_interne_count'));
        $this->assertSame(1, $reponseParDefaut->json('sorties_externe_count'));

        // Filtre explicite couvrant les 3 derniers mois : l'achat hors mois courant doit apparaître aussi.
        $reponseFiltree = $this->actingAs($user)->getJson(route('stock.etat-stock.kpis', [
            'date_debut' => now()->subMonths(3)->toDateString(),
            'date_fin' => now()->toDateString(),
        ]));

        $reponseFiltree->assertOk();
        $this->assertEquals(14000, $reponseFiltree->json('achats_periode'));
    }

    public function test_kpis_dettes_fournisseurs_and_en_alerte_are_not_scoped_to_period(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Achat::factory()->create(['date_achat' => now()->subYear(), 'montant_total' => 8000, 'montant_paye' => 3000, 'montant_restant' => 5000]);
        Article::factory()->create(['quantite_stock' => 1, 'seuil_alerte' => 5, 'actif' => true]);

        $response = $this->actingAs($user)->getJson(route('stock.etat-stock.kpis', [
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->toDateString(),
        ]));

        $response->assertOk();
        $this->assertEquals(5000, $response->json('dettes_fournisseurs'));
        $this->assertSame(1, $response->json('en_alerte'));
    }

    public function test_detail_endpoint_returns_article_with_recent_mouvements(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create(['nom' => 'Filtre à huile']);
        MouvementStock::create([
            'article_id' => $article->id, 'article_reference' => $article->reference, 'article_nom' => $article->nom,
            'type' => 'entree', 'quantite' => 10, 'prix_unitaire' => 1000, 'user_id' => $user->id, 'date_mouvement' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('stock.etat-stock.detail', $article));

        $response->assertOk();
        $response->assertJsonPath('article.nom', 'Filtre à huile');
        $this->assertCount(1, $response->json('mouvements'));
    }

    public function test_export_excel_requires_view_permission(): void
    {
        $user = User::factory()->create();
        Article::factory()->create();

        $this->actingAs($user)->get(route('stock.etat-stock.export.excel'))->assertForbidden();
    }

    public function test_gestionnaire_stock_can_export_etat_stock_excel(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create();

        $this->actingAs($user)->get(route('stock.etat-stock.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_gestionnaire_stock_can_export_etat_stock_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create();

        $this->actingAs($user)->get(route('stock.etat-stock.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
