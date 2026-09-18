<?php

namespace App\Http\Controllers\Stock;

use App\Exports\Stock\AchatsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreAchatRequest;
use App\Models\Achat;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Services\Stock\AchatService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class AchatController extends Controller
{
    public function __construct(private readonly AchatService $achatService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Achat::class);

        $fournisseurs = Fournisseur::where('actif', true)->orderBy('nom')->get();
        $articles = Article::where('actif', true)->orderBy('nom')->get();
        // Au premier chargement, aucun filtre n'est encore appliqué : la période
        // par défaut est donc "toute la période" (tous les achats).
        $kpiPeriode = $this->calculerKpiPeriode($request);
        $kpiMois = $this->calculerKpiMois();

        return view('stock.achats.index', compact('fournisseurs', 'articles', 'kpiPeriode', 'kpiMois'));
    }

    public function kpis(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Achat::class);

        return response()->json($this->calculerKpiPeriode($request) + $this->calculerKpiMois());
    }

    public function show(Achat $achat): JsonResponse
    {
        Gate::authorize('view', $achat);

        return response()->json($achat->load('lignes', 'bonCommande'));
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Achat::class);

        $query = $this->filtrer(Achat::query(), $request)->with('fournisseur')->select('achats.*');

        return DataTables::of($query)
            ->editColumn('date_achat', fn (Achat $achat) => $achat->date_achat->format('d/m/Y'))
            ->editColumn('montant_total', fn (Achat $achat) => Money::format($achat->montant_total).' FCFA')
            ->editColumn('montant_paye', fn (Achat $achat) => Money::format($achat->montant_paye).' FCFA')
            ->editColumn('montant_restant', fn (Achat $achat) => Money::format($achat->montant_restant).' FCFA')
            ->addColumn('statut_badge', function (Achat $achat) {
                $classes = [
                    'comptant' => 'bg-success',
                    'partiel' => 'bg-warning text-dark',
                    'credit' => 'bg-danger',
                ];

                return '<span class="badge '.($classes[$achat->statut_paiement] ?? 'bg-secondary').'">'.$achat->statut_paiement.'</span>';
            })
            ->rawColumns(['statut_badge'])
            ->make(true);
    }

    public function store(StoreAchatRequest $request): JsonResponse
    {
        try {
            $achat = $this->achatService->creer($request->validated() + ['user_id' => $request->user()->id]);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'message' => "Achat #{$achat->id} enregistré.",
            'achat' => $achat,
        ], 201);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Achat::class);

        $achats = $this->filtrer(Achat::query(), $request)->with('fournisseur')->orderByDesc('date_achat')->get();

        return Excel::download(new AchatsExport($achats), 'achats-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        Gate::authorize('viewAny', Achat::class);

        $achats = $this->filtrer(Achat::query(), $request)->with('fournisseur')->orderByDesc('date_achat')->get();

        return Pdf::loadView('exports.pdf.achats', ['achats' => $achats])
            ->setPaper('a4', 'landscape')
            ->download('achats-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function pdf(Request $request, Achat $achat): Response
    {
        Gate::authorize('view', $achat);

        $achat->load('lignes', 'bonCommande');

        $pdf = Pdf::loadView('exports.pdf.achat', ['achat' => $achat])->setPaper('a4', 'portrait');

        // download=1 force le téléchargement ; sinon aperçu navigateur (impression) via stream().
        return $request->boolean('download')
            ? $pdf->download("achat-{$achat->reference}.pdf")
            : $pdf->stream("achat-{$achat->reference}.pdf");
    }

    public function exportExcelSingle(Achat $achat): BinaryFileResponse
    {
        Gate::authorize('view', $achat);

        return Excel::download(new AchatsExport(collect([$achat])), "achat-{$achat->reference}.xlsx");
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_achat', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_achat', '<=', $request->string('date_fin')))
            ->when($request->filled('fournisseur_id'), fn (Builder $q) => $q->where('fournisseur_id', $request->integer('fournisseur_id')))
            ->when($request->filled('statut_paiement'), fn (Builder $q) => $q->where('statut_paiement', $request->string('statut_paiement')));
    }

    /**
     * KPI réactifs au filtre (date/fournisseur/statut) : par défaut, sans filtre
     * appliqué, ils portent sur toute la période (tous les achats).
     *
     * @return array{total: float, count: int, paye: float, restant: float}
     */
    private function calculerKpiPeriode(Request $request): array
    {
        $query = $this->filtrer(Achat::query(), $request);

        return [
            'total' => (float) (clone $query)->sum('montant_total'),
            'count' => (clone $query)->count(),
            'paye' => (float) (clone $query)->sum('montant_paye'),
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
        $duMois = Achat::duMois(now());

        return [
            'mois' => (float) (clone $duMois)->sum('montant_total'),
            'mois_count' => (clone $duMois)->count(),
        ];
    }
}
