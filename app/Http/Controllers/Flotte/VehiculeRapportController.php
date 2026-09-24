<?php

namespace App\Http\Controllers\Flotte;

use App\Exports\Flotte\HistoriqueChangementsStatutExport;
use App\Exports\Flotte\HistoriqueInterventionsExport;
use App\Exports\Flotte\HistoriqueOperationsExport;
use App\Exports\Stock\SortiesStockExport;
use App\Http\Controllers\Controller;
use App\Models\Intervention;
use App\Models\OperationProgrammee;
use App\Models\SortieStock;
use App\Models\Vehicule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Rapport complet d'un véhicule : fiche identité + historique intégral des
 * changements de statut (dont les dépannages avec rapport), des sorties de
 * pièces, des interventions clôturées et des opérations programmées
 * réalisées sur ce véhicule.
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

    public function interventions(Vehicule $vehicule, Request $request): JsonResponse
    {
        Gate::authorize('view', $vehicule);

        $query = $this->filtrerPeriode(
            Intervention::where('vehicule_id', $vehicule->id)->where('statut', 'terminee')->with('clotureePar'),
            $request,
            'date_fin'
        )->orderByDesc('date_fin');

        return DataTables::of($query)
            ->editColumn('date_debut', fn (Intervention $i) => $i->date_debut->format('d/m/Y'))
            ->editColumn('date_fin', fn (Intervention $i) => $i->date_fin?->format('d/m/Y'))
            ->addColumn('type_panne_libelle', fn (Intervention $i) => $i->type_panne_libelle ?? '—')
            ->addColumn('cloturee_par', fn (Intervention $i) => $i->clotureePar?->name ?? '—')
            ->make(true);
    }

    public function operations(Vehicule $vehicule, Request $request): JsonResponse
    {
        Gate::authorize('view', $vehicule);

        $query = $this->filtrerPeriode(
            OperationProgrammee::where('vehicule_id', $vehicule->id)->where('statut', 'realisee')->with('realisePar'),
            $request,
            'date_realisation'
        )->orderByDesc('date_realisation');

        return DataTables::of($query)
            ->editColumn('date_realisation', fn (OperationProgrammee $o) => $o->date_realisation->format('d/m/Y'))
            ->editColumn('date_echeance', fn (OperationProgrammee $o) => $o->date_echeance->format('d/m/Y'))
            ->addColumn('realise_par', fn (OperationProgrammee $o) => $o->realisePar?->name ?? '—')
            ->make(true);
    }

    public function exportStatutsExcel(Vehicule $vehicule, Request $request): BinaryFileResponse
    {
        Gate::authorize('view', $vehicule);

        $changements = $this->filtrerPeriode($vehicule->historiqueStatuts()->with('user'), $request, 'created_at')
            ->orderByDesc('created_at')->get();

        return Excel::download(new HistoriqueChangementsStatutExport($changements), 'statuts-'.$vehicule->code.'-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportStatutsPdf(Vehicule $vehicule, Request $request): Response
    {
        Gate::authorize('view', $vehicule);

        $changements = $this->filtrerPeriode($vehicule->historiqueStatuts()->with('user'), $request, 'created_at')
            ->orderByDesc('created_at')->get();

        return Pdf::loadView('exports.pdf.changements-statut', ['vehicule' => $vehicule, 'changements' => $changements])
            ->setPaper('a4', 'landscape')
            ->download('statuts-'.$vehicule->code.'-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function exportSortiesExcel(Vehicule $vehicule, Request $request): BinaryFileResponse
    {
        Gate::authorize('view', $vehicule);

        $sorties = $this->filtrerPeriode(SortieStock::where('vehicule_id', $vehicule->id)->with(['user', 'lignes']), $request, 'date_sortie')
            ->orderByDesc('date_sortie')->get();

        return Excel::download(new SortiesStockExport($sorties), 'sorties-'.$vehicule->code.'-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportSortiesPdf(Vehicule $vehicule, Request $request): Response
    {
        Gate::authorize('view', $vehicule);

        $sorties = $this->filtrerPeriode(SortieStock::where('vehicule_id', $vehicule->id)->with(['user', 'lignes']), $request, 'date_sortie')
            ->orderByDesc('date_sortie')->get();

        return Pdf::loadView('exports.pdf.sorties', ['sorties' => $sorties])
            ->setPaper('a4', 'landscape')
            ->download('sorties-'.$vehicule->code.'-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function exportInterventionsExcel(Vehicule $vehicule, Request $request): BinaryFileResponse
    {
        Gate::authorize('view', $vehicule);

        $interventions = $this->filtrerPeriode(
            Intervention::where('vehicule_id', $vehicule->id)->where('statut', 'terminee')->with('clotureePar'),
            $request,
            'date_fin'
        )->orderByDesc('date_fin')->get();

        return Excel::download(new HistoriqueInterventionsExport($interventions), 'interventions-'.$vehicule->code.'-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportInterventionsPdf(Vehicule $vehicule, Request $request): Response
    {
        Gate::authorize('view', $vehicule);

        $interventions = $this->filtrerPeriode(
            Intervention::where('vehicule_id', $vehicule->id)->where('statut', 'terminee')->with('clotureePar'),
            $request,
            'date_fin'
        )->orderByDesc('date_fin')->get();

        return Pdf::loadView('exports.pdf.historique-interventions', ['interventions' => $interventions])
            ->setPaper('a4', 'landscape')
            ->download('interventions-'.$vehicule->code.'-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function exportOperationsExcel(Vehicule $vehicule, Request $request): BinaryFileResponse
    {
        Gate::authorize('view', $vehicule);

        $operations = $this->filtrerPeriode(
            OperationProgrammee::where('vehicule_id', $vehicule->id)->where('statut', 'realisee')->with('realisePar'),
            $request,
            'date_realisation'
        )->orderByDesc('date_realisation')->get();

        return Excel::download(new HistoriqueOperationsExport($operations), 'operations-'.$vehicule->code.'-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportOperationsPdf(Vehicule $vehicule, Request $request): Response
    {
        Gate::authorize('view', $vehicule);

        $operations = $this->filtrerPeriode(
            OperationProgrammee::where('vehicule_id', $vehicule->id)->where('statut', 'realisee')->with('realisePar'),
            $request,
            'date_realisation'
        )->orderByDesc('date_realisation')->get();

        return Pdf::loadView('exports.pdf.historique-operations', ['operations' => $operations])
            ->setPaper('a4', 'landscape')
            ->download('operations-'.$vehicule->code.'-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrerPeriode(Builder $query, Request $request, string $colonneDate): Builder
    {
        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate($colonneDate, '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate($colonneDate, '<=', $request->string('date_fin')));
    }
}
