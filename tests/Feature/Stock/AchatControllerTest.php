<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\User;
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
}
