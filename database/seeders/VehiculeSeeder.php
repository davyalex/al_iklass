<?php

namespace Database\Seeders;

use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Database\Seeder;

class VehiculeSeeder extends Seeder
{
    private const NOMBRE_VEHICULES = 100;

    /** Répartition réaliste des statuts sur l'ensemble du parc. */
    private const REPARTITION_STATUTS = [
        'en_circulation' => 70,
        'depannage' => 10,
        'maintenance' => 12,
        'arret' => 8,
    ];

    public function run(): void
    {
        $statutIds = StatutVehicule::pluck('id', 'code');
        $gestionnaireIds = User::role('gestionnaire')->pluck('id')->all();

        $marques = ['Toyota', 'Hyundai', 'Mercedes', 'Renault', 'Nissan', 'Mitsubishi'];
        $modeles = ['Coaster', 'Hiace', 'Sprinter', 'Master', 'Urvan', 'Rosa'];
        $prenoms = ['Koffi', 'Ouattara', 'Traoré', 'Bamba', 'Diabaté', 'Kouassi', 'Yao', 'Aka', 'Kader', 'Awa', 'Moussa', 'Aminata', 'Adjoua', 'Kouamé', 'Fatou'];
        $noms = ['Jean', 'Ibrahim', 'Moussa', 'Salif', 'Yacouba', 'Serge', 'Patrice', 'Firmin', 'Touré', 'Coulibaly', 'Sangaré', 'Konaté', 'Diallo'];

        $statutsAAffecter = [];
        foreach (self::REPARTITION_STATUTS as $code => $nombre) {
            $statutsAAffecter = array_merge($statutsAAffecter, array_fill(0, $nombre, $code));
        }
        shuffle($statutsAAffecter);

        for ($i = 1; $i <= self::NOMBRE_VEHICULES; $i++) {
            $code = 'VH-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $statutCode = $statutsAAffecter[$i - 1];
            $marque = $marques[array_rand($marques)];
            $modele = $modeles[array_rand($modeles)];

            // ~80% des véhicules sont affectés à un gestionnaire (répartis sur
            // les 10 comptes démo), le reste reste volontairement non affecté
            // pour couvrir ce cas dans les tests manuels.
            $gestionnaireId = ($gestionnaireIds !== [] && random_int(1, 100) <= 80)
                ? $gestionnaireIds[array_rand($gestionnaireIds)]
                : null;

            // withTrashed() : "code" est unique et non filtré sur deleted_at, donc
            // firstOrCreate() percuterait un véhicule archivé au lieu de le
            // retrouver (il est exclu du scope par défaut).
            if (Vehicule::withTrashed()->where('code', $code)->exists()) {
                continue;
            }

            Vehicule::create([
                'code' => $code,
                'libelle' => "{$marque} {$modele}",
                'marque' => $marque,
                'modele' => $modele,
                'immatriculation' => sprintf('CI-%04d-%s%s', $i, chr(65 + ($i % 26)), chr(65 + (($i * 7) % 26))),
                'date_mise_circulation' => now()->subDays(random_int(30, 1500))->format('Y-m-d'),
                // Le seeder tourne avec les évènements de modèle désactivés
                // (DatabaseSeeder utilise WithoutModelEvents) : le
                // VehiculeObserver ne synchronise donc pas "actif" ici,
                // il faut le renseigner explicitement.
                'actif' => $statutCode === 'en_circulation',
                'statut_id' => $statutIds[$statutCode],
                'chauffeur_nom' => $prenoms[array_rand($prenoms)].' '.$noms[array_rand($noms)],
                'chauffeur_telephone' => '07'.random_int(10000000, 99999999),
                'recette_journaliere' => random_int(120, 300) * 100,
                'gestionnaire_id' => $gestionnaireId,
            ]);
        }
    }
}
