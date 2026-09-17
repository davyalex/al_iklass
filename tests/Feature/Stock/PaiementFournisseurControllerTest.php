<?php

namespace Tests\Feature\Stock;

use App\Models\Achat;
use App\Models\Caisse;
use App\Models\Fournisseur;
use App\Models\ModePaiement;
use App\Models\PaiementFournisseur;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaiementFournisseurControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        Caisse::firstOrCreate(['type' => 'depenses_fournisseurs'], ['type' => 'depenses_fournisseurs', 'libelle' => 'Paiements fournisseurs']);
    }

    private function achatDe(float $montantTotal): Achat
    {
        $fournisseur = Fournisseur::factory()->create();
        $user = User::factory()->create();

        return Achat::create([
            'fournisseur_id' => $fournisseur->id,
            'fournisseur_nom' => $fournisseur->nom,
            'date_achat' => now(),
            'montant_total' => $montantTotal,
            'montant_paye' => 0,
            'montant_restant' => $montantTotal,
            'statut_paiement' => 'credit',
            'user_id' => $user->id,
        ]);
    }

    public function test_gestionnaire_stock_can_record_payment(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $achat = $this->achatDe(5000);
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $response = $this->actingAs($user)->postJson(route('stock.paiements.store'), [
            'achat_id' => $achat->id,
            'montant' => 2000,
            'mode_paiement_id' => $mode->id,
        ]);

        $response->assertCreated();
        $this->assertEquals(3000, $achat->fresh()->montant_restant);
    }

    public function test_payment_exceeding_restant_returns_validation_error(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $achat = $this->achatDe(1000);
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $this->actingAs($user)->postJson(route('stock.paiements.store'), [
            'achat_id' => $achat->id,
            'montant' => 1500,
            'mode_paiement_id' => $mode->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('montant');

        $this->assertEquals(0, $achat->fresh()->montant_paye);
    }

    public function test_chef_mecanicien_cannot_record_payment(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');
        $achat = $this->achatDe(1000);
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $this->actingAs($user)->postJson(route('stock.paiements.store'), [
            'achat_id' => $achat->id,
            'montant' => 500,
            'mode_paiement_id' => $mode->id,
        ])->assertForbidden();
    }

    public function test_data_can_be_filtered_by_fournisseur(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $achatRecherche = $this->achatDe(5000);
        PaiementFournisseur::create([
            'achat_id' => $achatRecherche->id,
            'date_paiement' => now(),
            'montant' => 1000,
            'mode_paiement_id' => $mode->id,
            'fournisseur_nom' => $achatRecherche->fournisseur_nom,
            'user_id' => $user->id,
        ]);

        $autreAchat = $this->achatDe(5000);
        PaiementFournisseur::create([
            'achat_id' => $autreAchat->id,
            'date_paiement' => now(),
            'montant' => 1000,
            'mode_paiement_id' => $mode->id,
            'fournisseur_nom' => $autreAchat->fournisseur_nom,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('stock.paiements.data', [
            'fournisseur_id' => $achatRecherche->fournisseur_id,
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_gestionnaire_stock_can_export_paiements_excel_and_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);
        $achat = $this->achatDe(5000);
        PaiementFournisseur::create([
            'achat_id' => $achat->id,
            'date_paiement' => now(),
            'montant' => 1000,
            'mode_paiement_id' => $mode->id,
            'fournisseur_nom' => $achat->fournisseur_nom,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->get(route('stock.paiements.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($user)->get(route('stock.paiements.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_chef_mecanicien_cannot_export_paiements(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->get(route('stock.paiements.export.excel'))->assertForbidden();
        $this->actingAs($user)->get(route('stock.paiements.export.pdf'))->assertForbidden();
    }
}
