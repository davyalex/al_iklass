<?php

namespace Tests\Feature\Flotte;

use App\Console\Commands\Flotte\ReinitialiserStatutsJournaliers;
use App\Models\HistoriqueStatutVehicule;
use App\Models\Parametre;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Services\Flotte\StatutJournalierService;
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

        // Le seeder marque "déjà fait aujourd'hui" par défaut (cf.
        // ParametreSeeder) : on l'efface pour tester la commande elle-même,
        // indépendamment du garde-fou "une fois par jour" (testé à part).
        Parametre::where('cle', 'flotte.statut_journalier.derniere_execution')->update(['valeur' => '']);

        $this->artisan(ReinitialiserStatutsJournaliers::class)->assertSuccessful();

        $this->assertSame($enCirculation->id, $aReinitialiser->fresh()->statut_id);
        $this->assertTrue($aReinitialiser->fresh()->actif);

        // Le véhicule déjà en circulation n'a pas été touché une seconde fois.
        $this->assertSame(1, HistoriqueStatutVehicule::where('vehicule_id', $dejaEnCirculation->id)->count());
        $this->assertSame(2, HistoriqueStatutVehicule::where('vehicule_id', $aReinitialiser->id)->count());
    }

    public function test_service_ne_reinitialise_quune_fois_par_jour(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 00:05'));

        $arret = StatutVehicule::where('code', 'arret')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $arret->id]);

        $service = app(StatutJournalierService::class);

        $this->assertSame(1, $service->reinitialiserSiNecessaire());

        // Un second appel le même jour (ex. cron ET filet de sécurité tous
        // deux déclenchés) ne doit rien refaire ni journaliser en double.
        // ->fresh() : l'instance $vehicule est périmée depuis le premier
        // appel (mis à jour par une autre instance dans le service), sans
        // rafraîchir, Eloquent ne verrait aucun changement à sauvegarder.
        $vehicule->fresh()->update(['statut_id' => $arret->id]);
        $this->assertSame(0, $service->reinitialiserSiNecessaire());
        $this->assertSame($arret->id, $vehicule->fresh()->statut_id);

        // Le lendemain, la réinitialisation redevient effective.
        $this->travelTo(Carbon::parse('2026-01-02 00:05'));
        $this->assertSame(1, $service->reinitialiserSiNecessaire());
    }

    public function test_visiter_une_page_flotte_declenche_le_filet_de_securite(): void
    {
        Parametre::where('cle', 'flotte.statut_journalier.derniere_execution')->update(['valeur' => '2025-01-01']);

        $admin = User::factory()->create()->assignRole('admin');
        $arret = StatutVehicule::where('code', 'arret')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $arret->id]);

        $this->actingAs($admin)->get(route('flotte.vehicules.index'))->assertOk();

        $this->assertSame('en_circulation', $vehicule->fresh()->statut->code);
        $this->assertSame(now()->format('Y-m-d'), Parametre::valeur('flotte.statut_journalier.derniere_execution'));
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

    public function test_admin_peut_remettre_en_circulation_avec_rapport(): void
    {
        // Le chef mécanicien n'a plus accès à cette route (il clôture depuis
        // Interventions désormais) : seul l'admin l'exerce encore ici.
        $admin = User::factory()->create()->assignRole('admin');
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutDepannage->id]);

        $response = $this->actingAs($admin)->postJson(route('flotte.vehicules.remise-circulation', $vehicule), [
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
        $admin = User::factory()->create()->assignRole('admin');
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutDepannage->id]);

        $response = $this->actingAs($admin)->postJson(route('flotte.vehicules.remise-circulation', $vehicule), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('rapport');
    }

    public function test_chef_mecanicien_ne_peut_plus_remettre_en_circulation_via_la_flotte(): void
    {
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        $statutDepannage = StatutVehicule::where('code', 'depannage')->firstOrFail();
        $vehicule = Vehicule::factory()->create(['statut_id' => $statutDepannage->id]);

        $this->actingAs($chefMecanicien)->postJson(route('flotte.vehicules.remise-circulation', $vehicule), [
            'rapport' => 'Réparé.',
        ])->assertForbidden();
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

    public function test_chef_mecanicien_ne_peut_consulter_ni_le_catalogue_vehicules_ni_les_gestionnaires(): void
    {
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');
        Vehicule::factory()->create();

        $this->actingAs($chefMecanicien)->get(route('flotte.vehicules.index'))->assertForbidden();
        $this->actingAs($chefMecanicien)->get(route('flotte.gestionnaires.index'))->assertForbidden();
    }
}
