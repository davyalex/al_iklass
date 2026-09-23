<?php

namespace App\Http\Controllers\Flotte;

use App\Exports\Flotte\HistoriqueDettesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Flotte\ReglerDetteRequest;
use App\Models\HistoriqueDette;
use App\Models\User;
use App\Services\Flotte\DetteJournalierService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Audit de la dette des gestionnaires : bascules automatiques, annulations
 * (admin) et règlements (gestionnaire ou admin en son nom). Un admin
 * (flotte.dette.gerer) voit tous les gestionnaires ; un gestionnaire
 * (flotte.dette.regler seul) ne voit et ne règle que sa propre dette.
 */
class DetteController extends Controller
{
    public function __construct(private readonly DetteJournalierService $detteService) {}

    public function index(Request $request): View
    {
        $this->autoriserAcces($request);

        $peutVoirTout = $request->user()->can('flotte.dette.gerer');

        // Options du filtre "Gestionnaire" : uniquement pertinent pour un
        // admin qui peut choisir qui regarder.
        $gestionnaires = $peutVoirTout ? User::role('gestionnaire')->orderBy('name')->get() : collect();

        // Gestionnaires actuellement en dette, dans le périmètre déjà scopé
        // (tous pour un admin, uniquement soi-même sinon) : sert aux cartes
        // "Régler"/"Annuler" ci-dessous, présentes pour les deux profils.
        $gestionnairesEnDette = User::role('gestionnaire')
            ->when(! $peutVoirTout, fn (Builder $q) => $q->whereKey($request->user()->id))
            ->where('dette', '>', 0)
            ->orderBy('name')
            ->get();

        $kpis = $this->calculerKpis($request, $peutVoirTout);

        return view('flotte.dettes.index', compact('gestionnaires', 'gestionnairesEnDette', 'kpis', 'peutVoirTout'));
    }

    public function kpis(Request $request): JsonResponse
    {
        $this->autoriserAcces($request);

        return response()->json($this->calculerKpis($request, $request->user()->can('flotte.dette.gerer')));
    }

    public function data(Request $request): JsonResponse
    {
        $this->autoriserAcces($request);

        $query = $this->filtrer(HistoriqueDette::query()->with(['gestionnaire', 'user']), $request)->select('historique_dettes.*');

        return DataTables::of($query)
            ->editColumn('created_at', fn (HistoriqueDette $h) => $h->created_at->format('d/m/Y H:i'))
            ->addColumn('gestionnaire_nom', fn (HistoriqueDette $h) => $h->gestionnaire_nom)
            ->addColumn('type_badge', fn (HistoriqueDette $h) => match ($h->type) {
                'bascule' => '<span class="badge bg-danger">Bascule</span>',
                'reglement' => '<span class="badge bg-success">Règlement</span>',
                default => '<span class="badge bg-secondary">Annulation</span>',
            })
            ->editColumn('montant', fn (HistoriqueDette $h) => Money::format((float) $h->montant).' FCFA')
            ->addColumn('dette_apres_fmt', fn (HistoriqueDette $h) => Money::format((float) $h->dette_apres).' FCFA')
            ->addColumn('auteur', fn (HistoriqueDette $h) => $h->user?->name ?? '—')
            ->editColumn('motif', fn (HistoriqueDette $h) => $h->motif ?? ($h->date_reference ? 'Reste à verser du '.$h->date_reference->format('d/m/Y') : '—'))
            ->rawColumns(['type_badge'])
            ->make(true);
    }

    public function regler(User $gestionnaire, ReglerDetteRequest $request): JsonResponse
    {
        try {
            $this->detteService->regler(
                $gestionnaire,
                (float) $request->validated('montant'),
                $request->validated('motif'),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        $gestionnaire = $gestionnaire->fresh();

        return response()->json([
            'message' => "Dette de {$gestionnaire->name} réglée.",
            'solde_du' => Money::format((float) $gestionnaire->dette),
            'solde_du_brut' => (float) $gestionnaire->dette,
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $this->autoriserAcces($request);

        $historiques = $this->filtrer(HistoriqueDette::query()->with(['gestionnaire', 'user']), $request)
            ->orderByDesc('created_at')->get();

        return Excel::download(new HistoriqueDettesExport($historiques), 'historique-dettes-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        $this->autoriserAcces($request);

        $historiques = $this->filtrer(HistoriqueDette::query()->with(['gestionnaire', 'user']), $request)
            ->orderByDesc('created_at')->get();

        return Pdf::loadView('exports.pdf.historique-dettes', ['historiques' => $historiques])
            ->setPaper('a4', 'landscape')
            ->download('historique-dettes-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function autoriserAcces(Request $request): void
    {
        abort_unless(
            $request->user()->can('flotte.dette.gerer') || $request->user()->can('flotte.dette.regler'),
            403
        );
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        // Un gestionnaire limité à flotte.dette.regler ne voit jamais que sa
        // propre dette, quel que soit le gestionnaire_id envoyé.
        $gestionnaireId = $request->user()->can('flotte.dette.gerer')
            ? $request->integer('gestionnaire_id')
            : $request->user()->id;

        return $query
            ->when($gestionnaireId, fn (Builder $q) => $q->where('gestionnaire_id', $gestionnaireId))
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->string('type')))
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->string('date_fin')));
    }

    /**
     * @return array{gestionnaires_en_dette: int, total_du: float}
     */
    private function calculerKpis(Request $request, bool $peutVoirTout): array
    {
        $query = User::role('gestionnaire')
            ->when(! $peutVoirTout, fn (Builder $q) => $q->whereKey($request->user()->id));

        return [
            'gestionnaires_en_dette' => (clone $query)->where('dette', '>', 0)->count(),
            'total_du' => (float) (clone $query)->sum('dette'),
        ];
    }
}
