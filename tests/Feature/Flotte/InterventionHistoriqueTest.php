<?php

namespace Tests\Feature\Flotte;

use App\Models\Intervention;
use App\Models\TypePanne;
use App\Models\User;
use App\Models\Vehicule;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypePanneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterventionHistoriqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TypePanneSeeder::class);
    }

    private function creerTerminee(Vehicule $vehicule, ?TypePanne $type, string $dateFin, User $auteur): Intervention
    {
        return Intervention::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_panne_id' => $type?->id, 'type_panne_libelle' => $type?->libelle,
            'description' => 'Panne réparée', 'statut' => 'terminee',
            'date_debut' => now()->subDays(5), 'date_fin' => $dateFin,
            'rapport' => 'Réparée', 'declaree_par_id' => $auteur->id, 'cloturee_par_id' => $auteur->id,
        ]);
    }

    public function test_admin_peut_afficher_la_page_historique(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('flotte.interventions.historique.index'))->assertOk();
    }

    public function test_role_sans_droit_est_rejete(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->get(route('flotte.interventions.historique.index'))->assertForbidden();
    }

    public function test_data_ne_liste_que_les_interventions_terminees(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $moteur = TypePanne::where('code', 'moteur')->firstOrFail();

        $this->creerTerminee($vehicule, $moteur, '2026-01-10', $admin);

        Intervention::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'description' => 'En cours', 'statut' => 'en_cours', 'date_debut' => now(),
            'declaree_par_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.interventions.historique.data'));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsTotal'));
    }

    public function test_data_filtre_par_vehicule_et_type(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehiculeA = Vehicule::factory()->create();
        $vehiculeB = Vehicule::factory()->create();
        $moteur = TypePanne::where('code', 'moteur')->firstOrFail();
        $freinage = TypePanne::where('code', 'freinage')->firstOrFail();

        $this->creerTerminee($vehiculeA, $moteur, '2026-01-10', $admin);
        $this->creerTerminee($vehiculeB, $freinage, '2026-01-15', $admin);

        $response = $this->actingAs($admin)->getJson(route('flotte.interventions.historique.data', [
            'vehicule_id' => $vehiculeA->id,
        ]));
        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));

        $response = $this->actingAs($admin)->getJson(route('flotte.interventions.historique.data', [
            'type_panne_id' => $freinage->id,
        ]));
        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_admin_peut_exporter_excel_et_pdf(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $this->creerTerminee($vehicule, TypePanne::where('code', 'moteur')->firstOrFail(), '2026-01-10', $admin);

        $this->actingAs($admin)->get(route('flotte.interventions.historique.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($admin)->get(route('flotte.interventions.historique.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
