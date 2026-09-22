<?php

namespace App\Http\Controllers\Stock;

use App\Exceptions\Stock\StockInsuffisantException;
use App\Exports\Stock\SortiesStockExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreSortieRequest;
use App\Models\Article;
use App\Models\SortieLigne;
use App\Models\SortieStock;
use App\Models\Vehicule;
use App\Services\Stock\SortieStockService;
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

class SortieStockController extends Controller
{
    public function __construct(private readonly SortieStockService $sortieService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', SortieStock::class);

        $articles = Article::where('actif', true)->orderBy('nom')->get();
        $vehicules = Vehicule::where('actif', true)->orderBy('code')->get();
        $kpis = $this->calculerKpis();

        return view('stock.sorties.index', compact('articles', 'vehicules', 'kpis'));
    }

    public function kpis(): JsonResponse
    {
        Gate::authorize('viewAny', SortieStock::class);

        return response()->json($this->calculerKpis());
    }

    public function show(SortieStock $sortie): JsonResponse
    {
        Gate::authorize('view', $sortie);

        return response()->json($sortie->load('lignes'));
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', SortieStock::class);

        $query = $this->filtrer(SortieStock::query()->withCount('lignes')->withSum('lignes', 'quantite'), $request)
            ->select('sorties_stock.*');

        return DataTables::of($query)
            ->editColumn('date_sortie', fn (SortieStock $s) => $s->date_sortie->format('d/m/Y'))
            ->editColumn('montant_total', fn (SortieStock $s) => Money::format((float) $s->montant_total).' FCFA')
            ->addColumn('nature_badge', fn (SortieStock $s) => $s->nature === 'interne'
                ? '<span class="badge bg-primary">Interne</span>'
                : '<span class="badge bg-info text-dark">Vente externe</span>')
            ->addColumn('destination', fn (SortieStock $s) => $s->nature === 'interne'
                ? ($s->vehicule_code ?? '—')
                : trim(($s->vehicule_externe ?? '').' / '.($s->acheteur ?? '')))
            ->addColumn('quantite_totale', fn (SortieStock $s) => (int) $s->lignes_sum_quantite)
            ->rawColumns(['nature_badge'])
            ->make(true);
    }

    public function store(StoreSortieRequest $request): JsonResponse
    {
        $data = $request->validated() + ['user_id' => $request->user()->id];

        try {
            $sortie = $data['nature'] === 'externe'
                ? $this->sortieService->creerExterne($data)
                : $this->sortieService->creerInterne($data);
        } catch (StockInsuffisantException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'insufficient_stock' => true,
                'article' => $e->article->only(['id', 'reference', 'nom', 'quantite_stock']),
            ], 422);
        }

        return response()->json([
            'message' => "Sortie {$sortie->reference} enregistrée.",
            'sortie' => $sortie,
        ], 201);
    }

    public function pdf(Request $request, SortieStock $sortie): Response
    {
        Gate::authorize('view', $sortie);

        $sortie->load('lignes');

        $pdf = Pdf::loadView('exports.pdf.sortie', ['sortie' => $sortie])->setPaper('a4', 'portrait');

        return $request->boolean('download')
            ? $pdf->download("sortie-{$sortie->reference}.pdf")
            : $pdf->stream("sortie-{$sortie->reference}.pdf");
    }

    public function exportExcelSingle(SortieStock $sortie): BinaryFileResponse
    {
        Gate::authorize('view', $sortie);

        return Excel::download(new SortiesStockExport(collect([$sortie->load('lignes')])), "sortie-{$sortie->reference}.xlsx");
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', SortieStock::class);

        $sorties = $this->filtrer(SortieStock::query()->with('lignes'), $request)->orderByDesc('date_sortie')->get();

        return Excel::download(new SortiesStockExport($sorties), 'sorties-stock-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        Gate::authorize('viewAny', SortieStock::class);

        $sorties = $this->filtrer(SortieStock::query()->with('lignes'), $request)->orderByDesc('date_sortie')->get();

        return Pdf::loadView('exports.pdf.sorties', ['sorties' => $sorties])
            ->setPaper('a4', 'landscape')
            ->download('sorties-stock-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_sortie', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_sortie', '<=', $request->string('date_fin')))
            ->when($request->filled('article_id'), fn (Builder $q) => $q->whereHas(
                'lignes',
                fn (Builder $l) => $l->where('article_id', $request->integer('article_id'))
            ))
            ->when($request->filled('nature'), fn (Builder $q) => $q->where('nature', $request->string('nature')));
    }

    /**
     * @return array{sorties_jour: int, sorties_mois: int, quantite_mois: int, valeur_mois: float}
     */
    private function calculerKpis(): array
    {
        $duMois = SortieStock::duMois(now());

        return [
            'sorties_jour' => SortieStock::whereDate('date_sortie', today())->count(),
            'sorties_mois' => (clone $duMois)->count(),
            'quantite_mois' => (int) SortieLigne::whereHas('sortie', fn (Builder $q) => $q->duMois())->sum('quantite'),
            'valeur_mois' => (float) (clone $duMois)->sum('montant_total'),
        ];
    }
}
