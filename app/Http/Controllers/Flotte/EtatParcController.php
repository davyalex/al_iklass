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
 * Photo du parc à une date passée, ou sur un intervalle (par défaut hier) :
 * quel statut avait chaque véhicule, reconstitué depuis
 * historique_statuts_vehicule (aucune nouvelle table nécessaire, le
 * changelog existe déjà). La photo de statut se fixe à la fin de
 * l'intervalle ; la situation financière, elle, s'agrège jour par jour sur
 * tout l'intervalle.
 */
class EtatParcController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Vehicule::class);

        $hier = now()->subDay();
        $du = ($request->filled('date_debut') ? Carbon::parse($request->string('date_debut')) : $hier->copy())->startOfDay();
        $au = ($request->filled('date_fin') ? Carbon::parse($request->string('date_fin')) : $hier->copy())->startOfDay();

        if ($du->gt($au)) {
            [$du, $au] = [$au, $du];
        }

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

        // Photo du parc à la fin de l'intervalle.
        $historiquesFinPeriode = $this->dernierStatutADate($vehicules->pluck('id'), $au->copy()->endOfDay());

        $vehiculesConnus = $vehicules->filter(fn (Vehicule $v) => $historiquesFinPeriode->has($v->id));
        $vehiculesInexistants = $vehicules->count() - $vehiculesConnus->count();

        $vehiculesParStatut = $vehiculesConnus->groupBy(fn (Vehicule $v) => $historiquesFinPeriode[$v->id]->nouveau_statut_code);

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

        // Un seul agrégat sur le périmètre déjà déterminé par les filtres
        // (gestionnaire précis ou tous) : pas de répartition par gestionnaire
        // ici, c'est justement le rôle du filtre "Gestionnaire" ci-dessus.
        $parGestionnaire = $this->situationFinancierePeriode($gestionnairesConcernes, $vehicules, $du, $au);
        $situationFinanciere = [
            'attendu' => $parGestionnaire->sum('attendu'),
            'deja_verse' => $parGestionnaire->sum('deja_verse'),
            'reste_a_verser' => $parGestionnaire->sum('reste_a_verser'),
            'solde_dette' => $parGestionnaire->sum('solde_dette'),
            'a_jour' => $parGestionnaire->sum('reste_a_verser') <= 0,
        ];

        return view('flotte.vehicules.etat-parc', compact(
            'du', 'au', 'statuts', 'vehiculesParStatut', 'kpisParStatut', 'gestionnaires', 'vehiculesInexistants', 'situationFinanciere'
        ));
    }

    /**
     * Situation financière de chaque gestionnaire du périmètre sur
     * l'intervalle [du, au] (bornes incluses), agrégée jour par jour :
     * recette attendue selon le statut réel de chaque véhicule ce jour-là,
     * versements du jour, reste à verser cumulé — somme des manques
     * quotidiens, pas la différence globale (un jour excédentaire ne
     * compense pas un jour déficitaire chez ce même gestionnaire, cohérent
     * avec le moteur de bascule de dette qui raisonne jour par jour). Se
     * réduit exactement au calcul "un seul jour" quand du === au. Le
     * résultat par gestionnaire est ensuite sommé par l'appelant pour
     * l'agrégat global affiché.
     *
     * @param  Collection<int, User>  $gestionnaires
     * @param  Collection<int, Vehicule>  $vehicules
     * @return Collection<int, array{gestionnaire: User, attendu: float, deja_verse: float, reste_a_verser: float, solde_dette: float, a_jour: bool}>
     */
    private function situationFinancierePeriode(Collection $gestionnaires, Collection $vehicules, Carbon $du, Carbon $au): Collection
    {
        $historiquesParVehicule = HistoriqueStatutVehicule::whereIn('vehicule_id', $vehicules->pluck('id'))
            ->where('created_at', '<=', $au->copy()->endOfDay())
            ->orderBy('created_at')
            ->get()
            ->groupBy('vehicule_id');

        $versementsParGestionnaireEtJour = Versement::whereIn('gestionnaire_id', $gestionnaires->pluck('id'))
            ->whereDate('date_versement', '>=', $du)
            ->whereDate('date_versement', '<=', $au)
            ->selectRaw('gestionnaire_id, DATE(date_versement) as jour, SUM(montant) as total')
            ->groupBy('gestionnaire_id', 'jour')
            ->get()
            ->groupBy('gestionnaire_id');

        return $gestionnaires->map(function (User $gestionnaire) use ($vehicules, $historiquesParVehicule, $versementsParGestionnaireEtJour, $du, $au) {
            $verseParJour = ($versementsParGestionnaireEtJour->get($gestionnaire->id) ?? collect())->pluck('total', 'jour');

            $attenduParJour = [];

            foreach ($vehicules->where('gestionnaire_id', $gestionnaire->id) as $vehicule) {
                $historique = $historiquesParVehicule->get($vehicule->id, collect())->values();
                $nombreLignes = $historique->count();
                $pointeur = 0;
                $statutCourant = null;

                for ($jour = $du->copy(); $jour->lte($au); $jour->addDay()) {
                    $finJour = $jour->copy()->endOfDay();

                    while ($pointeur < $nombreLignes && $historique[$pointeur]->created_at->lte($finJour)) {
                        $statutCourant = $historique[$pointeur]->nouveau_statut_code;
                        $pointeur++;
                    }

                    if ($statutCourant === 'en_circulation') {
                        $cle = $jour->format('Y-m-d');
                        $attenduParJour[$cle] = ($attenduParJour[$cle] ?? 0) + (float) $vehicule->recette_journaliere;
                    }
                }
            }

            $attenduTotal = 0.0;
            $verseTotal = 0.0;
            $resteTotal = 0.0;

            for ($jour = $du->copy(); $jour->lte($au); $jour->addDay()) {
                $cle = $jour->format('Y-m-d');
                $attenduJour = $attenduParJour[$cle] ?? 0.0;
                $verseJour = (float) ($verseParJour[$cle] ?? 0);

                $attenduTotal += $attenduJour;
                $verseTotal += $verseJour;
                $resteTotal += max(0, $attenduJour - $verseJour);
            }

            return [
                'gestionnaire' => $gestionnaire,
                'attendu' => $attenduTotal,
                'deja_verse' => $verseTotal,
                'reste_a_verser' => $resteTotal,
                'solde_dette' => (float) $gestionnaire->dette,
                'a_jour' => $resteTotal <= 0,
            ];
        });
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
