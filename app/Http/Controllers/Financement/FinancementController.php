<?php

namespace App\Http\Controllers\Financement;

use App\Exports\Financement\FinancementsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Financement\StoreFinancementRequest;
use App\Models\Financement;
use App\Models\Preteur;
use App\Services\Financement\FinancementService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class FinancementController extends Controller
{
    public function __construct(private readonly FinancementService $financementService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Financement::class);

        $preteurs = Preteur::where('actif', true)->orderBy('nom')->get();
        $kpiPeriode = $this->calculerKpiPeriode($request);
        $kpiMois = $this->calculerKpiMois();

        return view('financements.financements.index', compact('preteurs', 'kpiPeriode', 'kpiMois'));
    }

    public function kpis(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Financement::class);

        return response()->json($this->calculerKpiPeriode($request) + $this->calculerKpiMois());
    }

    public function show(Financement $financement): JsonResponse
    {
        Gate::authorize('view', $financement);

        return response()->json($financement->load('remboursements.modePaiement'));
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Financement::class);

        $query = $this->filtrer(Financement::query(), $request)->with('preteur')->select('financements.*');

        return DataTables::of($query)
            ->editColumn('date_financement', fn (Financement $f) => $f->date_financement->format('d/m/Y'))
            ->editColumn('montant_total', fn (Financement $f) => Money::format($f->montant_total).' FCFA')
            ->editColumn('montant_rembourse', fn (Financement $f) => Money::format($f->montant_rembourse).' FCFA')
            ->editColumn('montant_restant', fn (Financement $f) => Money::format($f->montant_restant).' FCFA')
            ->addColumn('statut_badge', function (Financement $f) {
                $classes = ['en_cours' => 'bg-warning text-dark', 'solde' => 'bg-success'];
                $libelles = ['en_cours' => 'En cours', 'solde' => 'Soldé'];

                return '<span class="badge '.($classes[$f->statut] ?? 'bg-secondary').'">'.($libelles[$f->statut] ?? $f->statut).'</span>';
            })
            ->rawColumns(['statut_badge'])
            ->make(true);
    }

    public function store(StoreFinancementRequest $request): JsonResponse
    {
        $financement = $this->financementService->declarer($request->validated() + ['user_id' => $request->user()->id]);

        return response()->json([
            'message' => "Financement {$financement->reference} enregistré.",
            'financement' => $financement,
        ], 201);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Financement::class);

        $financements = $this->filtrer(Financement::query(), $request)->with('preteur')->orderByDesc('date_financement')->get();

        return Excel::download(new FinancementsExport($financements), 'financements-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        Gate::authorize('viewAny', Financement::class);

        $financements = $this->filtrer(Financement::query(), $request)->with('preteur')->orderByDesc('date_financement')->get();

        return Pdf::loadView('exports.pdf.financements', ['financements' => $financements])
            ->setPaper('a4', 'landscape')
            ->download('financements-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_financement', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_financement', '<=', $request->string('date_fin')))
            ->when($request->filled('preteur_id'), fn (Builder $q) => $q->where('preteur_id', $request->integer('preteur_id')))
            ->when($request->filled('statut'), fn (Builder $q) => $q->where('statut', $request->string('statut')));
    }

    /**
     * KPI réactifs au filtre (date/prêteur/statut) : par défaut, sans filtre
     * appliqué, ils portent sur toute la période (tous les financements).
     *
     * @return array{total: float, count: int, rembourse: float, restant: float}
     */
    private function calculerKpiPeriode(Request $request): array
    {
        $query = $this->filtrer(Financement::query(), $request);

        return [
            'total' => (float) (clone $query)->sum('montant_total'),
            'count' => (clone $query)->count(),
            'rembourse' => (float) (clone $query)->sum('montant_rembourse'),
            'restant' => (float) (clone $query)->sum('montant_restant'),
        ];
    }

    /**
     * KPI fixe, toujours sur le mois en cours — n'est jamais affecté par le filtre.
     *
     * @return array{mois: float, mois_count: int}
     */
    private function calculerKpiMois(): array
    {
        $duMois = Financement::duMois(now());

        return [
            'mois' => (float) (clone $duMois)->sum('montant_total'),
            'mois_count' => (clone $duMois)->count(),
        ];
    }
}
