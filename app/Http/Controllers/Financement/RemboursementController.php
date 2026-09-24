<?php

namespace App\Http\Controllers\Financement;

use App\Exceptions\Financement\RemboursementExcessifException;
use App\Exports\Financement\RemboursementsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Financement\StoreRemboursementRequest;
use App\Models\Financement;
use App\Models\ModePaiement;
use App\Models\Preteur;
use App\Models\RemboursementFinancement;
use App\Services\Financement\FinancementService;
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

class RemboursementController extends Controller
{
    public function __construct(private readonly FinancementService $financementService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', RemboursementFinancement::class);

        $financementsEnCours = Financement::where('statut', 'en_cours')->orderBy('preteur_nom')->get();
        $modesPaiement = ModePaiement::where('actif', true)->orderBy('libelle')->get();
        $preteurs = Preteur::where('actif', true)->orderBy('nom')->get();

        return view('financements.remboursements.index', compact('financementsEnCours', 'modesPaiement', 'preteurs'));
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', RemboursementFinancement::class);

        $query = $this->filtrer(RemboursementFinancement::query(), $request)->with('modePaiement')->select('remboursements_financement.*');

        return DataTables::of($query)
            ->editColumn('date_remboursement', fn (RemboursementFinancement $r) => $r->date_remboursement->format('d/m/Y'))
            ->editColumn('montant', fn (RemboursementFinancement $r) => number_format((float) $r->montant, 0, ',', ' ').' FCFA')
            ->addColumn('mode_paiement_libelle', fn (RemboursementFinancement $r) => $r->modePaiement?->libelle ?? '—')
            ->make(true);
    }

    public function store(StoreRemboursementRequest $request): JsonResponse
    {
        try {
            $remboursement = $this->financementService->rembourser($request->validated() + ['user_id' => $request->user()->id]);
        } catch (RemboursementExcessifException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Remboursement de {$remboursement->montant} FCFA enregistré.",
            'remboursement' => $remboursement,
        ], 201);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', RemboursementFinancement::class);

        $remboursements = $this->filtrer(RemboursementFinancement::query(), $request)->with('modePaiement')->orderByDesc('date_remboursement')->get();

        return Excel::download(new RemboursementsExport($remboursements), 'remboursements-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        Gate::authorize('viewAny', RemboursementFinancement::class);

        $remboursements = $this->filtrer(RemboursementFinancement::query(), $request)->with('modePaiement')->orderByDesc('date_remboursement')->get();

        return Pdf::loadView('exports.pdf.remboursements', ['remboursements' => $remboursements])
            ->setPaper('a4', 'landscape')
            ->download('remboursements-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_remboursement', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_remboursement', '<=', $request->string('date_fin')))
            ->when($request->filled('preteur_id'), fn (Builder $q) => $q->whereHas(
                'financement',
                fn (Builder $financement) => $financement->where('preteur_id', $request->integer('preteur_id'))
            ))
            ->when($request->filled('mode_paiement_id'), fn (Builder $q) => $q->where('mode_paiement_id', $request->integer('mode_paiement_id')));
    }
}
