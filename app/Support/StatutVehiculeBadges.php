<?php

namespace App\Support;

class StatutVehiculeBadges
{
    /**
     * Classe de badge Bootstrap par code de statut véhicule.
     *
     * @return array<string, string>
     */
    public static function classes(): array
    {
        return [
            'en_circulation' => 'bg-success',
            'depannage' => 'bg-danger',
            'maintenance' => 'bg-warning text-dark',
            'arret' => 'text-white bg-arret',
        ];
    }

    /**
     * Classe de fond "subtle" par code de statut, utilisée pour les cartes KPI.
     *
     * @return array<string, string>
     */
    public static function fondsKpi(): array
    {
        return [
            'en_circulation' => 'bg-success-subtle',
            'depannage' => 'bg-danger-subtle',
            'maintenance' => 'bg-warning-subtle',
            'arret' => 'bg-arret-subtle',
        ];
    }
}
