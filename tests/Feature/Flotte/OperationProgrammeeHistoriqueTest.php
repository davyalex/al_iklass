<?php

namespace Tests\Feature\Flotte;

use App\Models\OperationProgrammee;
use App\Models\TypeOperation;
use App\Models\User;
use App\Models\Vehicule;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypeOperationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationProgrammeeHistoriqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TypeOperationSeeder::class);
    }

    private function creerRealisee(Vehicule $vehicule, TypeOperation $type, string $dateRealisation, User $auteur): OperationProgrammee
    {
        return OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $type->id, 'type_operation_code' => $type->code, 'type_operation_libelle' => $type->libelle,
            'date_echeance' => $dateRealisation, 'rappel_jours' => 15, 'periodicite_jours' => $type->periodicite_jours,
            'statut' => 'realisee', 'date_realisation' => $dateRealisation, 'realise_par_id' => $auteur->id, 'user_id' => $auteur->id,
        ]);
    }

    public function test_admin_peut_afficher_la_page_historique(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('flotte.operations.historique.index'))->assertOk();
    }

    public function test_role_sans_droit_est_rejete(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->get(route('flotte.operations.historique.index'))->assertForbidden();
    }

    public function test_data_ne_liste_que_les_operations_realisees(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $this->creerRealisee($vehicule, $vidange, '2026-01-10', $admin);

        OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id, 'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => now()->addDays(30), 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.operations.historique.data'));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsTotal'));
    }

    public function test_data_filtre_par_vehicule_et_par_type(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehiculeA = Vehicule::factory()->create();
        $vehiculeB = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();
        $assurance = TypeOperation::where('code', 'assurance')->firstOrFail();

        $this->creerRealisee($vehiculeA, $vidange, '2026-01-10', $admin);
        $this->creerRealisee($vehiculeB, $assurance, '2026-01-15', $admin);

        $response = $this->actingAs($admin)->getJson(route('flotte.operations.historique.data', [
            'vehicule_id' => $vehiculeA->id,
        ]));
        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));

        $response = $this->actingAs($admin)->getJson(route('flotte.operations.historique.data', [
            'type_operation_id' => $assurance->id,
        ]));
        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_data_filtre_par_periode(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $this->creerRealisee($vehicule, $vidange, '2026-01-10', $admin);
        $this->creerRealisee($vehicule, $vidange, '2026-03-10', $admin);

        $response = $this->actingAs($admin)->getJson(route('flotte.operations.historique.data', [
            'date_debut' => '2026-02-01',
            'date_fin' => '2026-04-01',
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_admin_peut_exporter_excel_et_pdf(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $this->creerRealisee($vehicule, TypeOperation::where('code', 'vidange')->firstOrFail(), '2026-01-10', $admin);

        $this->actingAs($admin)->get(route('flotte.operations.historique.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($admin)->get(route('flotte.operations.historique.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
