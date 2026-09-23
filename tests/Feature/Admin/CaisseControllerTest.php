<?php

namespace Tests\Feature\Admin;

use App\Models\Caisse;
use App\Models\MouvementCaisse;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StockReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaisseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(StockReferenceSeeder::class);
    }

    private function creerMouvement(string $typeCaisse, string $sens, float $montant, ?User $auteur = null): MouvementCaisse
    {
        $auteur ??= User::factory()->create();

        return MouvementCaisse::create([
            'caisse_id' => Caisse::where('type', $typeCaisse)->firstOrFail()->id,
            'sens' => $sens,
            'montant' => $montant,
            'motif' => 'Test',
            'user_id' => $auteur->id,
            'date_mouvement' => now(),
        ]);
    }

    public function test_admin_peut_afficher_la_page(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.caisses.index'))->assertOk();
        $this->actingAs($admin)->getJson(route('admin.caisses.data'))
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal']);
    }

    public function test_gestionnaire_ne_peut_pas_afficher_la_page(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->get(route('admin.caisses.index'))->assertForbidden();
    }

    public function test_le_solde_dune_caisse_est_les_entrees_moins_les_sorties(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->creerMouvement('versements', 'entree', 20000);
        $this->creerMouvement('versements', 'entree', 5000);
        $this->creerMouvement('versements', 'sortie', 8000);

        $response = $this->actingAs($admin)->get(route('admin.caisses.index'));

        $response->assertOk()->assertSee('17 000');
    }

    public function test_data_liste_les_mouvements_de_toutes_les_caisses(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->creerMouvement('versements', 'entree', 20000);
        $this->creerMouvement('ventes_externes', 'entree', 15000);

        $response = $this->actingAs($admin)->getJson(route('admin.caisses.data'));

        $response->assertOk();
        $this->assertSame(2, $response->json('recordsTotal'));
    }

    public function test_data_filtre_par_caisse(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->creerMouvement('versements', 'entree', 20000);
        $this->creerMouvement('ventes_externes', 'entree', 15000);

        $response = $this->actingAs($admin)->getJson(route('admin.caisses.data', [
            'caisse_id' => Caisse::where('type', 'versements')->firstOrFail()->id,
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_data_filtre_par_sens(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->creerMouvement('depenses_fournisseurs', 'sortie', 12000);
        $this->creerMouvement('versements', 'entree', 20000);

        $response = $this->actingAs($admin)->getJson(route('admin.caisses.data', ['sens' => 'sortie']));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_admin_peut_exporter_excel_et_pdf(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $this->creerMouvement('versements', 'entree', 20000);

        $this->actingAs($admin)->get(route('admin.caisses.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($admin)->get(route('admin.caisses.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_gestionnaire_ne_peut_pas_exporter(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->get(route('admin.caisses.export.excel'))->assertForbidden();
        $this->actingAs($gestionnaire)->get(route('admin.caisses.export.pdf'))->assertForbidden();
    }
}
