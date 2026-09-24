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

    public function test_admin_peut_modifier_une_echeance_non_depassee(): void
    {
        $this->travelTo(Carbon::parse('2026-03-01'));

        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $operation = OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id, 'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => '2026-03-20', 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->putJson(route('flotte.operations.update', $operation), [
            'date_echeance' => '2026-04-01',
            'rappel_jours' => 10,
            'periodicite_jours' => 120,
            'commentaire' => 'Reporté suite à indisponibilité garage',
        ]);

        $response->assertOk();

        $operation->refresh();
        $this->assertSame('2026-04-01', $operation->date_echeance->toDateString());
        $this->assertSame(10, $operation->rappel_jours);
        $this->assertSame(120, $operation->periodicite_jours);
        $this->assertSame('Reporté suite à indisponibilité garage', $operation->commentaire);
    }

    public function test_modifier_une_echeance_depassee_est_rejete(): void
    {
        $this->travelTo(Carbon::parse('2026-03-01'));

        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $operation = OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id, 'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => '2026-02-15', 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->putJson(route('flotte.operations.update', $operation), [
            'date_echeance' => '2026-05-01',
            'rappel_jours' => 15,
        ]);

        $response->assertStatus(422);
        $this->assertSame('2026-02-15', $operation->fresh()->date_echeance->toDateString());
    }

    public function test_chef_mecanicien_ne_peut_pas_modifier_une_echeance(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $operation = OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id, 'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => now()->addDays(30), 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => $admin->id,
        ]);

        $this->actingAs($chefMecanicien)->putJson(route('flotte.operations.update', $operation), [
            'date_echeance' => now()->addDays(45)->toDateString(),
            'rappel_jours' => 15,
        ])->assertForbidden();
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

    public function test_chef_mecanicien_na_plus_acces_aux_operations_programmees(): void
    {
        // Le chef mécanicien n'a plus que les Interventions : ni planifier,
        // ni réaliser une opération programmée, ni même voir la page.
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
            ->assertForbidden();
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

    public function test_realiser_avec_une_date_de_realisation_choisie_calcule_le_cycle_suivant_depuis_celle_ci(): void
    {
        $this->travelTo(Carbon::parse('2026-02-10'));

        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $operation = OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id, 'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => '2026-02-01', 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => $admin->id,
        ]);

        // La vidange a en réalité été faite à l'échéance (01/02), pas le jour
        // de la saisie (10/02) : la date choisie doit être celle enregistrée,
        // et le prochain cycle doit se calculer à partir d'elle.
        $response = $this->actingAs($admin)->postJson(route('flotte.operations.realiser', $operation), [
            'date_realisation' => '2026-02-01',
        ]);

        $response->assertOk();

        $operation->refresh();
        $this->assertSame('2026-02-01', $operation->date_realisation->toDateString());

        $suivante = OperationProgrammee::where('statut', 'planifiee')->firstOrFail();
        $this->assertSame('2026-05-02', $suivante->date_echeance->toDateString());
    }

    public function test_realiser_avec_une_date_de_renouvellement_choisie_lutilise_au_lieu_du_calcul_automatique(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $operation = OperationProgrammee::create([
            'vehicule_id' => $vehicule->id, 'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id, 'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => '2026-02-01', 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => $admin->id,
        ]);

        // Périodicité 90j depuis le 01/02 donnerait le 02/05 par défaut ;
        // l'utilisateur choisit explicitement une autre date de renouvellement.
        $response = $this->actingAs($admin)->postJson(route('flotte.operations.realiser', $operation), [
            'date_realisation' => '2026-02-01',
            'date_renouvellement' => '2026-06-15',
        ]);

        $response->assertOk();

        $suivante = OperationProgrammee::where('statut', 'planifiee')->firstOrFail();
        $this->assertSame('2026-06-15', $suivante->date_echeance->toDateString());
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

    public function test_badge_distingue_depasse_jour_j_et_a_venir(): void
    {
        $this->travelTo(Carbon::parse('2026-03-01'));

        $enRetard = OperationProgrammee::create([
            'vehicule_id' => Vehicule::factory()->create()->id, 'vehicule_code' => 'AL-001',
            'type_operation_id' => TypeOperation::where('code', 'vidange')->firstOrFail()->id,
            'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => '2026-02-20', 'rappel_jours' => 15, 'periodicite_jours' => 90,
            'statut' => 'planifiee', 'user_id' => User::factory()->create()->id,
        ]);

        $jourJ = OperationProgrammee::create([
            'vehicule_id' => Vehicule::factory()->create()->id, 'vehicule_code' => 'AL-004',
            'type_operation_id' => TypeOperation::where('code', 'vidange')->firstOrFail()->id,
            'type_operation_code' => 'vidange', 'type_operation_libelle' => 'Vidange',
            'date_echeance' => '2026-03-01', 'rappel_jours' => 15, 'periodicite_jours' => 90,
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

        $this->assertSame('depasse', $enRetard->badge());
        $this->assertSame('jour_j', $jourJ->badge());
        $this->assertSame('a_venir', $aVenir->badge());
        $this->assertNull($paisible->badge());
    }
}
