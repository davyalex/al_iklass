<?php

namespace Tests\Feature\Financement;

use App\Models\Caisse;
use App\Models\Financement;
use App\Models\Preteur;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        Caisse::firstOrCreate(['type' => 'emprunt'], ['type' => 'emprunt', 'libelle' => 'Emprunts / financements']);
    }

    public function test_gestionnaire_stock_can_view_financements_index_and_data(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($user)->get(route('financements.financements.index'))->assertOk();
        $this->actingAs($user)->getJson(route('financements.financements.data'))
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    }

    public function test_gestionnaire_stock_can_declare_financement_via_controller(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $preteur = Preteur::factory()->create();

        $response = $this->actingAs($user)->postJson(route('financements.financements.store'), [
            'preteur_id' => $preteur->id,
            'date_financement' => now()->format('Y-m-d'),
            'montant_total' => 500000,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('financements', [
            'preteur_id' => $preteur->id,
            'montant_total' => 500000,
            'statut' => 'en_cours',
        ]);
    }

    public function test_chef_mecanicien_cannot_declare_financement(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');
        $preteur = Preteur::factory()->create();

        $this->actingAs($user)->postJson(route('financements.financements.store'), [
            'preteur_id' => $preteur->id,
            'montant_total' => 1000,
        ])->assertForbidden();
    }

    public function test_store_rejects_zero_montant(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $preteur = Preteur::factory()->create();

        $this->actingAs($user)->postJson(route('financements.financements.store'), [
            'preteur_id' => $preteur->id,
            'montant_total' => 0,
        ])->assertUnprocessable();
    }

    public function test_data_can_be_filtered_by_preteur_and_statut(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $preteurRecherche = Preteur::factory()->create();

        Financement::factory()->create([
            'preteur_id' => $preteurRecherche->id,
            'statut' => 'en_cours',
        ]);
        Financement::factory()->create([
            'statut' => 'solde',
        ]);

        $response = $this->actingAs($user)->getJson(route('financements.financements.data', [
            'preteur_id' => $preteurRecherche->id,
            'statut' => 'en_cours',
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_data_can_be_filtered_by_date_range(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        Financement::factory()->create(['date_financement' => now()->subDays(10)]);
        Financement::factory()->create(['date_financement' => now()]);

        $response = $this->actingAs($user)->getJson(route('financements.financements.data', [
            'date_debut' => now()->subDay()->format('Y-m-d'),
            'date_fin' => now()->addDay()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_gestionnaire_stock_can_export_financements_excel_and_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Financement::factory()->create();

        $this->actingAs($user)->get(route('financements.financements.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($user)->get(route('financements.financements.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_chef_mecanicien_cannot_export_financements(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->get(route('financements.financements.export.excel'))->assertForbidden();
        $this->actingAs($user)->get(route('financements.financements.export.pdf'))->assertForbidden();
    }

    public function test_show_returns_financement_with_remboursements(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $financement = Financement::factory()->create();

        $this->actingAs($user)->getJson(route('financements.financements.show', $financement))
            ->assertOk()
            ->assertJsonStructure(['id', 'preteur_nom', 'remboursements']);
    }

    public function test_kpis_endpoint_reacts_to_filters(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $preteurCible = Preteur::factory()->create();

        Financement::factory()->create([
            'preteur_id' => $preteurCible->id,
            'date_financement' => now(),
            'montant_total' => 1000,
            'montant_rembourse' => 1000,
            'montant_restant' => 0,
        ]);
        Financement::factory()->create([
            'date_financement' => now(),
            'montant_total' => 9000,
            'montant_rembourse' => 0,
            'montant_restant' => 9000,
        ]);

        $response = $this->actingAs($user)->getJson(route('financements.financements.kpis', [
            'preteur_id' => $preteurCible->id,
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('count'));
        $this->assertEquals(1000, $response->json('total'));
        $this->assertEquals(1000, $response->json('rembourse'));
        $this->assertEquals(0, $response->json('restant'));
    }
}
