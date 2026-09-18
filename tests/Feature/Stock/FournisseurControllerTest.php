<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\Caisse;
use App\Models\Fournisseur;
use App\Models\ModePaiement;
use App\Models\User;
use App\Services\Stock\AchatService;
use App\Services\Stock\BonCommandeService;
use App\Services\Stock\PaiementFournisseurService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FournisseurControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gestionnaire_stock_can_create_and_update_fournisseur(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $create = $this->actingAs($user)->postJson(route('stock.fournisseurs.store'), [
            'nom' => 'Pièces Abidjan SARL',
            'telephone' => '0102030405',
        ]);
        $create->assertCreated();

        $fournisseur = Fournisseur::where('nom', 'Pièces Abidjan SARL')->firstOrFail();

        $this->actingAs($user)->putJson(route('stock.fournisseurs.update', $fournisseur), [
            'nom' => 'Pièces Abidjan SARL',
            'telephone' => '0709080706',
        ])->assertOk();

        $this->assertSame('0709080706', $fournisseur->fresh()->telephone);
    }

    public function test_chef_mecanicien_cannot_manage_fournisseurs(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->postJson(route('stock.fournisseurs.store'), [
            'nom' => 'Test',
        ])->assertForbidden();
    }

    public function test_destroy_soft_deletes_fournisseur(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $fournisseur = Fournisseur::factory()->create();

        $this->actingAs($user)->deleteJson(route('stock.fournisseurs.destroy', $fournisseur))
            ->assertOk();

        $this->assertSoftDeleted($fournisseur);
    }

    public function test_gestionnaire_stock_can_view_compte_fournisseur(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();

        $this->actingAs($user)->get(route('stock.fournisseurs.compte', $fournisseur))
            ->assertOk()
            ->assertViewIs('stock.fournisseurs.compte');
    }

    public function test_chef_mecanicien_cannot_view_compte_fournisseur(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');
        $fournisseur = Fournisseur::factory()->create();

        $this->actingAs($user)->get(route('stock.fournisseurs.compte', $fournisseur))
            ->assertForbidden();
    }

    public function test_compte_fournisseur_computes_kpis_from_bc_achats_et_paiements(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();
        $article = Article::factory()->create(['quantite_stock' => 0]);
        $modePaiement = ModePaiement::create(['code' => 'especes', 'libelle' => 'Espèces', 'actif' => true]);
        Caisse::firstOrCreate(['type' => 'depenses_fournisseurs'], ['type' => 'depenses_fournisseurs', 'libelle' => 'Paiements fournisseurs']);

        $bonCommande = app(BonCommandeService::class)->creer([
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now(),
            'user_id' => $user->id,
            'lignes' => [['article_id' => $article->id, 'quantite_commandee' => 10, 'prix_unitaire_estime' => 500]],
        ]);
        $ligneId = $bonCommande->lignes->first()->id;

        $achat = app(AchatService::class)->creer([
            'fournisseur_id' => $fournisseur->id,
            'bon_commande_id' => $bonCommande->id,
            'date_achat' => now(),
            'user_id' => $user->id,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 10, 'prix_unitaire' => 500, 'bon_commande_ligne_id' => $ligneId],
            ],
        ]);

        app(PaiementFournisseurService::class)->enregistrer([
            'achat_id' => $achat->id,
            'date_paiement' => now(),
            'montant' => 2000,
            'mode_paiement_id' => $modePaiement->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('stock.fournisseurs.compte', $fournisseur));

        $response->assertOk();
        $kpis = $response->viewData('kpis');
        $this->assertEquals(5000, $kpis['total_achats']);
        $this->assertEquals(5000, $kpis['total_bons_commande']);
        $this->assertEquals(3000, $kpis['solde_du']);
        $this->assertEquals(2000, $kpis['deja_regle']);
        $this->assertEquals(3000, $kpis['reste_a_regler']);

        $mouvements = $response->viewData('mouvements');
        $this->assertCount(2, $mouvements);
    }

    public function test_compte_pdf_returns_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $fournisseur = Fournisseur::factory()->create();

        $this->actingAs($user)->get(route('stock.fournisseurs.compte.pdf', $fournisseur))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
