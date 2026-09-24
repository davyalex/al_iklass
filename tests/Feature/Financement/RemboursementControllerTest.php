<?php

namespace Tests\Feature\Financement;

use App\Models\Caisse;
use App\Models\Financement;
use App\Models\ModePaiement;
use App\Models\Preteur;
use App\Models\RemboursementFinancement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemboursementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        Caisse::firstOrCreate(['type' => 'emprunt'], ['type' => 'emprunt', 'libelle' => 'Emprunts / financements']);
    }

    private function financementDe(float $montantTotal): Financement
    {
        $preteur = Preteur::factory()->create();
        $user = User::factory()->create();

        return Financement::create([
            'reference' => 'FIN-TEST-'.uniqid(),
            'preteur_id' => $preteur->id,
            'preteur_nom' => $preteur->nom,
            'date_financement' => now(),
            'montant_total' => $montantTotal,
            'montant_rembourse' => 0,
            'montant_restant' => $montantTotal,
            'statut' => 'en_cours',
            'user_id' => $user->id,
        ]);
    }

    public function test_gestionnaire_stock_can_view_remboursements_index(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $this->financementDe(1000);

        $this->actingAs($user)->get(route('financements.remboursements.index'))
            ->assertOk()
            ->assertViewIs('financements.remboursements.index');
    }

    public function test_gestionnaire_stock_can_record_remboursement(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $financement = $this->financementDe(5000);
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $response = $this->actingAs($user)->postJson(route('financements.remboursements.store'), [
            'financement_id' => $financement->id,
            'montant' => 2000,
            'mode_paiement_id' => $mode->id,
        ]);

        $response->assertCreated();
        $this->assertEquals(3000, $financement->fresh()->montant_restant);
    }

    public function test_remboursement_exceeding_restant_returns_validation_error(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $financement = $this->financementDe(1000);
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $this->actingAs($user)->postJson(route('financements.remboursements.store'), [
            'financement_id' => $financement->id,
            'montant' => 1500,
            'mode_paiement_id' => $mode->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('montant');

        $this->assertEquals(0, $financement->fresh()->montant_rembourse);
    }

    public function test_chef_mecanicien_cannot_record_remboursement(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');
        $financement = $this->financementDe(1000);
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $this->actingAs($user)->postJson(route('financements.remboursements.store'), [
            'financement_id' => $financement->id,
            'montant' => 500,
            'mode_paiement_id' => $mode->id,
        ])->assertForbidden();
    }

    public function test_data_can_be_filtered_by_preteur(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $financementRecherche = $this->financementDe(5000);
        RemboursementFinancement::create([
            'financement_id' => $financementRecherche->id,
            'preteur_nom' => $financementRecherche->preteur_nom,
            'date_remboursement' => now(),
            'montant' => 1000,
            'mode_paiement_id' => $mode->id,
            'user_id' => $user->id,
        ]);

        $autreFinancement = $this->financementDe(5000);
        RemboursementFinancement::create([
            'financement_id' => $autreFinancement->id,
            'preteur_nom' => $autreFinancement->preteur_nom,
            'date_remboursement' => now(),
            'montant' => 1000,
            'mode_paiement_id' => $mode->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('financements.remboursements.data', [
            'preteur_id' => $financementRecherche->preteur_id,
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_gestionnaire_stock_can_export_remboursements_excel_and_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);
        $financement = $this->financementDe(5000);
        RemboursementFinancement::create([
            'financement_id' => $financement->id,
            'preteur_nom' => $financement->preteur_nom,
            'date_remboursement' => now(),
            'montant' => 1000,
            'mode_paiement_id' => $mode->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->get(route('financements.remboursements.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($user)->get(route('financements.remboursements.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_chef_mecanicien_cannot_export_remboursements(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->get(route('financements.remboursements.export.excel'))->assertForbidden();
        $this->actingAs($user)->get(route('financements.remboursements.export.pdf'))->assertForbidden();
    }
}
