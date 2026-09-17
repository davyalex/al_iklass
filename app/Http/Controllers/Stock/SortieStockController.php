<?php

namespace App\Http\Controllers\Stock;

use App\Exceptions\Stock\StockInsuffisantException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreSortieRequest;
use App\Models\Article;
use App\Models\MouvementStock;
use App\Models\Vehicule;
use App\Services\Stock\SortieStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SortieStockController extends Controller
{
    public function __construct(private readonly SortieStockService $sortieService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', MouvementStock::class);

        $articles = Article::where('actif', true)->orderBy('nom')->get();
        $vehicules = Vehicule::where('actif', true)->orderBy('code')->get();

        return view('stock.sorties.index', compact('articles', 'vehicules'));
    }

    public function data(): JsonResponse
    {
        Gate::authorize('viewAny', MouvementStock::class);

        $query = MouvementStock::query()->sorties()->select('mouvements_stock.*');

        return DataTables::of($query)
            ->editColumn('date_mouvement', fn (MouvementStock $m) => $m->date_mouvement->format('d/m/Y H:i'))
            ->addColumn('nature_badge', fn (MouvementStock $m) => $m->nature === 'interne'
                ? '<span class="badge bg-primary">Interne</span>'
                : '<span class="badge bg-info text-dark">Vente externe</span>')
            ->addColumn('destination', fn (MouvementStock $m) => $m->nature === 'interne'
                ? ($m->vehicule_code ?? '—')
                : trim(($m->vehicule_externe ?? '').' / '.($m->acheteur ?? '')))
            ->editColumn('prix_vente', fn (MouvementStock $m) => $m->prix_vente !== null
                ? number_format((float) $m->prix_vente, 0, ',', ' ').' FCFA'
                : '—')
            ->rawColumns(['nature_badge'])
            ->make(true);
    }

    public function store(StoreSortieRequest $request): JsonResponse
    {
        $data = $request->validated() + ['user_id' => $request->user()->id];

        try {
            $mouvement = $data['nature'] === 'externe'
                ? $this->sortieService->sortieExterne($data)
                : $this->sortieService->sortieInterne($data);
        } catch (StockInsuffisantException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'insufficient_stock' => true,
                'article' => $e->article->only(['id', 'reference', 'nom', 'quantite_stock']),
            ], 422);
        }

        return response()->json([
            'message' => 'Sortie de stock enregistrée.',
            'mouvement' => $mouvement,
        ], 201);
    }
}
