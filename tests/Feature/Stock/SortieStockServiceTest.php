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

    public function test_sortie_interne_decrements_stock_snapshots_vehicule_and_does_not_touch_caisse(): void
    {
        $article = Article::factory()->create(['quantite_stock' => 20, 'prix_achat' => 1500]);
        $vehicule = Vehicule::factory()->create(['code' => 'AL-042']);
        $user = User::factory()->create();

        $mouvement = $this->service->sortieInterne([
            'article_id' => $article->id,
            'quantite' => 5,
            'vehicule_id' => $vehicule->id,
            'motif' => 'Réparation freins',
            'user_id' => $user->id,
        ]);

        $this->assertSame(15, $article->fresh()->quantite_stock);
        $this->assertSame('sortie', $mouvement->type);
        $this->assertSame('interne', $mouvement->nature);
        $this->assertSame('AL-042', $mouvement->vehicule_code);
        $this->assertEquals(1500, $mouvement->prix_unitaire);
        $this->assertNull($mouvement->caisse_mouvement_id);
        $this->assertNull($mouvement->prix_vente);
    }

    public function test_sortie_externe_decrements_stock_captures_sale_price_and_credits_ventes_externes_caisse(): void
    {
        $article = Article::factory()->create(['quantite_stock' => 20, 'prix_achat' => 1000]);
        $user = User::factory()->create();

        $mouvement = $this->service->sortieExterne([
            'article_id' => $article->id,
            'quantite' => 4,
            'prix_vente' => 1800,
            'vehicule_externe' => 'CI-1234-AB',
            'acheteur' => 'Garage Koffi',
            'motif' => 'Vente comptoir',
            'user_id' => $user->id,
        ]);

        $this->assertSame(16, $article->fresh()->quantite_stock);
        $this->assertSame('externe', $mouvement->nature);
        $this->assertEquals(1000, $mouvement->prix_unitaire);
        $this->assertEquals(1800, $mouvement->prix_vente);
        $this->assertNotNull($mouvement->caisse_mouvement_id);

        $mouvementCaisse = $mouvement->mouvementCaisse;
        $this->assertSame('entree', $mouvementCaisse->sens);
        $this->assertEquals(4 * 1800, $mouvementCaisse->montant);
        $this->assertSame('ventes_externes', $mouvementCaisse->caisse->type);
    }

    public function test_sortie_interne_is_rejected_when_quantity_exceeds_stock(): void
    {
        $article = Article::factory()->create(['quantite_stock' => 3]);
        $vehicule = Vehicule::factory()->create();
        $user = User::factory()->create();

        $this->expectException(StockInsuffisantException::class);

        try {
            $this->service->sortieInterne([
                'article_id' => $article->id,
                'quantite' => 10,
                'vehicule_id' => $vehicule->id,
                'motif' => 'Test',
                'user_id' => $user->id,
            ]);
        } finally {
            $this->assertSame(3, $article->fresh()->quantite_stock);
        }
    }

    public function test_sortie_externe_is_rejected_when_quantity_exceeds_stock(): void
    {
        $article = Article::factory()->create(['quantite_stock' => 2]);
        $user = User::factory()->create();

        $this->expectException(StockInsuffisantException::class);

        try {
            $this->service->sortieExterne([
                'article_id' => $article->id,
                'quantite' => 5,
                'prix_vente' => 1000,
                'vehicule_externe' => 'CI-9999-ZZ',
                'acheteur' => 'Client X',
                'motif' => 'Test',
                'user_id' => $user->id,
            ]);
        } finally {
            $this->assertSame(2, $article->fresh()->quantite_stock);
        }
    }
}
