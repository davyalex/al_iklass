<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\MouvementStock;
use App\Models\User;
use App\Services\Stock\AchatService;
use App\Services\Stock\BonCommandeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AchatServiceTest extends TestCase
{
    use RefreshDatabase;

    private AchatService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AchatService::class);
    }

    public function test_achat_increments_stock_writes_lines_and_movements_and_updates_prix_achat(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10, 'prix_achat' => 500]);

        $achat = $this->service->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 5, 'prix_unitaire' => 800],
            ],
        ]);

        $article->refresh();

        $this->assertSame(15, $article->quantite_stock);
        $this->assertEquals(800, $article->prix_achat);
        $this->assertSame(1, $achat->lignes->count());
        $this->assertEquals(4000, $achat->montant_total);

        $mouvement = MouvementStock::where('achat_id', $achat->id)->first();
        $this->assertNotNull($mouvement);
        $this->assertSame('entree', $mouvement->type);
        $this->assertSame($article->reference, $mouvement->article_reference);
        $this->assertSame(5, $mouvement->quantite);
    }

    public function test_montant_total_is_recomputed_server_side_from_lignes(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $achat = $this->service->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 3, 'prix_unitaire' => 1000],
            ],
        ]);

        $this->assertEquals(3000, $achat->montant_total);
    }

    public function test_statut_paiement_is_derived_from_montant_paye(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $achatCredit = $this->service->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1, 'prix_unitaire' => 1000]],
        ]);
        $this->assertSame('credit', $achatCredit->statut_paiement);

        $achatPartiel = $this->service->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_achat' => now(),
            'montant_paye' => 400,
            'user_id' => $user->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1, 'prix_unitaire' => 1000]],
        ]);
        $this->assertSame('partiel', $achatPartiel->statut_paiement);

        $achatComptant = $this->service->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_achat' => now(),
            'montant_paye' => 1000,
            'user_id' => $user->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1, 'prix_unitaire' => 1000]],
        ]);
        $this->assertSame('comptant', $achatComptant->statut_paiement);
        $this->assertEquals(0, $achatComptant->montant_restant);
    }

    public function test_achat_with_multiple_lignes_increments_each_article_independently(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $articleA = Article::factory()->create(['quantite_stock' => 0]);
        $articleB = Article::factory()->create(['quantite_stock' => 0]);

        $this->service->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $articleA->id, 'quantite' => 4, 'prix_unitaire' => 200],
                ['article_id' => $articleB->id, 'quantite' => 7, 'prix_unitaire' => 300],
            ],
        ]);

        $this->assertSame(4, $articleA->fresh()->quantite_stock);
        $this->assertSame(7, $articleB->fresh()->quantite_stock);
    }

    public function test_reception_cannot_exceed_quantite_restante_du_bon_de_commande(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 0]);
        $bonCommandeService = app(BonCommandeService::class);

        $bonCommande = $bonCommandeService->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 5, 'prix_unitaire_estime' => 200]],
        ]);
        $ligneId = $bonCommande->lignes->first()->id;

        $this->expectException(ValidationException::class);

        $this->service->creer([
            'fournisseur_id' => $fournisseur->id,
            'bon_commande_id' => $bonCommande->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 6, 'prix_unitaire' => 200, 'bon_commande_ligne_id' => $ligneId],
            ],
        ]);

        $this->assertSame(0, $article->fresh()->quantite_stock);
        $this->assertSame(0, $bonCommande->fresh()->lignes->first()->quantite_recue);
    }

    public function test_reception_ligne_ne_peut_pas_appartenir_a_un_autre_bon_de_commande(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 0]);
        $bonCommandeService = app(BonCommandeService::class);

        $bonCommandeA = $bonCommandeService->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 5, 'prix_unitaire_estime' => 200]],
        ]);
        $bonCommandeB = $bonCommandeService->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 5, 'prix_unitaire_estime' => 200]],
        ]);
        $ligneDeA = $bonCommandeA->lignes->first()->id;

        $this->expectException(ValidationException::class);

        $this->service->creer([
            'fournisseur_id' => $fournisseur->id,
            'bon_commande_id' => $bonCommandeB->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 1, 'prix_unitaire' => 200, 'bon_commande_ligne_id' => $ligneDeA],
            ],
        ]);
    }

    public function test_reception_liee_a_un_bon_de_commande_refuse_un_article_non_commande(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $articleCommande = Article::factory()->create();
        $articleNonCommande = Article::factory()->create(['quantite_stock' => 0]);
        $bonCommandeService = app(BonCommandeService::class);

        $bonCommande = $bonCommandeService->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [['article_id' => $articleCommande->id, 'quantite_commandee' => 5, 'prix_unitaire_estime' => 200]],
        ]);

        $this->expectException(ValidationException::class);

        $this->service->creer([
            'fournisseur_id' => $fournisseur->id,
            'bon_commande_id' => $bonCommande->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $articleNonCommande->id, 'quantite' => 1, 'prix_unitaire' => 200],
            ],
        ]);

        $this->assertSame(0, $articleNonCommande->fresh()->quantite_stock);
    }
}
