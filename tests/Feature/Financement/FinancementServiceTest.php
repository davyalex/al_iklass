<?php

namespace Tests\Feature\Financement;

use App\Exceptions\Financement\RemboursementExcessifException;
use App\Models\Caisse;
use App\Models\Financement;
use App\Models\ModePaiement;
use App\Models\MouvementCaisse;
use App\Models\Preteur;
use App\Models\RemboursementFinancement;
use App\Models\TypePreteur;
use App\Models\User;
use App\Services\Financement\FinancementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancementServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinancementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FinancementService::class);

        Caisse::firstOrCreate(['type' => 'emprunt'], ['type' => 'emprunt', 'libelle' => 'Emprunts / financements']);
    }

    private function preteur(): Preteur
    {
        $type = TypePreteur::firstOrCreate(['code' => 'banque'], ['code' => 'banque', 'libelle' => 'Banque']);

        return Preteur::create([
            'nom' => 'Banque Atlantique',
            'type_preteur_id' => $type->id,
            'type_preteur_code' => $type->code,
            'type_preteur_libelle' => $type->libelle,
            'actif' => true,
        ]);
    }

    public function test_declarer_creates_financement_en_cours_and_writes_mouvement_caisse_entree(): void
    {
        $preteur = $this->preteur();
        $user = User::factory()->create();

        $financement = $this->service->declarer([
            'preteur_id' => $preteur->id,
            'montant_total' => 500000,
            'user_id' => $user->id,
        ]);

        $this->assertSame('en_cours', $financement->statut);
        $this->assertEquals(0, $financement->montant_rembourse);
        $this->assertEquals(500000, $financement->montant_restant);
        $this->assertSame('Banque Atlantique', $financement->preteur_nom);
        $this->assertNotNull($financement->reference);

        $mouvement = MouvementCaisse::where('origine_type', Financement::class)
            ->where('origine_id', $financement->id)
            ->first();

        $this->assertNotNull($mouvement);
        $this->assertSame('entree', $mouvement->sens);
        $this->assertEquals(500000, $mouvement->montant);
        $this->assertSame('emprunt', $mouvement->caisse->type);
    }

    public function test_declarer_generates_reference_when_absent(): void
    {
        $preteur = $this->preteur();
        $user = User::factory()->create();

        $financement = $this->service->declarer([
            'preteur_id' => $preteur->id,
            'montant_total' => 100000,
            'user_id' => $user->id,
        ]);

        $this->assertMatchesRegularExpression('/^FIN-\d{4}-\d{4}$/', $financement->reference);
    }

    public function test_rembourser_partiel_updates_montant_restant_and_statut(): void
    {
        $preteur = $this->preteur();
        $user = User::factory()->create();
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $financement = $this->service->declarer([
            'preteur_id' => $preteur->id,
            'montant_total' => 10000,
            'user_id' => $user->id,
        ]);

        $this->service->rembourser([
            'financement_id' => $financement->id,
            'montant' => 4000,
            'mode_paiement_id' => $mode->id,
            'user_id' => $user->id,
        ]);

        $financement->refresh();

        $this->assertEquals(4000, $financement->montant_rembourse);
        $this->assertEquals(6000, $financement->montant_restant);
        $this->assertSame('en_cours', $financement->statut);
    }

    public function test_rembourser_total_sets_statut_solde(): void
    {
        $preteur = $this->preteur();
        $user = User::factory()->create();
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $financement = $this->service->declarer([
            'preteur_id' => $preteur->id,
            'montant_total' => 5000,
            'user_id' => $user->id,
        ]);

        $this->service->rembourser([
            'financement_id' => $financement->id,
            'montant' => 5000,
            'mode_paiement_id' => $mode->id,
            'user_id' => $user->id,
        ]);

        $financement->refresh();

        $this->assertEquals(0, $financement->montant_restant);
        $this->assertSame('solde', $financement->statut);
    }

    public function test_rembourser_exceeding_montant_restant_is_blocked(): void
    {
        $preteur = $this->preteur();
        $user = User::factory()->create();
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $financement = $this->service->declarer([
            'preteur_id' => $preteur->id,
            'montant_total' => 3000,
            'user_id' => $user->id,
        ]);

        $this->expectException(RemboursementExcessifException::class);

        try {
            $this->service->rembourser([
                'financement_id' => $financement->id,
                'montant' => 3500,
                'mode_paiement_id' => $mode->id,
                'user_id' => $user->id,
            ]);
        } finally {
            $financement->refresh();
            $this->assertEquals(0, $financement->montant_rembourse);
            $this->assertEquals(3000, $financement->montant_restant);
        }
    }

    public function test_rembourser_writes_mouvement_caisse_sortie_on_emprunt(): void
    {
        $preteur = $this->preteur();
        $user = User::factory()->create();
        $mode = ModePaiement::firstOrCreate(['code' => 'wave'], ['code' => 'wave', 'libelle' => 'Wave']);

        $financement = $this->service->declarer([
            'preteur_id' => $preteur->id,
            'montant_total' => 2000,
            'user_id' => $user->id,
        ]);

        $remboursement = $this->service->rembourser([
            'financement_id' => $financement->id,
            'montant' => 2000,
            'mode_paiement_id' => $mode->id,
            'user_id' => $user->id,
        ]);

        $mouvement = MouvementCaisse::where('origine_type', RemboursementFinancement::class)
            ->where('origine_id', $remboursement->id)
            ->first();

        $this->assertNotNull($mouvement);
        $this->assertSame('sortie', $mouvement->sens);
        $this->assertEquals(2000, $mouvement->montant);
        $this->assertSame('emprunt', $mouvement->caisse->type);
        $this->assertSame('Banque Atlantique', $remboursement->preteur_nom);
    }

    public function test_declarer_et_rembourser_ecrivent_chacun_une_entree_dans_le_journal_audit(): void
    {
        $preteur = $this->preteur();
        $user = User::factory()->create();
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $financement = $this->service->declarer([
            'preteur_id' => $preteur->id,
            'montant_total' => 10000,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Financement::class,
            'subject_id' => $financement->id,
            'description' => "Emprunt {$financement->reference} déclaré auprès de « Banque Atlantique » pour 10000 FCFA.",
        ]);

        $this->service->rembourser([
            'financement_id' => $financement->id,
            'montant' => 4000,
            'mode_paiement_id' => $mode->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Financement::class,
            'subject_id' => $financement->id,
            'description' => "Remboursement de 4000 FCFA enregistré pour l'emprunt {$financement->reference} (Banque Atlantique).",
        ]);
    }
}
