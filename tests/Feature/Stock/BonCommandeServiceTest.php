<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\User;
use App\Services\Stock\AchatService;
use App\Services\Stock\BonCommandeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BonCommandeServiceTest extends TestCase
{
    use RefreshDatabase;

    private BonCommandeService $bonCommandeService;

    private AchatService $achatService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bonCommandeService = app(BonCommandeService::class);
        $this->achatService = app(AchatService::class);
    }

    public function test_creer_snapshots_lines_and_does_not_touch_stock(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 5, 'reference' => 'ART-BC-1', 'nom' => 'Filtre']);

        $bonCommande = $this->bonCommandeService->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite_commandee' => 10, 'prix_unitaire_estime' => 500],
            ],
        ]);

        $this->assertSame('en_attente', $bonCommande->statut);
        $this->assertSame(5, $article->fresh()->quantite_stock);

        $ligne = $bonCommande->lignes->first();
        $this->assertSame('ART-BC-1', $ligne->article_reference);
        $this->assertSame('Filtre', $ligne->article_nom);
        $this->assertSame(10, $ligne->quantite_commandee);
        $this->assertSame(0, $ligne->quantite_recue);
        $this->assertEquals(5000, $ligne->montant_estime);
    }

    public function test_reception_complete_updates_statut_to_recu_and_stock(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 0]);

        $bonCommande = $this->bonCommandeService->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite_commandee' => 10, 'prix_unitaire_estime' => 500],
            ],
        ]);
        $ligneId = $bonCommande->lignes->first()->id;

        $this->achatService->creer([
            'fournisseur_id' => $fournisseur->id,
            'bon_commande_id' => $bonCommande->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 10, 'prix_unitaire' => 500, 'bon_commande_ligne_id' => $ligneId],
            ],
        ]);

        $this->assertSame(10, $article->fresh()->quantite_stock);
        $this->assertSame('recu', $bonCommande->fresh()->statut);
        $this->assertSame(10, $bonCommande->fresh()->lignes->first()->quantite_recue);
    }

    public function test_reception_partielle_updates_statut_to_partiellement_recu(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 0]);

        $bonCommande = $this->bonCommandeService->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite_commandee' => 10, 'prix_unitaire_estime' => 500],
            ],
        ]);
        $ligneId = $bonCommande->lignes->first()->id;

        $this->achatService->creer([
            'fournisseur_id' => $fournisseur->id,
            'bon_commande_id' => $bonCommande->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 6, 'prix_unitaire' => 500, 'bon_commande_ligne_id' => $ligneId],
            ],
        ]);

        $this->assertSame(6, $article->fresh()->quantite_stock);
        $this->assertSame('partiellement_recu', $bonCommande->fresh()->statut);

        // Deuxième livraison qui complète la commande.
        $this->achatService->creer([
            'fournisseur_id' => $fournisseur->id,
            'bon_commande_id' => $bonCommande->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 4, 'prix_unitaire' => 500, 'bon_commande_ligne_id' => $ligneId],
            ],
        ]);

        $this->assertSame(10, $article->fresh()->quantite_stock);
        $this->assertSame('recu', $bonCommande->fresh()->statut);
    }

    public function test_achat_direct_sans_bon_de_commande_still_works(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 0]);

        $achat = $this->achatService->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 3, 'prix_unitaire' => 100],
            ],
        ]);

        $this->assertNull($achat->bon_commande_id);
        $this->assertSame(3, $article->fresh()->quantite_stock);
    }

    public function test_annuler_bon_commande_non_recu(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $bonCommande = $this->bonCommandeService->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite_commandee' => 5, 'prix_unitaire_estime' => 200],
            ],
        ]);

        $this->bonCommandeService->annuler($bonCommande);

        $this->assertSame('annule', $bonCommande->fresh()->statut);
    }

    public function test_annuler_bon_commande_deja_partiellement_recu_est_bloque(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();

        $bonCommande = $this->bonCommandeService->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite_commandee' => 5, 'prix_unitaire_estime' => 200],
            ],
        ]);
        $ligneId = $bonCommande->lignes->first()->id;

        $this->achatService->creer([
            'fournisseur_id' => $fournisseur->id,
            'bon_commande_id' => $bonCommande->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 2, 'prix_unitaire' => 200, 'bon_commande_ligne_id' => $ligneId],
            ],
        ]);

        $this->expectException(ValidationException::class);

        $this->bonCommandeService->annuler($bonCommande->fresh());
    }
}
