<?php

namespace Tests\Feature\Stock;

use App\Exceptions\Stock\StockInsuffisantException;
use App\Models\Article;
use App\Models\Caisse;
use App\Models\User;
use App\Models\Vehicule;
use App\Services\Stock\SortieStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SortieStockServiceTest extends TestCase
{
    use RefreshDatabase;

    private SortieStockService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SortieStockService::class);

        Caisse::firstOrCreate(['type' => 'ventes_externes'], ['type' => 'ventes_externes', 'libelle' => 'Ventes externes']);
    }

    public function test_creer_interne_decrements_stock_snapshots_vehicule_and_does_not_touch_caisse(): void
    {
        $article = Article::factory()->create(['quantite_stock' => 20, 'prix_achat' => 1500]);
        $vehicule = Vehicule::factory()->create(['code' => 'AL-042']);
        $user = User::factory()->create();

        $sortie = $this->service->creerInterne([
            'motif' => 'Réparation freins',
            'vehicule_id' => $vehicule->id,
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 5],
            ],
        ]);

        $this->assertSame(15, $article->fresh()->quantite_stock);
        $this->assertSame('interne', $sortie->nature);
        $this->assertSame('AL-042', $sortie->vehicule_code);
        $this->assertNull($sortie->caisse_mouvement_id);
        $this->assertEquals(5 * 1500, $sortie->montant_total);

        $ligne = $sortie->lignes->first();
        $this->assertEquals(1500, $ligne->prix_unitaire);
        $this->assertNull($ligne->prix_vente);

        $mouvement = $sortie->mouvementsStock->first();
        $this->assertSame('sortie', $mouvement->type);
        $this->assertSame('interne', $mouvement->nature);
    }

    public function test_creer_externe_decrements_stock_captures_sale_price_and_credits_ventes_externes_caisse(): void
    {
        $article = Article::factory()->create(['quantite_stock' => 20, 'prix_achat' => 1000]);
        $user = User::factory()->create();

        $sortie = $this->service->creerExterne([
            'motif' => 'Vente comptoir',
            'vehicule_externe' => 'CI-1234-AB',
            'acheteur' => 'Garage Koffi',
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 4, 'prix_vente' => 1800],
            ],
        ]);

        $this->assertSame(16, $article->fresh()->quantite_stock);
        $this->assertSame('externe', $sortie->nature);
        $this->assertEquals(4 * 1800, $sortie->montant_total);
        $this->assertNotNull($sortie->caisse_mouvement_id);

        $ligne = $sortie->lignes->first();
        $this->assertEquals(1000, $ligne->prix_unitaire);
        $this->assertEquals(1800, $ligne->prix_vente);

        $mouvementCaisse = $sortie->mouvementCaisse;
        $this->assertSame('entree', $mouvementCaisse->sens);
        $this->assertEquals(4 * 1800, $mouvementCaisse->montant);
        $this->assertSame('ventes_externes', $mouvementCaisse->caisse->type);
    }

    public function test_creer_interne_avec_plusieurs_lignes_cumule_le_montant_total(): void
    {
        $article1 = Article::factory()->create(['quantite_stock' => 20, 'prix_achat' => 1000]);
        $article2 = Article::factory()->create(['quantite_stock' => 20, 'prix_achat' => 500]);
        $vehicule = Vehicule::factory()->create();
        $user = User::factory()->create();

        $sortie = $this->service->creerInterne([
            'motif' => 'Entretien',
            'vehicule_id' => $vehicule->id,
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article1->id, 'quantite' => 2],
                ['article_id' => $article2->id, 'quantite' => 3],
            ],
        ]);

        $this->assertSame(18, $article1->fresh()->quantite_stock);
        $this->assertSame(17, $article2->fresh()->quantite_stock);
        $this->assertEquals(2 * 1000 + 3 * 500, $sortie->montant_total);
        $this->assertSame(2, $sortie->lignes->count());
    }

    public function test_creer_interne_is_rejected_when_quantity_exceeds_stock(): void
    {
        $article = Article::factory()->create(['quantite_stock' => 3]);
        $vehicule = Vehicule::factory()->create();
        $user = User::factory()->create();

        $this->expectException(StockInsuffisantException::class);

        try {
            $this->service->creerInterne([
                'motif' => 'Test',
                'vehicule_id' => $vehicule->id,
                'user_id' => $user->id,
                'lignes' => [
                    ['article_id' => $article->id, 'quantite' => 10],
                ],
            ]);
        } finally {
            $this->assertSame(3, $article->fresh()->quantite_stock);
        }
    }

    public function test_creer_externe_is_rejected_when_quantity_exceeds_stock(): void
    {
        $article = Article::factory()->create(['quantite_stock' => 2]);
        $user = User::factory()->create();

        $this->expectException(StockInsuffisantException::class);

        try {
            $this->service->creerExterne([
                'motif' => 'Test',
                'vehicule_externe' => 'CI-9999-ZZ',
                'acheteur' => 'Client X',
                'user_id' => $user->id,
                'lignes' => [
                    ['article_id' => $article->id, 'quantite' => 5, 'prix_vente' => 1000],
                ],
            ]);
        } finally {
            $this->assertSame(2, $article->fresh()->quantite_stock);
        }
    }
}
