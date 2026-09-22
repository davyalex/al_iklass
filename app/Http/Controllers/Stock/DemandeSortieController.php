<?php

namespace App\Http\Controllers\Stock;

use App\Exceptions\Stock\StockInsuffisantException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\RejeterDemandeSortieRequest;
use App\Http\Requests\Stock\StoreDemandeSortieRequest;
use App\Models\Article;
use App\Models\DemandeSortie;
use App\Models\Vehicule;
use App\Services\Stock\DemandeSortieService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class DemandeSortieController extends Controller
{
    public function __construct(private readonly DemandeSortieService $demandeService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', DemandeSortie::class);

        $peutTraiter = $request->user()->can('stock.demande.traiter');

        $articles = Article::where('actif', true)->orderBy('nom')->get();
        $vehicules = Vehicule::where('actif', true)->orderBy('code')->get();
        $kpis = $this->calculerKpis($request);

        return view('stock.demandes.index', compact('articles', 'vehicules', 'kpis', 'peutTraiter'));
    }

    public function kpis(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', DemandeSortie::class);

        return response()->json($this->calculerKpis($request));
    }

    public function show(DemandeSortie $demande): JsonResponse
    {
        Gate::authorize('view', $demande);

        return response()->json($demande->load(['lignes', 'demandeur', 'traitePar', 'sortie']));
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', DemandeSortie::class);

        $query = $this->filtrer(DemandeSortie::query()->withCount('lignes')->withSum('lignes', 'quantite'), $request);

        return DataTables::of($query)
            ->editColumn('date_demande', fn (DemandeSortie $d) => $d->date_demande->format('d/m/Y'))
            ->addColumn('lignes_count', fn (DemandeSortie $d) => (int) $d->lignes_count)
            ->addColumn('quantite_totale', fn (DemandeSortie $d) => (int) $d->lignes_sum_quantite)
            ->addColumn('statut_badge', fn (DemandeSortie $d) => match ($d->statut) {
                'validee' => '<span class="badge bg-success">Validée</span>',
                'rejetee' => '<span class="badge bg-danger">Rejetée</span>',
                default => '<span class="badge bg-secondary">En attente</span>',
            })
            ->rawColumns(['statut_badge'])
            ->make(true);
    }

    public function store(StoreDemandeSortieRequest $request): JsonResponse
    {
        $demande = $this->demandeService->creer($request->validated() + ['demandeur_id' => $request->user()->id]);

        return response()->json([
            'message' => "Demande {$demande->reference} envoyée au gestionnaire de stock.",
            'demande' => $demande,
        ], 201);
    }

    public function valider(DemandeSortie $demande, Request $request): JsonResponse
    {
        Gate::authorize('traiter', DemandeSortie::class);

        try {
            $demande = $this->demandeService->valider($demande, $request->user());
        } catch (StockInsuffisantException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'insufficient_stock' => true,
            ], 422);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'message' => "Demande {$demande->reference} validée : sortie {$demande->sortie->reference} créée.",
            'demande' => $demande,
        ]);
    }

    public function rejeter(DemandeSortie $demande, RejeterDemandeSortieRequest $request): JsonResponse
    {
        try {
            $demande = $this->demandeService->rejeter($demande, $request->user(), $request->validated('motif'));
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'message' => "Demande {$demande->reference} rejetée.",
            'demande' => $demande,
        ]);
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        $demandeurId = $request->user()->can('stock.demande.traiter') ? null : $request->user()->id;

        return $query
            ->when($demandeurId, fn (Builder $q) => $q->where('demandeur_id', $demandeurId))
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_demande', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_demande', '<=', $request->string('date_fin')))
            ->when($request->filled('vehicule_id'), fn (Builder $q) => $q->where('vehicule_id', $request->integer('vehicule_id')))
            ->when($request->filled('statut'), fn (Builder $q) => $q->where('statut', $request->string('statut')));
    }

    /**
     * @return array{en_attente: int, validees_mois: int, rejetees_mois: int}
     */
    private function calculerKpis(Request $request): array
    {
        $demandeurId = $request->user()->can('stock.demande.traiter') ? null : $request->user()->id;

        return [
            'en_attente' => DemandeSortie::enAttente()
                ->when($demandeurId, fn (Builder $q) => $q->where('demandeur_id', $demandeurId))
                ->count(),
            'validees_mois' => DemandeSortie::where('statut', 'validee')
                ->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)
                ->when($demandeurId, fn (Builder $q) => $q->where('demandeur_id', $demandeurId))
                ->count(),
            'rejetees_mois' => DemandeSortie::where('statut', 'rejetee')
                ->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)
                ->when($demandeurId, fn (Builder $q) => $q->where('demandeur_id', $demandeurId))
                ->count(),
        ];
    }
}
