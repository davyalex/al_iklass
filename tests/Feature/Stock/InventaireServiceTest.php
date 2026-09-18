<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\CategorieArticle;
use App\Models\MouvementStock;
use App\Models\User;
use App\Services\Stock\InventaireService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventaireServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventaireService $inventaireService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventaireService = app(InventaireService::class);
    }

    public function test_creer_snapshots_theoretical_quantities_for_all_active_articles(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 12, 'prix_achat' => 500, 'actif' => true]);
        Article::factory()->create(['actif' => false]);

        $inventaire = $this->inventaireService->creer([
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);

        $this->assertSame('brouillon', $inventaire->statut);
        $this->assertCount(1, $inventaire->lignes);

        $ligne = $inventaire->lignes->first();
        $this->assertSame($article->id, $ligne->article_id);
        $this->assertSame(12, $ligne->quantite_theorique);
        $this->assertNull($ligne->quantite_comptee);
        $this->assertEquals(500, $ligne->prix_achat_unitaire);
    }

    public function test_creer_scoped_to_categorie_only_includes_that_categorie(): void
    {
        $user = User::factory()->create();
        $categorie = CategorieArticle::create(['code' => 'moteur', 'libelle' => 'Moteur', 'actif' => true]);
        $autreCategorie = CategorieArticle::create(['code' => 'freinage', 'libelle' => 'Freinage', 'actif' => true]);

        $articleMoteur = Article::factory()->create(['categorie_id' => $categorie->id]);
        Article::factory()->create(['categorie_id' => $autreCategorie->id]);

        $inventaire = $this->inventaireService->creer([
            'categorie_id' => $categorie->id,
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);

        $this->assertCount(1, $inventaire->lignes);
        $this->assertSame($articleMoteur->id, $inventaire->lignes->first()->article_id);
    }

    public function test_enregistrer_comptage_saves_quantity_and_comment(): void
    {
        $user = User::factory()->create();
        Article::factory()->create(['quantite_stock' => 10]);

        $inventaire = $this->inventaireService->creer([
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);
        $ligne = $inventaire->lignes->first();

        $this->inventaireService->enregistrerComptage($ligne, 8, 'Casse constatée');

        $ligne->refresh();
        $this->assertSame(8, $ligne->quantite_comptee);
        $this->assertSame('Casse constatée', $ligne->commentaire);
    }

    public function test_enregistrer_comptage_blocked_once_validated(): void
    {
        $user = User::factory()->create();
        Article::factory()->create(['quantite_stock' => 10]);

        $inventaire = $this->inventaireService->creer([
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);
        $ligne = $inventaire->lignes->first();
        $this->inventaireService->enregistrerComptage($ligne, 10, null);
        $this->inventaireService->valider($inventaire->fresh(), $user->id);

        $this->expectException(ValidationException::class);

        $this->inventaireService->enregistrerComptage($ligne->fresh(), 5, null);
    }

    public function test_valider_applies_positive_ecart_as_entree_and_updates_stock(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10, 'prix_achat' => 200]);

        $inventaire = $this->inventaireService->creer([
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);
        $ligne = $inventaire->lignes->first();
        $this->inventaireService->enregistrerComptage($ligne, 15, null);

        $this->inventaireService->valider($inventaire->fresh(), $user->id);

        $this->assertSame(15, $article->fresh()->quantite_stock);

        $mouvement = MouvementStock::where('inventaire_id', $inventaire->id)->first();
        $this->assertNotNull($mouvement);
        $this->assertSame('entree', $mouvement->type);
        $this->assertSame('ajustement', $mouvement->nature);
        $this->assertSame(5, $mouvement->quantite);
    }

    public function test_valider_applies_negative_ecart_as_sortie_and_updates_stock(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10, 'prix_achat' => 200]);

        $inventaire = $this->inventaireService->creer([
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);
        $ligne = $inventaire->lignes->first();
        $this->inventaireService->enregistrerComptage($ligne, 6, null);

        $this->inventaireService->valider($inventaire->fresh(), $user->id);

        $this->assertSame(6, $article->fresh()->quantite_stock);

        $mouvement = MouvementStock::where('inventaire_id', $inventaire->id)->first();
        $this->assertNotNull($mouvement);
        $this->assertSame('sortie', $mouvement->type);
        $this->assertSame('ajustement', $mouvement->nature);
        $this->assertSame(4, $mouvement->quantite);
    }

    public function test_valider_ignores_lines_with_zero_ecart(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $inventaire = $this->inventaireService->creer([
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);
        $ligne = $inventaire->lignes->first();
        $this->inventaireService->enregistrerComptage($ligne, 10, null);

        $this->inventaireService->valider($inventaire->fresh(), $user->id);

        $this->assertSame(10, $article->fresh()->quantite_stock);
        $this->assertSame(0, MouvementStock::where('inventaire_id', $inventaire->id)->count());
    }

    public function test_valider_ignores_uncounted_lines(): void
    {
        $user = User::factory()->create();
        $articleCompte = Article::factory()->create(['quantite_stock' => 10]);
        $articleNonCompte = Article::factory()->create(['quantite_stock' => 20]);

        $inventaire = $this->inventaireService->creer([
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);
        $ligneCompte = $inventaire->lignes->firstWhere('article_id', $articleCompte->id);
        $this->inventaireService->enregistrerComptage($ligneCompte, 15, null);

        $this->inventaireService->valider($inventaire->fresh(), $user->id);

        $this->assertSame(15, $articleCompte->fresh()->quantite_stock);
        $this->assertSame(20, $articleNonCompte->fresh()->quantite_stock);
    }

    public function test_valider_fails_when_nothing_counted(): void
    {
        $user = User::factory()->create();
        Article::factory()->create(['quantite_stock' => 10]);

        $inventaire = $this->inventaireService->creer([
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);

        $this->expectException(ValidationException::class);

        $this->inventaireService->valider($inventaire, $user->id);
    }

    public function test_valider_fails_when_already_validated(): void
    {
        $user = User::factory()->create();
        Article::factory()->create(['quantite_stock' => 10]);

        $inventaire = $this->inventaireService->creer([
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);
        $this->inventaireService->enregistrerComptage($inventaire->lignes->first(), 10, null);
        $this->inventaireService->valider($inventaire->fresh(), $user->id);

        $this->expectException(ValidationException::class);

        $this->inventaireService->valider($inventaire->fresh(), $user->id);
    }

    public function test_supprimer_brouillon_ok(): void
    {
        $user = User::factory()->create();
        Article::factory()->create();

        $inventaire = $this->inventaireService->creer([
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);

        $this->inventaireService->supprimer($inventaire);

        $this->assertSoftDeleted($inventaire);
    }

    public function test_supprimer_valide_est_bloque(): void
    {
        $user = User::factory()->create();
        Article::factory()->create(['quantite_stock' => 10]);

        $inventaire = $this->inventaireService->creer([
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);
        $this->inventaireService->enregistrerComptage($inventaire->lignes->first(), 10, null);
        $this->inventaireService->valider($inventaire->fresh(), $user->id);

        $this->expectException(ValidationException::class);

        $this->inventaireService->supprimer($inventaire->fresh());
    }

    public function test_reference_generee_automatiquement_si_vide(): void
    {
        $user = User::factory()->create();
        Article::factory()->create();

        $inventaire = $this->inventaireService->creer([
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);

        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{4}$/', $inventaire->reference);
    }

    public function test_reference_fournie_est_conservee(): void
    {
        $user = User::factory()->create();
        Article::factory()->create();

        $inventaire = $this->inventaireService->creer([
            'reference' => 'MA-REF-PERSO',
            'date_inventaire' => now(),
            'user_id' => $user->id,
        ]);

        $this->assertSame('MA-REF-PERSO', $inventaire->reference);
    }
}
