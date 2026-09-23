<?php

namespace Tests\Feature\Flotte;

use App\Models\HistoriqueDette;
use App\Models\ModePaiement;
use App\Models\Parametre;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Services\Flotte\DetteJournalierService;
use App\Services\Flotte\StatutJournalierService;
use App\Services\Flotte\VersementService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StatutVehiculeSeeder;
use Database\Seeders\StockReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DetteJournalierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(StatutVehiculeSeeder::class);
    }

    public function test_bascule_cree_la_dette_et_lhistorique_pour_le_reste_a_verser(): void
    {
        $this->travelTo(Carbon::parse('2026-01-02 00:05'));

        $gestionnaire = User::factory()->create(['name' => 'Awa Koné'])->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        Vehicule::factory()->create([
            'statut_id' => $statutCirculation->id,
            'gestionnaire_id' => $gestionnaire->id,
            'recette_journaliere' => 20000,
        ]);

        $nombre = app(DetteJournalierService::class)->basculerSiNecessaire();

        $this->assertSame(1, $nombre);
        $this->assertEquals(20000, (float) $gestionnaire->fresh()->dette);
        $this->assertDatabaseHas('historique_dettes', [
            'gestionnaire_id' => $gestionnaire->id,
            'gestionnaire_nom' => 'Awa Koné',
            'type' => 'bascule',
            'montant' => 20000,
            'dette_avant' => 0,
            'dette_apres' => 20000,
            'user_id' => null,
        ]);

        // date_reference stockée en datetime complet sous SQLite : comparaison
        // via le cast Eloquent plutôt qu'une chaîne brute dans assertDatabaseHas.
        $historique = HistoriqueDette::first();
        $this->assertSame('2026-01-01', $historique->date_reference->toDateString());
        $this->assertEquals(20000, (float) $historique->attendu);
        $this->assertEquals(0, (float) $historique->deja_verse);
    }

    public function test_pas_de_double_bascule_le_meme_jour_puis_redevient_effective_le_lendemain(): void
    {
        $this->travelTo(Carbon::parse('2026-01-02 00:05'));

        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        Vehicule::factory()->create([
            'statut_id' => $statutCirculation->id,
            'gestionnaire_id' => $gestionnaire->id,
            'recette_journaliere' => 10000,
        ]);

        $service = app(DetteJournalierService::class);

        $this->assertSame(1, $service->basculerSiNecessaire());
        $this->assertSame(0, $service->basculerSiNecessaire());
        $this->assertEquals(10000, (float) $gestionnaire->fresh()->dette);

        // Le lendemain, le même véhicule toujours impayé rebascule (cumul).
        $this->travelTo(Carbon::parse('2026-01-03 00:05'));
        $this->assertSame(1, $service->basculerSiNecessaire());
        $this->assertEquals(20000, (float) $gestionnaire->fresh()->dette);
    }

    public function test_gestionnaire_sans_reste_a_verser_ne_bascule_pas(): void
    {
        $this->seed(StockReferenceSeeder::class);
        $this->travelTo(Carbon::parse('2026-01-02 00:05'));

        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        Vehicule::factory()->create([
            'statut_id' => $statutCirculation->id,
            'gestionnaire_id' => $gestionnaire->id,
            'recette_journaliere' => 10000,
        ]);

        app(VersementService::class)->enregistrer([
            'gestionnaire_id' => $gestionnaire->id,
            'montant' => 10000,
            'mode_paiement_id' => ModePaiement::first()->id,
            'date_versement' => '2026-01-01',
            'user_id' => $admin->id,
        ]);

        $nombre = app(DetteJournalierService::class)->basculerSiNecessaire();

        $this->assertSame(0, $nombre);
        $this->assertEquals(0, (float) $gestionnaire->fresh()->dette);
        $this->assertDatabaseCount('historique_dettes', 0);
    }

    public function test_bascule_nest_pas_declenchee_si_le_reset_de_statut_a_deja_tourne_aujourdhui(): void
    {
        $this->travelTo(Carbon::parse('2026-01-02 00:05'));

        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        Vehicule::factory()->create([
            'statut_id' => $statutCirculation->id,
            'gestionnaire_id' => $gestionnaire->id,
            'recette_journaliere' => 10000,
        ]);

        // Simule que le reset de statut journalier a déjà tourné aujourd'hui
        // (comme le fait ParametreSeeder en pratique pour protéger les autres
        // tests Flotte) : la bascule de dette, imbriquée dans ce même bloc,
        // ne doit alors pas s'exécuter non plus.
        Parametre::updateOrCreate(
            ['cle' => 'flotte.statut_journalier.derniere_execution'],
            ['valeur' => '2026-01-02', 'libelle' => 'x', 'groupe' => 'interne', 'ordre' => 0]
        );

        $this->assertSame(0, app(StatutJournalierService::class)->reinitialiserSiNecessaire());
        $this->assertEquals(0, (float) $gestionnaire->fresh()->dette);
        $this->assertDatabaseCount('historique_dettes', 0);
    }

    public function test_reinitialiser_statuts_declenche_la_bascule_de_dette_avant_le_reset(): void
    {
        $this->travelTo(Carbon::parse('2026-01-02 00:05'));

        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $statutCirculation = StatutVehicule::where('code', 'en_circulation')->firstOrFail();
        $vehicule = Vehicule::factory()->create([
            'statut_id' => $statutCirculation->id,
            'gestionnaire_id' => $gestionnaire->id,
            'recette_journaliere' => 15000,
        ]);

        app(StatutJournalierService::class)->reinitialiserSiNecessaire();

        $this->assertEquals(15000, (float) $gestionnaire->fresh()->dette);
        $this->assertSame('en_circulation', $vehicule->fresh()->statut->code);
    }
}
