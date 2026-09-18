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

    public function test_data_endpoint_returns_valeur_stock_computed_from_quantite_and_prix_achat(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create(['nom' => 'Filtre à huile', 'quantite_stock' => 10, 'prix_achat' => 1500]);

        $response = $this->actingAs($user)->getJson(route('stock.etat-stock.data'));

        $response->assertOk();
        $response->assertJsonFragment(['valeur_stock' => '15 000 FCFA']);
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
