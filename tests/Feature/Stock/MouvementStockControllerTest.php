<?php

namespace Tests\Feature\Stock;

use App\Models\Achat;
use App\Models\Article;
use App\Models\MouvementStock;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MouvementStockControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function creerMouvement(Article $article, User $user, array $overrides = []): MouvementStock
    {
        return MouvementStock::create(array_merge([
            'article_id' => $article->id,
            'article_reference' => $article->reference,
            'article_nom' => $article->nom,
            'type' => 'entree',
            'quantite' => 5,
            'prix_unitaire' => 1000,
            'user_id' => $user->id,
            'date_mouvement' => now(),
        ], $overrides));
    }

    public function test_gestionnaire_stock_can_view_mouvements_index(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($user)
            ->get(route('stock.mouvements.index'))
            ->assertOk()
            ->assertViewIs('stock.mouvements.index');
    }

    public function test_gestionnaire_without_permission_cannot_view_mouvements_index(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($user)
            ->get(route('stock.mouvements.index'))
            ->assertForbidden();
    }

    public function test_index_exposes_the_preselected_article_from_query_string(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create(['nom' => 'Filtre à huile']);

        $response = $this->actingAs($user)->get(route('stock.mouvements.index', ['article_id' => $article->id]));

        $response->assertOk();
        $response->assertViewHas('articlePreselectionne', fn ($a) => $a->id === $article->id);
    }

    public function test_data_endpoint_lists_both_entrees_and_sorties(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create();
        $this->creerMouvement($article, $user, ['type' => 'entree']);
        $this->creerMouvement($article, $user, ['type' => 'sortie', 'nature' => 'interne']);

        $response = $this->actingAs($user)->getJson(route('stock.mouvements.data'));

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_article_id_filter_shows_only_movements_for_that_article(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $cible = Article::factory()->create(['nom' => 'Filtre à huile']);
        $autre = Article::factory()->create(['nom' => 'Bougie']);
        $this->creerMouvement($cible, $user);
        $this->creerMouvement($autre, $user);

        $response = $this->actingAs($user)->getJson(route('stock.mouvements.data', ['article_id' => $cible->id]));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonFragment(['article_libelle' => $cible->reference.' — '.$cible->nom]);
    }

    public function test_type_filter_shows_only_matching_movements(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create();
        $this->creerMouvement($article, $user, ['type' => 'entree']);
        $this->creerMouvement($article, $user, ['type' => 'sortie', 'nature' => 'externe']);

        $response = $this->actingAs($user)->getJson(route('stock.mouvements.data', ['type' => 'sortie']));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_origine_reflects_the_linked_achat(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create();
        $achat = Achat::factory()->create(['reference' => 'ACH-2026-0099']);
        $this->creerMouvement($article, $user, ['achat_id' => $achat->id]);

        $response = $this->actingAs($user)->getJson(route('stock.mouvements.data'));

        $response->assertOk();
        $response->assertJsonFragment(['origine' => 'Achat ACH-2026-0099']);
    }

    public function test_origine_reflects_the_internal_vehicle_for_sortie_interne(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create();
        $this->creerMouvement($article, $user, [
            'type' => 'sortie', 'nature' => 'interne', 'vehicule_code' => 'AL-001', 'motif' => 'Vidange',
        ]);

        $response = $this->actingAs($user)->getJson(route('stock.mouvements.data'));

        $response->assertOk();
        $response->assertJsonFragment(['origine' => 'Véhicule AL-001 — Vidange']);
    }

    public function test_export_excel_requires_view_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('stock.mouvements.export.excel'))->assertForbidden();
    }

    public function test_gestionnaire_stock_can_export_mouvements_excel(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create();
        $this->creerMouvement($article, $user);

        $this->actingAs($user)->get(route('stock.mouvements.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_gestionnaire_stock_can_export_mouvements_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create();
        $this->creerMouvement($article, $user);

        $this->actingAs($user)->get(route('stock.mouvements.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
