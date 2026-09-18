<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\CategorieArticle;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventaireControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gestionnaire_stock_can_view_and_create_inventaire(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create();

        $this->actingAs($user)->get(route('stock.inventaires.index'))->assertOk();

        $response = $this->actingAs($user)->postJson(route('stock.inventaires.store'), [
            'date_inventaire' => now()->format('Y-m-d'),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('inventaires', ['statut' => 'brouillon']);
    }

    public function test_chef_mecanicien_cannot_create_inventaire(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->postJson(route('stock.inventaires.store'), [
            'date_inventaire' => now()->format('Y-m-d'),
        ])->assertForbidden();
    }

    public function test_update_ligne_comptage_via_http(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create(['quantite_stock' => 10]);

        $create = $this->actingAs($user)->postJson(route('stock.inventaires.store'), [
            'date_inventaire' => now()->format('Y-m-d'),
        ]);
        $ligneId = $create->json('inventaire.lignes.0.id');

        $this->actingAs($user)->putJson(route('stock.inventaires.lignes.update', $ligneId), [
            'quantite_comptee' => 7,
            'commentaire' => 'Vérifié',
        ])->assertOk();

        $this->assertDatabaseHas('inventaire_lignes', ['id' => $ligneId, 'quantite_comptee' => 7]);
    }

    public function test_valider_inventaire_via_http_updates_stock(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $create = $this->actingAs($user)->postJson(route('stock.inventaires.store'), [
            'date_inventaire' => now()->format('Y-m-d'),
        ]);
        $inventaireId = $create->json('inventaire.id');
        $ligneId = $create->json('inventaire.lignes.0.id');

        $this->actingAs($user)->putJson(route('stock.inventaires.lignes.update', $ligneId), [
            'quantite_comptee' => 14,
        ])->assertOk();

        $this->actingAs($user)->postJson(route('stock.inventaires.valider', $inventaireId))
            ->assertOk();

        $this->assertSame(14, $article->fresh()->quantite_stock);
        $this->assertDatabaseHas('inventaires', ['id' => $inventaireId, 'statut' => 'valide']);
    }

    public function test_supprimer_inventaire_via_http(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create();

        $create = $this->actingAs($user)->postJson(route('stock.inventaires.store'), [
            'date_inventaire' => now()->format('Y-m-d'),
        ]);
        $inventaireId = $create->json('inventaire.id');

        $this->actingAs($user)->deleteJson(route('stock.inventaires.destroy', $inventaireId))
            ->assertOk();

        $this->assertSoftDeleted('inventaires', ['id' => $inventaireId]);
    }

    public function test_data_can_be_filtered_by_categorie_and_statut(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $categorie = CategorieArticle::create(['code' => 'moteur', 'libelle' => 'Moteur', 'actif' => true]);
        Article::factory()->create(['categorie_id' => $categorie->id]);

        $this->actingAs($user)->postJson(route('stock.inventaires.store'), [
            'categorie_id' => $categorie->id,
            'date_inventaire' => now()->format('Y-m-d'),
        ])->assertCreated();

        $this->actingAs($user)->postJson(route('stock.inventaires.store'), [
            'date_inventaire' => now()->format('Y-m-d'),
        ])->assertCreated();

        $response = $this->actingAs($user)->getJson(route('stock.inventaires.data', [
            'categorie_id' => $categorie->id,
            'statut' => 'brouillon',
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_gestionnaire_stock_can_export_inventaires_excel_and_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create();

        $this->actingAs($user)->postJson(route('stock.inventaires.store'), [
            'date_inventaire' => now()->format('Y-m-d'),
        ]);

        $this->actingAs($user)->get(route('stock.inventaires.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($user)->get(route('stock.inventaires.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_chef_mecanicien_cannot_export_inventaires(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->get(route('stock.inventaires.export.excel'))->assertForbidden();
        $this->actingAs($user)->get(route('stock.inventaires.export.pdf'))->assertForbidden();
    }
}
