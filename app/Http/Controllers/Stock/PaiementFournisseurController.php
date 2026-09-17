<?php

namespace App\Http\Controllers\Stock;

use App\Exceptions\Stock\TropPercuException;
use App\Exports\Stock\PaiementsFournisseurExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StorePaiementFournisseurRequest;
use App\Models\Achat;
use App\Models\Fournisseur;
use App\Models\ModePaiement;
use App\Models\PaiementFournisseur;
use App\Services\Stock\PaiementFournisseurService;
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

class PaiementFournisseurController extends Controller
{
    public function __construct(private readonly PaiementFournisseurService $paiementService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', PaiementFournisseur::class);

        $achatsEnCredit = Achat::where('montant_restant', '>', 0)->orderByDesc('date_achat')->get();
        $modesPaiement = ModePaiement::where('actif', true)->orderBy('libelle')->get();
        $fournisseurs = Fournisseur::where('actif', true)->orderBy('nom')->get();

        return view('stock.paiements.index', compact('achatsEnCredit', 'modesPaiement', 'fournisseurs'));
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PaiementFournisseur::class);

        $query = $this->filtrer(PaiementFournisseur::query(), $request)->with('modePaiement')->select('paiements_fournisseur.*');

        return DataTables::of($query)
            ->editColumn('date_paiement', fn (PaiementFournisseur $p) => $p->date_paiement->format('d/m/Y'))
            ->editColumn('montant', fn (PaiementFournisseur $p) => number_format((float) $p->montant, 0, ',', ' ').' FCFA')
            ->addColumn('mode_paiement_libelle', fn (PaiementFournisseur $p) => $p->modePaiement->libelle)
            ->make(true);
    }

    public function store(StorePaiementFournisseurRequest $request): JsonResponse
    {
        try {
            $paiement = $this->paiementService->enregistrer($request->validated() + ['user_id' => $request->user()->id]);
        } catch (TropPercuException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Paiement de {$paiement->montant} FCFA enregistré.",
            'paiement' => $paiement,
        ], 201);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', PaiementFournisseur::class);

        $paiements = $this->filtrer(PaiementFournisseur::query(), $request)->with('modePaiement')->orderByDesc('date_paiement')->get();

        return Excel::download(new PaiementsFournisseurExport($paiements), 'paiements-fournisseurs-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        Gate::authorize('viewAny', PaiementFournisseur::class);

        $paiements = $this->filtrer(PaiementFournisseur::query(), $request)->with('modePaiement')->orderByDesc('date_paiement')->get();

        return Pdf::loadView('exports.pdf.paiements', ['paiements' => $paiements])
            ->setPaper('a4', 'landscape')
            ->download('paiements-fournisseurs-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_paiement', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_paiement', '<=', $request->string('date_fin')))
            ->when($request->filled('fournisseur_id'), fn (Builder $q) => $q->whereHas(
                'achat',
                fn (Builder $achat) => $achat->where('fournisseur_id', $request->integer('fournisseur_id'))
            ))
            ->when($request->filled('mode_paiement_id'), fn (Builder $q) => $q->where('mode_paiement_id', $request->integer('mode_paiement_id')));
    }
}
