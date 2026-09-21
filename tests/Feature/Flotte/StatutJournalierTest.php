<?php

namespace Tests\Feature\Flotte;

use App\Console\Commands\Flotte\ReinitialiserStatutsJournaliers;
use App\Models\HistoriqueStatutVehicule;
use App\Models\Parametre;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Support\FenetreStatutJournalier;
use Database\Seeders\ParametreSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StatutVehiculeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StatutJournalierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(StatutVehiculeSeeder::class);
        $this->seed(ParametreSeeder::class);
    }

    public function test_command_remet_en_circulation_les_vehicules_qui_ne_le_sont_pas(): void
    {
        $enCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $arret = StatutVehicule::where('code', 'arret')->firstOrFail();

        $dejaEnCirculation = Vehicule::factory()->create(['statut_id' => $enCirculation->id]);
        $aReinitialiser = Vehicule::factory()->create(['statut_id' => $arret->id]);

        $this->artisan(ReinitialiserStatutsJournaliers::class)->assertSuccessful();

        $this->assertSame($enCirculation->id, $aReinitialiser->fresh()->statut_id);
        $this->assertTrue($aReinitialiser->fresh()->actif);

        // Le véhicule déjà en circulation n'a pas été touché une seconde fois.
        $this->assertSame(1, HistoriqueStatutVehicule::where('vehicule_id', $dejaEnCirculation->id)->count());
        $this->assertSame(2, HistoriqueStatutVehicule::where('vehicule_id', $aReinitialiser->id)->count());
    }

    public function test_fenetre_statut_journalier_respecte_les_parametres(): void
    {
        Parametre::where('cle', 'flotte.statut_journalier.heure_debut_fenetre')->update(['valeur' => '08:00']);
        Parametre::where('cle', 'flotte.statut_journalier.heure_fin_fenetre')->update(['valeur' => '12:00']);

        $this->assertTrue(FenetreStatutJournalier::estOuverte(Carbon::parse('2026-01-01 10:00')));
        $this->assertFalse(FenetreStatutJournalier::estOuverte(Carbon::parse('2026-01-01 13:00')));
        $this->assertFalse(FenetreStatutJournalier::estOuverte(Carbon::parse('2026-01-01 07:59')));
    }

    public function test_gestionnaire_peut_changer_le_statut_de_son_vehicule_pendant_la_fenetre(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 10:00'));

        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $gestionnaire->id]);

        $response = $this->actingAs($gestionnaire)->patchJson(route('flotte.vehicules.statut', $vehicule), [
            'statut_id' => $statutDepannage->id,
        ]);

        $response->assertOk();
        $this->assertSame($statutDepannage->id, $vehicule->fresh()->statut_id);
    }

    public function test_gestionnaire_ne_peut_pas_changer_le_statut_hors_fenetre(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 14:00'));

        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => $gestionnaire->id]);

        $response = $this->actingAs($gestionnaire)->patchJson(route('flotte.vehicules.statut', $vehicule), [
            'statut_id' => $statutDepannage->id,
        ]);

        $response->assertForbidden();
    }

    public function test_gestionnaire_ne_peut_pas_changer_le_statut_dun_vehicule_qui_ne_lui_est_pas_affecte(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 10:00'));

        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutCirculation->id, 'gestionnaire_id' => null]);

        $response = $this->actingAs($gestionnaire)->patchJson(route('flotte.vehicules.statut', $vehicule), [
            'statut_id' => $statutDepannage->id,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_peut_changer_le_statut_a_tout_moment(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 23:00'));

        $admin = User::factory()->create()->assignRole('admin');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutCirculation->id]);

        $this->actingAs($admin)->patchJson(route('flotte.vehicules.statut', $vehicule), [
            'statut_id' => $statutDepannage->id,
        ])->assertOk();
    }

    public function test_chef_mecanicien_peut_remettre_en_circulation_avec_rapport(): void
    {
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutDepannage->id]);

        $response = $this->actingAs($chefMecanicien)->postJson(route('flotte.vehicules.remise-circulation', $vehicule), [
            'rapport' => 'Réparation terminée, courroie changée.',
        ]);

        $response->assertOk();
        $vehicule->refresh();
        $this->assertSame('en_circulation', $vehicule->statut->code);

        $this->assertDatabaseHas('historique_statuts_vehicule', [
            'vehicule_id' => $vehicule->id,
            'nouveau_statut_code' => 'en_circulation',
            'commentaire' => 'Réparation terminée, courroie changée.',
        ]);
    }

    public function test_remise_en_circulation_sans_rapport_est_rejetee(): void
    {
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutDepannage->id]);

        $response = $this->actingAs($chefMecanicien)->postJson(route('flotte.vehicules.remise-circulation', $vehicule), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('rapport');
    }

    public function test_gestionnaire_seul_ne_peut_pas_remettre_en_circulation(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutDepannage->id, 'gestionnaire_id' => $gestionnaire->id]);

        $this->actingAs($gestionnaire)->postJson(route('flotte.vehicules.remise-circulation', $vehicule), [
            'rapport' => 'Test',
        ])->assertForbidden();
    }

    public function test_chef_mecanicien_peut_consulter_le_catalogue_vehicules_sans_voir_les_gestionnaires(): void
    {
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        Vehicule::factory()->create();

        $this->actingAs($chefMecanicien)->get(route('flotte.vehicules.index'))->assertOk();
        $this->actingAs($chefMecanicien)->get(route('flotte.gestionnaires.index'))->assertForbidden();
    }
}
