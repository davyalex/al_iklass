<?php

namespace Tests\Feature\Stock;

use App\Models\Achat;
use App\Models\Caisse;
use App\Models\Fournisseur;
use App\Models\ModePaiement;
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
}
