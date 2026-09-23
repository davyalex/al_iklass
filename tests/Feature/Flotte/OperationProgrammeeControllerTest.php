<?php

namespace Tests\Feature\Flotte;

use App\Models\OperationProgrammee;
use App\Models\TypeOperation;
use App\Models\User;
use App\Models\Vehicule;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypeOperationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OperationProgrammeeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TypeOperationSeeder::class);
    }

    public function test_admin_peut_afficher_la_page(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('flotte.operations.index'))->assertOk();
    }

    public function test_role_sans_droit_est_rejete(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->get(route('flotte.operations.index'))->assertForbidden();
    }

    public function test_admin_peut_planifier_une_operation(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $response = $this->actingAs($admin)->postJson(route('flotte.operations.store'), [
            'vehicule_id' => $vehicule->id,
            'type_operation_id' => $vidange->id,
            'date_echeance' => now()->addDays(30)->toDateString(),
            'rappel_jours' => 15,
            'periodicite_jours' => 90,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('operations_programmees', [
            'vehicule_id' => $vehicule->id,
            'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id,
            'type_operation_code' => 'vidange',
            'statut' => 'planifiee',
            'rappel_jours' => 15,
        ]);
    }

    public function test_planifier_rejette_si_une_echeance_du_meme_type_est_deja_active(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id, 'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => now()->addDays(30), 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->postJson(route('flotte.operations.store'), [
            'vehicule_id' => $vehicule->id,
            'type_operation_id' => $vidange->id,
            'date_echeance' => now()->addDays(60)->toDateString(),
            'rappel_jours' => 15,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('operations_programmees', 1);
    }

    public function test_gestionnaire_stock_peut_planifier_mais_pas_realiser(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $this->actingAs($gestionnaireStock)->postJson(route('flotte.operations.store'), [
            'vehicule_id' => $vehicule->id,
            'type_operation_id' => $vidange->id,
            'date_echeance' => now()->addDays(30)->toDateString(),
            'rappel_jours' => 15,
        ])->assertCreated();

        $operation = OperationProgrammee::firstOrFail();

        $this->actingAs($gestionnaireStock)->postJson(route('flotte.operations.realiser', $operation))
            ->assertForbidden();
    }

    public function test_chef_mecanicien_peut_realiser_mais_pas_planifier(): void
    {
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $operation = OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id, 'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => now()->addDays(30), 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => $admin->id,
        ]);

        $this->actingAs($chefMecanicien)->postJson(route('flotte.operations.store'), [
            'vehicule_id' => $vehicule->id,
            'type_operation_id' => $vidange->id,
            'date_echeance' => now()->addDays(200)->toDateString(),
            'rappel_jours' => 15,
        ])->assertForbidden();

        $this->actingAs($chefMecanicien)->postJson(route('flotte.operations.realiser', $operation))
            ->assertOk();
    }

    public function test_realiser_cloture_la_ligne_et_cree_le_cycle_suivant(): void
    {
        $this->travelTo(Carbon::parse('2026-02-01'));

        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $operation = OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id, 'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => '2026-02-01', 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->postJson(route('flotte.operations.realiser', $operation), [
            'commentaire' => 'Vidange faite au garage X',
        ]);

        $response->assertOk();

        $operation->refresh();
        $this->assertSame('realisee', $operation->statut);
        $this->assertSame('2026-02-01', $operation->date_realisation->toDateString());
        $this->assertSame($admin->id, $operation->realise_par_id);

        // date_echeance stockée en datetime complet sous SQLite : comparaison
        // via le cast Eloquent plutôt qu'une chaîne brute dans assertDatabaseHas.
        $suivante = OperationProgrammee::where('statut', 'planifiee')->firstOrFail();
        $this->assertSame($vehicule->id, $suivante->vehicule_id);
        $this->assertSame($vidange->id, $suivante->type_operation_id);
        $this->assertSame('2026-05-02', $suivante->date_echeance->toDateString());
        $this->assertDatabaseCount('operations_programmees', 2);
    }

    public function test_realiser_sans_periodicite_ne_renouvelle_pas(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $assurance = TypeOperation::where('code', 'assurance')->firstOrFail();

        $operation = OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $assurance->id, 'type_operation_code' => 'assurance', 'type_operation_libelle' => 'Assurance',
            'date_echeance' => now()->addDays(10), 'rappel_jours' => 15, 'periodicite_jours' => null,
            'statut' => 'planifiee', 'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->postJson(route('flotte.operations.realiser', $operation));

        $response->assertOk();
        $this->assertNull($response->json('suivante'));
        $this->assertDatabaseCount('operations_programmees', 1);
    }

    public function test_realiser_une_operation_deja_realisee_est_rejete(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $operation = OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id, 'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => now(), 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'realisee', 'date_realisation' => now(), 'realise_par_id' => $admin->id, 'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->postJson(route('flotte.operations.realiser', $operation))
            ->assertStatus(422);
    }

    public function test_detail_expose_les_actives_et_lhistorique_du_vehicule(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id, 'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => now()->addDays(30), 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => $admin->id,
        ]);

        OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id, 'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => now()->subDays(60), 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'realisee', 'date_realisation' => now()->subDays(60), 'realise_par_id' => $admin->id, 'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->getJson(route('flotte.operations.detail', $vehicule));

        $response->assertOk();
        $this->assertCount(1, $response->json('actives'));
        $this->assertCount(1, $response->json('historique'));
    }

    public function test_badge_rouge_si_echeance_depassee_jaune_dans_la_fenetre_de_rappel(): void
    {
        $this->travelTo(Carbon::parse('2026-03-01'));

        $enRetard = OperationProgrammee::create([
            'vehicule_id' => Vehicule::factory()->create()->id, 'vehicule_code' => 'AL-001',
            'type_operation_id' => TypeOperation::where('code', 'vidange')->firstOrFail()->id,
            'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => '2026-02-20', 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => User::factory()->create()->id,
        ]);

        $aVenir = OperationProgrammee::create([
            'vehicule_id' => Vehicule::factory()->create()->id, 'vehicule_code' => 'AL-002',
            'type_operation_id' => TypeOperation::where('code', 'vidange')->firstOrFail()->id,
            'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => '2026-03-10', 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => User::factory()->create()->id,
        ]);

        $paisible = OperationProgrammee::create([
            'vehicule_id' => Vehicule::factory()->create()->id, 'vehicule_code' => 'AL-003',
            'type_operation_id' => TypeOperation::where('code', 'vidange')->firstOrFail()->id,
            'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => '2026-06-01', 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => User::factory()->create()->id,
        ]);

        $this->assertSame('rouge', $enRetard->badge());
        $this->assertSame('jaune', $aVenir->badge());
        $this->assertNull($paisible->badge());
    }
}
