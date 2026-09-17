<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\Caisse;
use App\Models\User;
use App\Models\Vehicule;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SortieStockControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        Caisse::firstOrCreate(['type' => 'ventes_externes'], ['type' => 'ventes_externes', 'libelle' => 'Ventes externes']);
    }

    public function test_gestionnaire_stock_can_record_sortie_interne(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create(['quantite_stock' => 10]);
        $vehicule = Vehicule::factory()->create();

        $response = $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'interne',
            'article_id' => $article->id,
            'quantite' => 3,
            'vehicule_id' => $vehicule->id,
            'motif' => 'Entretien',
        ]);

        $response->assertCreated();
        $this->assertSame(7, $article->fresh()->quantite_stock);
    }

    public function test_gestionnaire_stock_can_record_vente_externe(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $response = $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'externe',
            'article_id' => $article->id,
            'quantite' => 2,
            'prix_vente' => 3000,
            'vehicule_externe' => 'CI-0001-AA',
            'acheteur' => 'Client Test',
            'motif' => 'Vente',
        ]);

        $response->assertCreated();
        $this->assertSame(8, $article->fresh()->quantite_stock);
    }

    public function test_insufficient_stock_returns_422_with_flag(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create(['quantite_stock' => 1]);
        $vehicule = Vehicule::factory()->create();

        $response = $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'interne',
            'article_id' => $article->id,
            'quantite' => 5,
            'vehicule_id' => $vehicule->id,
            'motif' => 'Test',
        ]);

        $response->assertStatus(422)->assertJson(['insufficient_stock' => true]);
        $this->assertSame(1, $article->fresh()->quantite_stock);
    }

    public function test_chef_mecanicien_cannot_record_sortie(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');
        $article = Article::factory()->create(['quantite_stock' => 10]);
        $vehicule = Vehicule::factory()->create();

        $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'interne',
            'article_id' => $article->id,
            'quantite' => 1,
            'vehicule_id' => $vehicule->id,
            'motif' => 'Test',
        ])->assertForbidden();
    }
}
