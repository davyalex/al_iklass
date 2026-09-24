<?php

namespace Tests\Feature\Flotte;

use App\Models\Article;
use App\Models\Caisse;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Services\Stock\SortieStockService;
use Database\Seeders\ParametreSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StatutVehiculeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiculeRapportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(StatutVehiculeSeeder::class);
        $this->seed(ParametreSeeder::class);

        Caisse::firstOrCreate(['type' => 'ventes_externes'], ['type' => 'ventes_externes', 'libelle' => 'Ventes externes']);
    }

    public function test_admin_peut_voir_le_rapport_dun_vehicule(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();

        $response = $this->actingAs($admin)->get(route('flotte.vehicules.rapport', $vehicule));

        $response->assertOk()->assertSee($vehicule->code);
    }

    public function test_gestionnaire_ne_peut_pas_voir_le_rapport_dun_vehicule_non_affecte(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $vehicule = Vehicule::factory()->create(['gestionnaire_id' => null]);

        $this->actingAs($gestionnaire)->get(route('flotte.vehicules.rapport', $vehicule))->assertForbidden();
    }

    public function test_chef_mecanicien_na_plus_acces_au_rapport_vehicule(): void
    {
        // N'a plus accès à la Flotte : son seul menu est Interventions.
        $mecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $vehicule = Vehicule::factory()->create();

        $this->actingAs($mecanicien)->get(route('flotte.vehicules.rapport', $vehicule))->assertForbidden();
    }

    public function test_le_rapport_expose_uniquement_lhistorique_de_ce_vehicule(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutDepannage->id]);
        $autreVehicule = Vehicule::factory()->create(['statut_id' => $statutDepannage->id]);

        $this->actingAs($admin)->postJson(route('flotte.vehicules.remise-circulation', $vehicule), [
            'rapport' => 'Freins changés sur ce véhicule.',
        ])->assertOk();

        $this->actingAs($admin)->postJson(route('flotte.vehicules.remise-circulation', $autreVehicule), [
            'rapport' => 'Vidange sur un autre véhicule.',
        ])->assertOk();

        $response = $this->actingAs($admin)->getJson(route('flotte.vehicules.rapport.statuts', $vehicule));

        $response->assertOk();
        $this->assertSame(2, $response->json('recordsFiltered')); // création + remise en circulation
        $this->assertSame('Freins changés sur ce véhicule.', $response->json('data.0.commentaire'));
    }

    public function test_le_rapport_expose_uniquement_les_sorties_de_ce_vehicule(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $article = Article::factory()->create(['quantite_stock' => 10]);
        $vehicule = Vehicule::factory()->create();
        $autreVehicule = Vehicule::factory()->create();

        app(SortieStockService::class)->creerInterne([
            'motif' => 'Pour ce véhicule',
            'vehicule_id' => $vehicule->id,
            'user_id' => $admin->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 3]],
        ]);

        app(SortieStockService::class)->creerInterne([
            'motif' => 'Pour un autre véhicule',
            'vehicule_id' => $autreVehicule->id,
            'user_id' => $admin->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.vehicules.rapport.sorties', $vehicule));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
        $this->assertSame(3, $response->json('data.0.quantite_totale'));
    }

    public function test_les_kpis_et_rapports_dintervention_sont_corrects(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutDepannage->id]);
        $article = Article::factory()->create(['quantite_stock' => 10]);

        $this->actingAs($admin)->postJson(route('flotte.vehicules.remise-circulation', $vehicule), [
            'rapport' => 'Réparation moteur effectuée.',
        ])->assertOk();

        app(SortieStockService::class)->creerInterne([
            'motif' => 'Pièce',
            'vehicule_id' => $vehicule->id,
            'user_id' => $admin->id,
            'lignes' => [['article_id' => $article->id, 'quantite' => 1]],
        ]);

        $response = $this->actingAs($admin)->get(route('flotte.vehicules.rapport', $vehicule));

        $response->assertOk();
        $kpis = $response->viewData('kpis');
        $this->assertSame(2, $kpis['changements_statut']); // création (depannage) + remise en circulation
        $this->assertSame(1, $kpis['sorties_pieces']);
        $this->assertSame(1, $kpis['depannages_traites']);

        $rapportsIntervention = $response->viewData('rapportsIntervention');
        $this->assertCount(1, $rapportsIntervention);
        $this->assertSame('Réparation moteur effectuée.', $rapportsIntervention->first()->commentaire);
    }
}
