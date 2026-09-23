<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Models\HistoriqueStatutVehicule;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Photo du parc à une date passée (par défaut hier) : quel statut avait
 * chaque véhicule à cette date, reconstitué depuis historique_statuts_vehicule
 * (aucune nouvelle table nécessaire, le changelog existe déjà).
 */
class EtatParcController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Vehicule::class);

        $date = $request->filled('date') ? Carbon::parse($request->string('date')) : now()->subDay();

        $peutVoirTout = $request->user()->can('flotte.vehicule.voir') || $request->user()->can('flotte.vehicule.remise_circulation');

        $vehicules = Vehicule::query()
            ->when(
                ! $peutVoirTout && $request->user()->can('flotte.vehicule.voir_affectes'),
                fn ($query) => $query->where('gestionnaire_id', $request->user()->id)
            )
            ->when(
                $peutVoirTout && $request->filled('gestionnaire_id'),
                fn ($query) => $query->where('gestionnaire_id', $request->integer('gestionnaire_id'))
            )
            ->orderBy('code')
            ->get();

        $statuts = StatutVehicule::where('actif', true)->orderBy('id')->get();

        $historiques = $this->dernierStatutADate($vehicules->pluck('id'), $date->copy()->endOfDay());

        $vehiculesConnus = $vehicules->filter(fn (Vehicule $v) => $historiques->has($v->id));
        $vehiculesInexistants = $vehicules->count() - $vehiculesConnus->count();

        $vehiculesParStatut = $vehiculesConnus->groupBy(fn (Vehicule $v) => $historiques[$v->id]->nouveau_statut_code);

        $kpisParStatut = $statuts->mapWithKeys(
            fn (StatutVehicule $statut) => [$statut->code => $vehiculesParStatut->get($statut->code, collect())->count()]
        );

        $gestionnaires = $request->user()->can('flotte.vehicule.voir')
            ? User::role('gestionnaire')->orderBy('name')->get()
            : collect();

        return view('flotte.vehicules.etat-parc', compact(
            'date', 'statuts', 'vehiculesParStatut', 'kpisParStatut', 'gestionnaires', 'vehiculesInexistants'
        ));
    }

    /**
     * Pour chaque véhicule, la dernière ligne d'historique dont created_at
     * précède ou égale la fin de la journée demandée — sa "photo" à cette
     * date. Un véhicule sans ligne avant cette date n'existait pas encore
     * (absent du résultat).
     *
     * @param  Collection<int, int>  $vehiculeIds
     * @return Collection<int, HistoriqueStatutVehicule>
     */
    private function dernierStatutADate(Collection $vehiculeIds, Carbon $finJournee): Collection
    {
        $derniersIds = HistoriqueStatutVehicule::whereIn('vehicule_id', $vehiculeIds)
            ->where('created_at', '<=', $finJournee)
            ->selectRaw('vehicule_id, MAX(id) as dernier_id')
            ->groupBy('vehicule_id')
            ->pluck('dernier_id');

        return HistoriqueStatutVehicule::whereIn('id', $derniersIds)->get()->keyBy('vehicule_id');
    }
}
