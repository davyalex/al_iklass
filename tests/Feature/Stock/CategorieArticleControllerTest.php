<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\CategorieArticle;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorieArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gestionnaire_stock_can_create_categorie(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $response = $this->actingAs($user)->postJson(route('stock.categories-article.store'), [
            'libelle' => 'Moteur',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories_article', ['code' => 'CAT0001', 'libelle' => 'Moteur']);
    }

    public function test_chef_mecanicien_cannot_create_categorie(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->postJson(route('stock.categories-article.store'), [
            'libelle' => 'Moteur',
        ])->assertForbidden();
    }

    public function test_store_generates_sequential_code(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        CategorieArticle::create(['code' => 'CAT0001', 'libelle' => 'Moteur', 'actif' => true]);

        $response = $this->actingAs($user)->postJson(route('stock.categories-article.store'), [
            'libelle' => 'Freinage',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories_article', ['code' => 'CAT0002', 'libelle' => 'Freinage']);
    }

    public function test_gestionnaire_stock_can_update_categorie(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $categorie = CategorieArticle::create(['code' => 'CAT0001', 'libelle' => 'Moteur', 'actif' => true]);

        $this->actingAs($user)->putJson(route('stock.categories-article.update', $categorie), [
            'libelle' => 'Moteur renommé',
        ])->assertOk();

        $this->assertDatabaseHas('categories_article', ['id' => $categorie->id, 'code' => 'CAT0001', 'libelle' => 'Moteur renommé']);
    }

    public function test_destroy_soft_deletes_categorie_and_keeps_article_link(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $categorie = CategorieArticle::create(['code' => 'CAT0001', 'libelle' => 'Moteur', 'actif' => true]);
        $article = Article::factory()->create(['categorie_id' => $categorie->id]);

        $this->actingAs($user)->deleteJson(route('stock.categories-article.destroy', $categorie))
            ->assertOk();

        $this->assertSoftDeleted($categorie);
        $this->assertSame($categorie->id, $article->fresh()->categorie_id);
    }
}
