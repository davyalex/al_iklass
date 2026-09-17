<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreAchatRequest;
use App\Models\Achat;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Services\Stock\AchatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AchatController extends Controller
{
    public function __construct(private readonly AchatService $achatService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Achat::class);

        $fournisseurs = Fournisseur::where('actif', true)->orderBy('nom')->get();
        $articles = Article::where('actif', true)->orderBy('nom')->get();

        return view('stock.achats.index', compact('fournisseurs', 'articles'));
    }

    public function data(): JsonResponse
    {
        Gate::authorize('viewAny', Achat::class);

        $query = Achat::query()->with('fournisseur')->select('achats.*');

        return DataTables::of($query)
            ->editColumn('date_achat', fn (Achat $achat) => $achat->date_achat->format('d/m/Y'))
            ->editColumn('montant_total', fn (Achat $achat) => number_format((float) $achat->montant_total, 0, ',', ' ').' FCFA')
            ->editColumn('montant_paye', fn (Achat $achat) => number_format((float) $achat->montant_paye, 0, ',', ' ').' FCFA')
            ->editColumn('montant_restant', fn (Achat $achat) => number_format((float) $achat->montant_restant, 0, ',', ' ').' FCFA')
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
        $achat = $this->achatService->creer($request->validated() + ['user_id' => $request->user()->id]);

        return response()->json([
            'message' => "Achat #{$achat->id} enregistré.",
            'achat' => $achat,
        ], 201);
    }
}
