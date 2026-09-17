<?php

namespace Tests\Feature\Stock;

use App\Models\Achat;
use App\Models\AchatLigne;
use App\Models\Article;
use App\Models\Caisse;
use App\Models\CategorieArticle;
use App\Models\Fournisseur;
use App\Models\ModePaiement;
use App\Models\MouvementCaisse;
use App\Models\MouvementStock;
use App\Models\PaiementFournisseur;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StockDataLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_belongs_to_categorie_and_alert_scope_works(): void
    {
        $categorie = CategorieArticle::create(['code' => 'freinage', 'libelle' => 'Freinage', 'actif' => true]);

        $enAlerte = Article::factory()->create(['categorie_id' => $categorie->id, 'quantite_stock' => 2, 'seuil_alerte' => 5]);
        $ok = Article::factory()->create(['categorie_id' => $categorie->id, 'quantite_stock' => 10, 'seuil_alerte' => 5]);

        $this->assertTrue($enAlerte->categorie->is($categorie));
        $this->assertTrue(Article::enAlerte()->pluck('id')->contains($enAlerte->id));
        $this->assertFalse(Article::enAlerte()->pluck('id')->contains($ok->id));
    }

    public function test_full_relation_chain_achat_ligne_paiement_mouvement(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create();
        $modePaiement = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);
        $caisseDepenses = Caisse::firstOrCreate(['type' => 'depenses_fournisseurs'], ['type' => 'depenses_fournisseurs', 'libelle' => 'Paiements fournisseurs']);

        $achat = Achat::create([
            'reference' => 'PO-TEST',
            'fournisseur_id' => $fournisseur->id,
            'fournisseur_nom' => $fournisseur->nom,
            'date_achat' => now(),
            'montant_total' => 10000,
            'montant_paye' => 0,
            'montant_restant' => 10000,
            'statut_paiement' => 'credit',
            'user_id' => $user->id,
        ]);

        $ligne = AchatLigne::create([
            'achat_id' => $achat->id,
            'article_id' => $article->id,
            'article_reference' => $article->reference,
            'article_nom' => $article->nom,
            'quantite' => 10,
            'prix_unitaire' => 1000,
            'montant' => 10000,
        ]);

        $paiement = PaiementFournisseur::create([
            'achat_id' => $achat->id,
            'date_paiement' => now(),
            'montant' => 5000,
            'mode_paiement_id' => $modePaiement->id,
            'fournisseur_nom' => $fournisseur->nom,
            'user_id' => $user->id,
        ]);

        $mouvementCaisse = MouvementCaisse::create([
            'caisse_id' => $caisseDepenses->id,
            'sens' => 'sortie',
            'montant' => 5000,
            'mode_paiement_id' => $modePaiement->id,
            'motif' => 'Paiement fournisseur',
            'user_id' => $user->id,
            'date_mouvement' => now(),
        ]);

        $vehicule = Vehicule::factory()->create();

        $mouvementStock = MouvementStock::create([
            'article_id' => $article->id,
            'article_reference' => $article->reference,
            'article_nom' => $article->nom,
            'type' => 'entree',
            'quantite' => 10,
            'prix_unitaire' => 1000,
            'motif' => 'Achat',
            'achat_id' => $achat->id,
            'user_id' => $user->id,
            'date_mouvement' => now(),
        ]);

        $this->assertTrue($ligne->achat->is($achat));
        $this->assertTrue($ligne->article->is($article));
        $this->assertTrue($achat->lignes->first()->is($ligne));
        $this->assertTrue($paiement->achat->is($achat));
        $this->assertTrue($achat->paiements->first()->is($paiement));
        $this->assertTrue($mouvementCaisse->caisse->is($caisseDepenses));
        $this->assertTrue($mouvementStock->article->is($article));
        $this->assertTrue($mouvementStock->achat->is($achat));
        $this->assertNull($mouvementStock->vehicule);
        $this->assertTrue($vehicule->mouvementsStock->isEmpty());
    }

    public function test_soft_deletes_preserve_snapshot_history(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create(['nom' => 'Fournisseur Original']);
        $article = Article::factory()->create(['reference' => 'ART-0001', 'nom' => 'Article Original']);

        $achat = Achat::create([
            'fournisseur_id' => $fournisseur->id,
            'fournisseur_nom' => $fournisseur->nom,
            'date_achat' => now(),
            'montant_total' => 5000,
            'montant_paye' => 5000,
            'montant_restant' => 0,
            'statut_paiement' => 'comptant',
            'user_id' => $user->id,
        ]);

        $ligne = AchatLigne::create([
            'achat_id' => $achat->id,
            'article_id' => $article->id,
            'article_reference' => $article->reference,
            'article_nom' => $article->nom,
            'quantite' => 5,
            'prix_unitaire' => 1000,
            'montant' => 5000,
        ]);

        $fournisseur->update(['nom' => 'Fournisseur Renommé']);
        $article->delete();

        $achat->refresh();
        $ligne->refresh();

        $this->assertSame('Fournisseur Original', $achat->fournisseur_nom);
        $this->assertSame('Article Original', $ligne->article_nom);
        $this->assertSoftDeleted($article);
    }

    public function test_achat_observer_invalidates_month_kpi_cache(): void
    {
        $cacheKey = 'stock:kpi:'.now()->format('Y-m');
        Cache::put($cacheKey, ['stale' => true], 60);

        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create();

        Achat::create([
            'fournisseur_id' => $fournisseur->id,
            'fournisseur_nom' => $fournisseur->nom,
            'date_achat' => now(),
            'montant_total' => 1000,
            'montant_paye' => 0,
            'montant_restant' => 1000,
            'statut_paiement' => 'credit',
            'user_id' => $user->id,
        ]);

        $this->assertFalse(Cache::has($cacheKey));
    }
}
