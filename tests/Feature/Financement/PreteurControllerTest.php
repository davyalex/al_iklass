<?php

namespace Tests\Feature\Financement;

use App\Models\Caisse;
use App\Models\ModePaiement;
use App\Models\Preteur;
use App\Models\TypePreteur;
use App\Models\User;
use App\Services\Financement\FinancementService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreteurControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        Caisse::firstOrCreate(['type' => 'emprunt'], ['type' => 'emprunt', 'libelle' => 'Emprunts / financements']);
    }

    private function typeBanque(): TypePreteur
    {
        return TypePreteur::firstOrCreate(['code' => 'banque'], ['code' => 'banque', 'libelle' => 'Banque']);
    }

    public function test_gestionnaire_stock_can_view_preteurs_index(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Preteur::factory()->create();

        $this->actingAs($user)->get(route('financements.preteurs.index'))
            ->assertOk()
            ->assertViewIs('financements.preteurs.index');
    }

    public function test_index_can_be_filtered_by_nom_type_et_statut(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $banque = $this->typeBanque();
        $personne = TypePreteur::firstOrCreate(['code' => 'personne'], ['code' => 'personne', 'libelle' => 'Personne']);

        $banqueAtlantique = Preteur::factory()->create([
            'nom' => 'Banque Atlantique',
            'type_preteur_id' => $banque->id,
            'type_preteur_code' => $banque->code,
            'type_preteur_libelle' => $banque->libelle,
            'actif' => true,
        ]);
        Preteur::factory()->create([
            'nom' => 'Jean Kouassi',
            'type_preteur_id' => $personne->id,
            'type_preteur_code' => $personne->code,
            'type_preteur_libelle' => $personne->libelle,
            'actif' => false,
        ]);

        $parNom = $this->actingAs($user)->get(route('financements.preteurs.index', ['nom' => 'Atlantique']));
        $parNom->assertOk();
        $this->assertCount(1, $parNom->viewData('preteurs'));
        $this->assertSame($banqueAtlantique->id, $parNom->viewData('preteurs')->first()->id);

        $parType = $this->actingAs($user)->get(route('financements.preteurs.index', ['type_preteur_id' => $personne->id]));
        $parType->assertOk();
        $this->assertCount(1, $parType->viewData('preteurs'));
        $this->assertSame('Jean Kouassi', $parType->viewData('preteurs')->first()->nom);

        $parStatut = $this->actingAs($user)->get(route('financements.preteurs.index', ['actif' => '1']));
        $parStatut->assertOk();
        $this->assertCount(1, $parStatut->viewData('preteurs'));
        $this->assertSame($banqueAtlantique->id, $parStatut->viewData('preteurs')->first()->id);
    }

    public function test_gestionnaire_stock_can_create_and_update_preteur(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $type = $this->typeBanque();

        $create = $this->actingAs($user)->postJson(route('financements.preteurs.store'), [
            'nom' => 'Banque Atlantique',
            'type_preteur_id' => $type->id,
            'telephone' => '0102030405',
        ]);
        $create->assertCreated();

        $preteur = Preteur::where('nom', 'Banque Atlantique')->firstOrFail();
        $this->assertSame('banque', $preteur->type_preteur_code);

        $this->actingAs($user)->putJson(route('financements.preteurs.update', $preteur), [
            'nom' => 'Banque Atlantique',
            'type_preteur_id' => $type->id,
            'telephone' => '0709080706',
        ])->assertOk();

        $this->assertSame('0709080706', $preteur->fresh()->telephone);
    }

    public function test_chef_mecanicien_cannot_manage_preteurs(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');
        $type = $this->typeBanque();

        $this->actingAs($user)->postJson(route('financements.preteurs.store'), [
            'nom' => 'Test',
            'type_preteur_id' => $type->id,
        ])->assertForbidden();
    }

    public function test_destroy_soft_deletes_preteur(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $preteur = Preteur::factory()->create();

        $this->actingAs($user)->deleteJson(route('financements.preteurs.destroy', $preteur))
            ->assertOk();

        $this->assertSoftDeleted($preteur);
    }

    public function test_gestionnaire_stock_can_view_compte_preteur(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $preteur = Preteur::factory()->create();

        $this->actingAs($user)->get(route('financements.preteurs.compte', $preteur))
            ->assertOk()
            ->assertViewIs('financements.preteurs.compte');
    }

    public function test_chef_mecanicien_cannot_view_compte_preteur(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');
        $preteur = Preteur::factory()->create();

        $this->actingAs($user)->get(route('financements.preteurs.compte', $preteur))
            ->assertForbidden();
    }

    public function test_compte_preteur_computes_kpis_from_financements_et_remboursements(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $preteur = Preteur::factory()->create();
        $mode = ModePaiement::firstOrCreate(['code' => 'especes'], ['code' => 'especes', 'libelle' => 'Espèces']);

        $financement = app(FinancementService::class)->declarer([
            'preteur_id' => $preteur->id,
            'montant_total' => 5000,
            'user_id' => $user->id,
        ]);

        app(FinancementService::class)->rembourser([
            'financement_id' => $financement->id,
            'montant' => 2000,
            'mode_paiement_id' => $mode->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('financements.preteurs.compte', $preteur));

        $response->assertOk();
        $kpis = $response->viewData('kpis');
        $this->assertEquals(5000, $kpis['total_emprunte']);
        $this->assertEquals(2000, $kpis['deja_rembourse']);
        $this->assertEquals(3000, $kpis['reste_a_rembourser']);

        $mouvements = $response->viewData('mouvements');
        $this->assertCount(2, $mouvements);
    }

    public function test_compte_pdf_returns_pdf(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $preteur = Preteur::factory()->create();

        $this->actingAs($user)->get(route('financements.preteurs.compte.pdf', $preteur))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
