<?php

namespace Tests\Feature\Stock;

use App\Models\Achat;
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

    public function test_data_can_be_filtered_by_fournisseur_and_statut(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseurRecherche = Fournisseur::factory()->create();

        Achat::factory()->create([
            'fournisseur_id' => $fournisseurRecherche->id,
            'statut_paiement' => 'comptant',
        ]);
        Achat::factory()->create([
            'statut_paiement' => 'credit',
        ]);

        $response = $this->actingAs($user)->getJson(route('stock.achats.data', [
            'fournisseur_id' => $fournisseurRecherche->id,
            'statut_paiement' => 'comptant',
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_data_can_be_filtered_by_date_range(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        Achat::factory()->create(['date_achat' => now()->subDays(10)]);
        Achat::factory()->create(['date_achat' => now()]);

        $response = $this->actingAs($user)->getJson(route('stock.achats.data', [
            'date_debut' => now()->subDay()->format('Y-m-d'),
            'date_fin' => now()->addDay()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_gestionnaire_stock_can_export_achats_excel_and_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Achat::factory()->create();

        $this->actingAs($user)->get(route('stock.achats.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($user)->get(route('stock.achats.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_chef_mecanicien_cannot_export_achats(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->get(route('stock.achats.export.excel'))->assertForbidden();
        $this->actingAs($user)->get(route('stock.achats.export.pdf'))->assertForbidden();
    }

    public function test_pdf_detail_endpoint_returns_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $achat = Achat::factory()->create();

        $this->actingAs($user)->get(route('stock.achats.pdf', $achat))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
