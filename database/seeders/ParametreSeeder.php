<?php

namespace Database\Seeders;

use App\Models\Parametre;
use Illuminate\Database\Seeder;

class ParametreSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'cle' => 'application.nom',
                'valeur' => 'AL-IKLASS',
                'libelle' => "Nom de l'application",
                'groupe' => 'identite_application',
                'ordre' => 1,
            ],
            [
                'cle' => 'application.logo',
                'valeur' => '',
                'libelle' => "Logo de l'application",
                'groupe' => 'identite_application',
                'ordre' => 2,
            ],
            [
                'cle' => 'flotte.statut_journalier.heure_debut_fenetre',
                'valeur' => '08:00',
                'libelle' => "Début de la fenêtre d'ajustement des gestionnaires",
                'groupe' => 'statut_journalier',
                'ordre' => 1,
            ],
            [
                'cle' => 'flotte.statut_journalier.heure_fin_fenetre',
                'valeur' => '12:00',
                'libelle' => "Fin de la fenêtre d'ajustement des gestionnaires",
                'groupe' => 'statut_journalier',
                'ordre' => 2,
            ],
            [
                // Marqueur interne (pas affiché dans l'écran Paramètres) :
                // date de la dernière réinitialisation des statuts
                // journaliers, posée par StatutJournalierService. Initialisé
                // à aujourd'hui : on ne veut pas qu'un premier accès juste
                // après le seed écrase la répartition volontairement variée
                // des statuts de démo (VehiculeSeeder) avant même le
                // prochain vrai changement de jour.
                'cle' => 'flotte.statut_journalier.derniere_execution',
                'valeur' => now()->format('Y-m-d'),
                'libelle' => 'Dernière réinitialisation des statuts journaliers',
                'groupe' => 'interne',
                'ordre' => 0,
            ],
        ] as $parametre) {
            Parametre::firstOrCreate(['cle' => $parametre['cle']], $parametre);
        }
    }
}
