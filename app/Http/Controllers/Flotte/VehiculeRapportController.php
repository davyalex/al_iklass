<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Models\SortieStock;
use App\Models\Vehicule;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Rapport complet d'un véhicule : fiche identité + historique intégral des
 * changements de statut (dont les dépannages avec rapport) et des sorties
 * de pièces effectuées sur ce véhicule.
 */
class VehiculeRapportController extends Controller
{
    public function index(Vehicule $vehicule): View
    {
        Gate::authorize('view', $vehicule);

        $vehicule->load(['statut', 'gestionnaire']);

        $rapportsIntervention = $vehicule->historiqueStatuts()
            ->with('user')
            ->whereNotNull('commentaire')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $kpis = [
            'changements_statut' => $vehicule->historiqueStatuts()->count(),
            'sorties_pieces' => SortieStock::where('vehicule_id', $vehicule->id)->count(),
            'depannages_traites' => $vehicule->historiqueStatuts()
                ->where('ancien_statut_code', 'depannage')
                ->where('nouveau_statut_code', 'en_circulation')
                ->count(),
        ];

        return view('flotte.vehicules.rapport', compact('vehicule', 'rapportsIntervention', 'kpis'));
    }

    public function statuts(Vehicule $vehicule, Request $request): JsonResponse
    {
        Gate::authorize('view', $vehicule);

        $query = $this->filtrerPeriode($vehicule->historiqueStatuts()->with('user'), $request, 'created_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return DataTables::of($query)
            ->editColumn('created_at', fn ($h) => $h->created_at->format('d/m/Y H:i'))
            ->addColumn('transition', fn ($h) => ($h->ancien_statut_libelle ?? 'Création').' → '.$h->nouveau_statut_libelle)
            ->addColumn('auteur', fn ($h) => $h->user?->name ?? '—')
            ->editColumn('commentaire', fn ($h) => $h->commentaire ?? '—')
            ->make(true);
    }

    public function sorties(Vehicule $vehicule, Request $request): JsonResponse
    {
        Gate::authorize('view', $vehicule);

        $query = $this->filtrerPeriode(
            SortieStock::where('vehicule_id', $vehicule->id)->with('user')->withCount('lignes')->withSum('lignes', 'quantite'),
            $request,
            'date_sortie'
        )->orderByDesc('date_sortie')->orderByDesc('id');

        return DataTables::of($query)
            ->editColumn('date_sortie', fn (SortieStock $s) => $s->date_sortie->format('d/m/Y'))
            ->addColumn('lignes_count', fn (SortieStock $s) => (int) $s->lignes_count)
            ->addColumn('quantite_totale', fn (SortieStock $s) => (int) $s->lignes_sum_quantite)
            ->addColumn('auteur', fn (SortieStock $s) => $s->user?->name ?? '—')
            ->editColumn('motif', fn (SortieStock $s) => $s->motif ?? '—')
            ->make(true);
    }

    private function filtrerPeriode(Builder $query, Request $request, string $colonneDate): Builder
    {
        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate($colonneDate, '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate($colonneDate, '<=', $request->string('date_fin')));
    }
}
