<?php

namespace App\Support;

use App\Models\StatutVehicule;
use App\Models\Vehicule;
use Illuminate\Support\Collection;

class VehiculeKpis
{
    /**
     * @param  Collection<int, Vehicule>  $vehicules
     * @param  Collection<int, StatutVehicule>  $statuts
     * @return array<string, mixed>
     */
    public static function calculer(Collection $vehicules, Collection $statuts): array
    {
        $parStatut = $vehicules->groupBy(fn (Vehicule $vehicule) => $vehicule->statut?->code ?? 'sans_statut');

        $comptesParStatut = $statuts->mapWithKeys(
            fn (StatutVehicule $statut) => [$statut->code => $parStatut->get($statut->code, collect())->count()]
        );

        $recetteEnCirculation = $vehicules
            ->filter(fn (Vehicule $vehicule) => $vehicule->statut?->code === 'en_circulation')
            ->sum('recette_journaliere');

        return [
            'total' => $vehicules->count(),
            'par_statut' => $comptesParStatut,
            'recette_en_circulation' => Money::format($recetteEnCirculation),
        ];
    }
}
