<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\Caisse;
use App\Models\SortieStock;
use App\Models\User;
use App\Models\Vehicule;
use App\Services\Stock\SortieStockService;
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

    public function test_gestionnaire_stock_peut_afficher_la_page_sorties(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Article::factory()->create(['quantite_stock' => 10]);
        Vehicule::factory()->create();

        $this->actingAs($user)->get(route('stock.sorties.index'))
            ->assertOk()
            ->assertSee('Sortie interne')
            ->assertSee('Vente externe');
    }

    public function test_gestionnaire_stock_can_record_sortie_interne_avec_plusieurs_lignes(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article1 = Article::factory()->create(['quantite_stock' => 10]);
        $article2 = Article::factory()->create(['quantite_stock' => 10]);
        $vehicule = Vehicule::factory()->create();

        $response = $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'interne',
            'vehicule_id' => $vehicule->id,
            'motif' => 'Entretien',
            'lignes' => [
                ['article_id' => $article1->id, 'quantite' => 3],
                ['article_id' => $article2->id, 'quantite' => 2],
            ],
        ]);

        $response->assertCreated();
        $this->assertSame(7, $article1->fresh()->quantite_stock);
        $this->assertSame(8, $article2->fresh()->quantite_stock);

        $sortie = SortieStock::first();
        $this->assertSame(2, $sortie->lignes()->count());
    }

    public function test_gestionnaire_stock_can_record_vente_externe_avec_plusieurs_lignes(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article1 = Article::factory()->create(['quantite_stock' => 10, 'prix_achat' => 1000]);
        $article2 = Article::factory()->create(['quantite_stock' => 10, 'prix_achat' => 500]);

        $response = $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'externe',
            'vehicule_externe' => 'CI-0001-AA',
            'acheteur' => 'Client Test',
            'motif' => 'Vente',
            'lignes' => [
                ['article_id' => $article1->id, 'quantite' => 2, 'prix_vente' => 3000],
                ['article_id' => $article2->id, 'quantite' => 1, 'prix_vente' => 800],
            ],
        ]);

        $response->assertCreated();
        $this->assertSame(8, $article1->fresh()->quantite_stock);
        $this->assertSame(9, $article2->fresh()->quantite_stock);

        $sortie = SortieStock::first();
        $this->assertEquals(6800, (float) $sortie->montant_total);
        $this->assertNotNull($sortie->caisse_mouvement_id);
    }

    public function test_insufficient_stock_returns_422_with_flag_et_annule_toute_la_sortie(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $articleOk = Article::factory()->create(['quantite_stock' => 10]);
        $articleInsuffisant = Article::factory()->create(['quantite_stock' => 1]);
        $vehicule = Vehicule::factory()->create();

        $response = $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'interne',
            'vehicule_id' => $vehicule->id,
            'motif' => 'Test',
            'lignes' => [
                ['article_id' => $articleOk->id, 'quantite' => 2],
                ['article_id' => $articleInsuffisant->id, 'quantite' => 5],
            ],
        ]);

        $response->assertStatus(422)->assertJson(['insufficient_stock' => true]);
        $this->assertSame(10, $articleOk->fresh()->quantite_stock);
        $this->assertSame(1, $articleInsuffisant->fresh()->quantite_stock);
        $this->assertSame(0, SortieStock::count());
    }

    public function test_chef_mecanicien_cannot_record_sortie(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');
        $article = Article::factory()->create(['quantite_stock' => 10]);
        $vehicule = Vehicule::factory()->create();

        $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'interne',
            'vehicule_id' => $vehicule->id,
            'motif' => 'Test',
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 1],
            ],
        ])->assertForbidden();
    }

    public function test_data_can_be_filtered_by_article_and_nature(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create(['quantite_stock' => 10]);
        $vehicule = Vehicule::factory()->create();

        $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'interne',
            'vehicule_id' => $vehicule->id,
            'motif' => 'Test',
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 1],
            ],
        ])->assertCreated();

        $autreArticle = Article::factory()->create(['quantite_stock' => 10]);
        $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'externe',
            'vehicule_externe' => 'CI-0002-BB',
            'acheteur' => 'Autre client',
            'motif' => 'Vente',
            'lignes' => [
                ['article_id' => $autreArticle->id, 'quantite' => 1, 'prix_vente' => 1000],
            ],
        ])->assertCreated();

        $response = $this->actingAs($user)->getJson(route('stock.sorties.data', [
            'article_id' => $article->id,
            'nature' => 'interne',
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_kpis_sont_dissocies_entre_interne_et_externe(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $articleInterne = Article::factory()->create(['quantite_stock' => 10, 'prix_achat' => 1000]);
        $articleExterne = Article::factory()->create(['quantite_stock' => 10]);
        $vehicule = Vehicule::factory()->create();

        $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'interne',
            'vehicule_id' => $vehicule->id,
            'motif' => 'Test',
            'lignes' => [
                ['article_id' => $articleInterne->id, 'quantite' => 3],
            ],
        ])->assertCreated();

        $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'externe',
            'vehicule_externe' => 'CI-0003-CC',
            'acheteur' => 'Client externe',
            'motif' => 'Vente',
            'lignes' => [
                ['article_id' => $articleExterne->id, 'quantite' => 2, 'prix_vente' => 1500],
            ],
        ])->assertCreated();

        $response = $this->actingAs($user)->getJson(route('stock.sorties.kpis'));

        $response->assertOk();
        $this->assertSame(1, $response->json('sorties_jour_interne'));
        $this->assertSame(1, $response->json('sorties_jour_externe'));
        $this->assertSame(3, $response->json('quantite_interne'));
        $this->assertEquals(3000, $response->json('valeur_interne'));
        $this->assertSame(2, $response->json('quantite_externe'));
        $this->assertEquals(3000, $response->json('valeur_externe'));
    }

    public function test_kpis_periode_suivent_le_mois_par_defaut_mais_le_jour_reste_fixe(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create(['quantite_stock' => 10, 'prix_achat' => 1000]);
        $vehicule = Vehicule::factory()->create();

        // Une sortie le mois dernier ne doit pas compter dans les KPI "mois en cours" par défaut.
        $this->service()->creerInterne([
            'motif' => 'Ancien',
            'vehicule_id' => $vehicule->id,
            'date_sortie' => now()->subMonth(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 5],
            ],
        ]);

        $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'interne',
            'vehicule_id' => $vehicule->id,
            'motif' => 'Ce mois',
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 2],
            ],
        ])->assertCreated();

        $reponseParDefaut = $this->actingAs($user)->getJson(route('stock.sorties.kpis'));
        $reponseParDefaut->assertOk();
        $this->assertSame(2, $reponseParDefaut->json('quantite_interne'));
        $this->assertSame(1, $reponseParDefaut->json('sorties_jour_interne'));

        // Avec un filtre de période couvrant le mois dernier, le KPI "jour" reste inchangé
        // mais le KPI de quantité doit refléter la période filtrée.
        $reponseFiltree = $this->actingAs($user)->getJson(route('stock.sorties.kpis', [
            'date_debut' => now()->subMonth()->startOfMonth()->toDateString(),
            'date_fin' => now()->subMonth()->endOfMonth()->toDateString(),
        ]));
        $reponseFiltree->assertOk();
        $this->assertSame(5, $reponseFiltree->json('quantite_interne'));
        $this->assertSame(1, $reponseFiltree->json('sorties_jour_interne'));
    }

    private function service(): SortieStockService
    {
        return app(SortieStockService::class);
    }

    public function test_motif_est_facultatif(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create(['quantite_stock' => 10]);
        $vehicule = Vehicule::factory()->create();

        $response = $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'interne',
            'vehicule_id' => $vehicule->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 1],
            ],
        ]);

        $response->assertCreated();
        $this->assertNull(SortieStock::first()->motif);
    }

    public function test_show_retourne_la_sortie_avec_ses_lignes(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create(['quantite_stock' => 10]);
        $vehicule = Vehicule::factory()->create();

        $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'interne',
            'vehicule_id' => $vehicule->id,
            'motif' => 'Test',
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 1],
            ],
        ])->assertCreated();

        $sortie = SortieStock::first();

        $response = $this->actingAs($user)->getJson(route('stock.sorties.show', $sortie));

        $response->assertOk();
        $this->assertCount(1, $response->json('lignes'));
    }

    public function test_gestionnaire_stock_can_export_sorties_excel_et_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $article = Article::factory()->create(['quantite_stock' => 10]);
        $vehicule = Vehicule::factory()->create();

        $this->actingAs($user)->postJson(route('stock.sorties.store'), [
            'nature' => 'interne',
            'vehicule_id' => $vehicule->id,
            'motif' => 'Test',
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 1],
            ],
        ])->assertCreated();

        $sortie = SortieStock::first();

        $this->actingAs($user)->get(route('stock.sorties.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($user)->get(route('stock.sorties.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)->get(route('stock.sorties.pdf', $sortie))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)->get(route('stock.sorties.export.excel.single', $sortie))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_chef_mecanicien_cannot_export_sorties(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->get(route('stock.sorties.export.excel'))->assertForbidden();
        $this->actingAs($user)->get(route('stock.sorties.export.pdf'))->assertForbidden();
    }
}
