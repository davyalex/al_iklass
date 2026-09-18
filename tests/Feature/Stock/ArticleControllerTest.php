<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\CategorieArticle;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gestionnaire_stock_can_view_articles_index(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($user)
            ->get(route('stock.articles.index'))
            ->assertOk()
            ->assertViewIs('stock.articles.index');
    }

    public function test_gestionnaire_without_permission_cannot_view_articles_index(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($user)
            ->get(route('stock.articles.index'))
            ->assertForbidden();
    }

    public function test_gestionnaire_stock_can_create_article(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $categorie = CategorieArticle::create(['code' => 'moteur', 'libelle' => 'Moteur', 'actif' => true]);

        $response = $this->actingAs($user)->postJson(route('stock.articles.store'), [
            'reference' => 'ART-100',
            'nom' => 'Filtre à huile',
            'categorie_id' => $categorie->id,
            'seuil_alerte' => 3,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('articles', ['reference' => 'ART-100', 'nom' => 'Filtre à huile']);
    }

    public function test_reference_is_generated_automatically_when_omitted(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $response = $this->actingAs($user)->postJson(route('stock.articles.store'), [
            'nom' => 'Article sans référence',
            'seuil_alerte' => 1,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('articles', ['nom' => 'Article sans référence', 'reference' => 'ART0001']);
    }

    public function test_reference_is_generated_when_submitted_as_empty_string(): void
    {
        // Le formulaire HTML envoie toujours le champ "reference", vide si l'utilisateur
        // ne l'a pas rempli (contrairement à une simple omission de la clé en JSON) :
        // ce cas doit lui aussi déclencher la génération automatique.
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $response = $this->actingAs($user)->postJson(route('stock.articles.store'), [
            'reference' => '',
            'nom' => 'Article référence vide',
            'seuil_alerte' => 1,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('articles', ['nom' => 'Article référence vide', 'reference' => 'ART0001']);
    }

    public function test_chef_mecanicien_cannot_create_article(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->postJson(route('stock.articles.store'), [
            'reference' => 'ART-200',
            'nom' => 'Test',
            'seuil_alerte' => 1,
        ])->assertForbidden();
    }

    public function test_update_rejects_duplicate_reference(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create(['reference' => 'ART-DUP']);
        $article = Article::factory()->create(['reference' => 'ART-ORIG']);

        $this->actingAs($user)->putJson(route('stock.articles.update', $article), [
            'reference' => 'ART-DUP',
            'nom' => $article->nom,
            'seuil_alerte' => 0,
        ])->assertUnprocessable();
    }

    public function test_destroy_soft_deletes_article(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $article = Article::factory()->create();

        $this->actingAs($user)->deleteJson(route('stock.articles.destroy', $article))
            ->assertOk();

        $this->assertSoftDeleted($article);
    }

    public function test_en_alerte_filter_only_shows_articles_below_threshold(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $enAlerte = Article::factory()->create(['nom' => 'Article en alerte', 'quantite_stock' => 1, 'seuil_alerte' => 5]);
        $normal = Article::factory()->create(['nom' => 'Article normal', 'quantite_stock' => 50, 'seuil_alerte' => 5]);

        $response = $this->actingAs($user)->get(route('stock.articles.index', ['en_alerte' => 1]));

        $response->assertOk();
        $response->assertSee($enAlerte->nom);
        $response->assertDontSee($normal->nom);
    }

    public function test_export_excel_requires_view_permission(): void
    {
        $user = User::factory()->create();
        Article::factory()->create();

        $this->actingAs($user)->get(route('stock.articles.export.excel'))->assertForbidden();
    }

    public function test_gestionnaire_stock_can_export_articles_excel(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create();

        $this->actingAs($user)->get(route('stock.articles.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_gestionnaire_stock_can_export_articles_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create();

        $this->actingAs($user)->get(route('stock.articles.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
