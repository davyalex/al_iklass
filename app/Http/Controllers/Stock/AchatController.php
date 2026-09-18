<?php

namespace App\Http\Controllers\Stock;

use App\Exports\Stock\AchatsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreAchatRequest;
use App\Models\Achat;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Services\Stock\AchatService;
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

    public function pdf(Achat $achat): Response
    {
        Gate::authorize('view', $achat);

        $achat->load('lignes', 'bonCommande');

        // stream() : aperçu dans le navigateur pour impression, pas un téléchargement forcé.
        return Pdf::loadView('exports.pdf.achat', ['achat' => $achat])
            ->setPaper('a4', 'portrait')
            ->stream("achat-{$achat->reference}.pdf");
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_achat', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_achat', '<=', $request->string('date_fin')))
            ->when($request->filled('fournisseur_id'), fn (Builder $q) => $q->where('fournisseur_id', $request->integer('fournisseur_id')))
            ->when($request->filled('statut_paiement'), fn (Builder $q) => $q->where('statut_paiement', $request->string('statut_paiement')));
    }
}
