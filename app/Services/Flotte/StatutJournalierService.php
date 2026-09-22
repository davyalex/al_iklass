<?php

namespace App\Services\Flotte;

use App\Models\Parametre;
use App\Models\StatutVehicule;
use App\Models\Vehicule;

class StatutJournalierService
{
    private const CLE_DERNIERE_EXECUTION = 'flotte.statut_journalier.derniere_execution';

    public function __construct(private readonly DetteJournalierService $detteService) {}

    /**
     * Remet tous les véhicules non archivés en statut "en circulation"
     * (CONTEXTE.md §5), mais une seule fois par jour : si c'est déjà fait
     * aujourd'hui (via le cron ou un précédent appel), ne fait rien. Permet
     * d'appeler cette méthode aussi bien depuis la commande planifiée que
     * depuis un filet de sécurité déclenché par la navigation, sans jamais
     * dupliquer le travail.
     *
     * @return int Nombre de véhicules effectivement remis en circulation.
     */
    public function reinitialiserSiNecessaire(): int
    {
        $aujourdhui = now()->format('Y-m-d');

        if (Parametre::valeur(self::CLE_DERNIERE_EXECUTION) === $aujourdhui) {
            return 0;
        }

        // Doit tourner avant le reset ci-dessous : le statut de chaque
        // véhicule reflète encore "hier", point d'ancrage du calcul de dette.
        $this->detteService->basculerSiNecessaire();

        $statutEnCirculation = StatutVehicule::where('code', 'en_circulation')->first();

        if (! $statutEnCirculation) {
            return 0;
        }

        $vehicules = Vehicule::where('statut_id', '!=', $statutEnCirculation->id)
            ->orWhereNull('statut_id')
            ->get();

        foreach ($vehicules as $vehicule) {
            $vehicule->update(['statut_id' => $statutEnCirculation->id]);
        }

        $this->marquerExecute($aujourdhui);

        return $vehicules->count();
    }

    private function marquerExecute(string $date): void
    {
        Parametre::updateOrCreate(
            ['cle' => self::CLE_DERNIERE_EXECUTION],
            [
                'valeur' => $date,
                'libelle' => 'Dernière réinitialisation des statuts journaliers',
                'groupe' => 'interne',
                'ordre' => 0,
            ],
        );
    }
}
