<?php

namespace Tests\Feature\Stock;

use App\Exceptions\Stock\TropPercuException;
use App\Models\Achat;
use App\Models\Caisse;
use App\Models\Fournisseur;
use App\Models\ModePaiement;
use App\Models\MouvementCaisse;
use App\Models\User;
use App\Services\Stock\PaiementFournisseurService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaiementFournisseurServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaiementFournisseurService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PaiementFournisseurService::class);

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

    public function test_partial_payment_updates_montant_restant_and_statut(): void
    {
        $achat = $this->achatDe(10000);
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);
        $user = User::factory()->create();

        $this->service->enregistrer([
            'achat_id' => $achat->id,
            'montant' => 4000,
            'mode_paiement_id' => $mode->id,
            'user_id' => $user->id,
        ]);

        $achat->refresh();

        $this->assertEquals(4000, $achat->montant_paye);
        $this->assertEquals(6000, $achat->montant_restant);
        $this->assertSame('partiel', $achat->statut_paiement);
    }

    public function test_full_payment_sets_statut_comptant(): void
    {
        $achat = $this->achatDe(5000);
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);
        $user = User::factory()->create();

        $this->service->enregistrer([
            'achat_id' => $achat->id,
            'montant' => 5000,
            'mode_paiement_id' => $mode->id,
            'user_id' => $user->id,
        ]);

        $achat->refresh();

        $this->assertEquals(0, $achat->montant_restant);
        $this->assertSame('comptant', $achat->statut_paiement);
    }

    public function test_payment_exceeding_montant_restant_is_blocked(): void
    {
        $achat = $this->achatDe(3000);
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);
        $user = User::factory()->create();

        $this->expectException(TropPercuException::class);

        try {
            $this->service->enregistrer([
                'achat_id' => $achat->id,
                'montant' => 3500,
                'mode_paiement_id' => $mode->id,
                'user_id' => $user->id,
            ]);
        } finally {
            $achat->refresh();
            $this->assertEquals(0, $achat->montant_paye);
            $this->assertEquals(3000, $achat->montant_restant);
        }
    }

    public function test_payment_writes_mouvement_caisse_on_depenses_fournisseurs(): void
    {
        $achat = $this->achatDe(2000);
        $mode = ModePaiement::firstOrCreate(['code' => 'wave'], ['code' => 'wave', 'libelle' => 'Wave']);
        $user = User::factory()->create();

        $paiement = $this->service->enregistrer([
            'achat_id' => $achat->id,
            'montant' => 2000,
            'mode_paiement_id' => $mode->id,
            'user_id' => $user->id,
        ]);

        $mouvement = MouvementCaisse::where('origine_type', \App\Models\PaiementFournisseur::class)
            ->where('origine_id', $paiement->id)
            ->first();

        $this->assertNotNull($mouvement);
        $this->assertSame('sortie', $mouvement->sens);
        $this->assertEquals(2000, $mouvement->montant);
        $this->assertSame('depenses_fournisseurs', $mouvement->caisse->type);
    }
}
