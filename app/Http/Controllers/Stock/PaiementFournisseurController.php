<?php

namespace App\Http\Controllers\Stock;

use App\Exceptions\Stock\TropPercuException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StorePaiementFournisseurRequest;
use App\Models\Achat;
use App\Models\ModePaiement;
use App\Models\PaiementFournisseur;
use App\Services\Stock\PaiementFournisseurService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class PaiementFournisseurController extends Controller
{
    public function __construct(private readonly PaiementFournisseurService $paiementService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', PaiementFournisseur::class);

        $achatsEnCredit = Achat::where('montant_restant', '>', 0)->orderByDesc('date_achat')->get();
        $modesPaiement = ModePaiement::where('actif', true)->orderBy('libelle')->get();

        return view('stock.paiements.index', compact('achatsEnCredit', 'modesPaiement'));
    }

    public function data(): JsonResponse
    {
        Gate::authorize('viewAny', PaiementFournisseur::class);

        $query = PaiementFournisseur::query()->with('modePaiement')->select('paiements_fournisseur.*');

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
}
