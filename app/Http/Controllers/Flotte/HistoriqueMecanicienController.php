<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Models\HistoriqueStatutVehicule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Historique personnel d'un chef mécanicien : ses changements de statut
 * (dépannages traités), tous véhicules confondus.
 */
class HistoriqueMecanicienController extends Controller
{
    public function index(): View
    {
        Gate::authorize('flotte.vehicule.remise_circulation');

        return view('flotte.mon-historique.index');
    }

    public function kpis(): JsonResponse
    {
        Gate::authorize('flotte.vehicule.remise_circulation');

        return response()->json([
            'depannages_mois' => HistoriqueStatutVehicule::where('user_id', auth()->id())
                ->where('ancien_statut_code', 'depannage')
                ->where('nouveau_statut_code', 'en_circulation')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ]);
    }

    public function statuts(Request $request): JsonResponse
    {
        Gate::authorize('flotte.vehicule.remise_circulation');

        $query = $this->filtrerPeriode(
            HistoriqueStatutVehicule::where('user_id', auth()->id()),
            $request,
            'created_at'
        );

        return DataTables::of($query)
            ->editColumn('created_at', fn (HistoriqueStatutVehicule $h) => $h->created_at->format('d/m/Y H:i'))
            ->addColumn('transition', fn (HistoriqueStatutVehicule $h) => ($h->ancien_statut_libelle ?? 'Création').' → '.$h->nouveau_statut_libelle)
            ->editColumn('commentaire', fn (HistoriqueStatutVehicule $h) => $h->commentaire ?? '—')
            ->make(true);
    }

    private function filtrerPeriode(Builder $query, Request $request, string $colonneDate): Builder
    {
        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate($colonneDate, '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate($colonneDate, '<=', $request->string('date_fin')));
    }
}
