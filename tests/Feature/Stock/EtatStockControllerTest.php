<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
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
