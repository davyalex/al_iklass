<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Models\HistoriqueStatutVehicule;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Versement;
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

        $gestionnairesConcernes = $peutVoirTout
            ? ($request->filled('gestionnaire_id')
                ? User::role('gestionnaire')->whereKey($request->integer('gestionnaire_id'))->orderBy('name')->get()
                : User::role('gestionnaire')->orderBy('name')->get())
            : collect([$request->user()]);

        $situationFinanciere = $gestionnairesConcernes->map(
            fn (User $gestionnaire) => $this->situationFinanciereADate($gestionnaire, $vehicules, $historiques, $date)
        );

        return view('flotte.vehicules.etat-parc', compact(
            'date', 'statuts', 'vehiculesParStatut', 'kpisParStatut', 'gestionnaires', 'vehiculesInexistants', 'situationFinanciere'
        ));
    }

    /**
     * Situation financière d'un gestionnaire à la date demandée : recette
     * attendue des véhicules qui étaient en circulation ce jour-là, montant
     * déjà versé ce jour-là, reste à verser. Le solde de dette affiché est
     * l'actuel (running, pas reconstitué pour cette date) : c'est un solde
     * cumulé, la valeur qui intéresse au moment de la consultation.
     *
     * @param  Collection<int, Vehicule>  $vehicules
     * @param  Collection<int, HistoriqueStatutVehicule>  $historiques
     * @return array{gestionnaire: User, attendu: float, deja_verse: float, reste_a_verser: float, solde_dette: float, a_jour: bool}
     */
    private function situationFinanciereADate(User $gestionnaire, Collection $vehicules, Collection $historiques, Carbon $date): array
    {
        $attendu = (float) $vehicules
            ->where('gestionnaire_id', $gestionnaire->id)
            ->filter(fn (Vehicule $v) => ($historiques[$v->id]->nouveau_statut_code ?? null) === 'en_circulation')
            ->sum('recette_journaliere');

        $dejaVerse = (float) Versement::whereDate('date_versement', $date)
            ->where('gestionnaire_id', $gestionnaire->id)
            ->sum('montant');

        $resteAVerser = max(0, $attendu - $dejaVerse);

        return [
            'gestionnaire' => $gestionnaire,
            'attendu' => $attendu,
            'deja_verse' => $dejaVerse,
            'reste_a_verser' => $resteAVerser,
            'solde_dette' => (float) $gestionnaire->dette,
            'a_jour' => $resteAVerser <= 0,
        ];
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
