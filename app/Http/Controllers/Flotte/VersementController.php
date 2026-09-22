<?php

namespace App\Http\Controllers\Flotte;

use App\Exports\Flotte\VersementsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Flotte\StoreVersementRequest;
use App\Models\ModePaiement;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Versement;
use App\Services\Flotte\VersementService;
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

class VersementController extends Controller
{
    public function __construct(private readonly VersementService $versementService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Versement::class);

        $peutVoirTout = $request->user()->can('flotte.vehicule.voir');

        $gestionnaires = $peutVoirTout ? User::role('gestionnaire')->orderBy('name')->get() : collect();
        $vehicules = $peutVoirTout
            ? Vehicule::whereNotNull('gestionnaire_id')->orderBy('code')->get(['id', 'code', 'gestionnaire_id'])
            : Vehicule::where('gestionnaire_id', $request->user()->id)->orderBy('code')->get(['id', 'code']);
        $modesPaiement = ModePaiement::where('actif', true)->orderBy('libelle')->get();

        return view('flotte.versements.index', compact('gestionnaires', 'vehicules', 'modesPaiement', 'peutVoirTout'));
    }

    public function kpis(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Versement::class);

        // Un gestionnaire limité à ses propres versements ne peut pas consulter
        // les KPI d'un autre : la cible est forcée à lui-même quoi qu'il envoie.
        $gestionnaireId = $request->user()->can('flotte.vehicule.voir')
            ? ($request->integer('gestionnaire_id') ?: null)
            : $request->user()->id;

        $attendu = Vehicule::with('statut')
            ->when($gestionnaireId, fn (Builder $q) => $q->where('gestionnaire_id', $gestionnaireId))
            ->get()
            ->filter(fn (Vehicule $vehicule) => $vehicule->statut?->code === 'en_circulation')
            ->sum('recette_journaliere');

        $dejaVerseJour = (float) Versement::whereDate('date_versement', today())
            ->when($gestionnaireId, fn (Builder $q) => $q->where('gestionnaire_id', $gestionnaireId))
            ->sum('montant');

        $resteAVerserJour = max(0, (float) $attendu - $dejaVerseJour);

        $montantDu = $gestionnaireId
            ? (float) (User::find($gestionnaireId)?->dette ?? 0)
            : (float) User::role('gestionnaire')->sum('dette');

        [$debutPeriode, $finPeriode] = $this->bornesPeriode($request);
        $totalVersePeriode = (float) Versement::whereDate('date_versement', '>=', $debutPeriode)
            ->whereDate('date_versement', '<=', $finPeriode)
            ->when($gestionnaireId, fn (Builder $q) => $q->where('gestionnaire_id', $gestionnaireId))
            ->sum('montant');

        return response()->json([
            'recette_journaliere' => Money::format($attendu),
            'deja_verse_jour' => Money::format($dejaVerseJour),
            'reste_a_verser_jour' => Money::format($resteAVerserJour),
            'montant_du' => Money::format($montantDu),
            'total_verse_periode' => Money::format($totalVersePeriode),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Versement::class);

        $query = $this->filtrer(Versement::query(), $request)->with(['modePaiement', 'user'])->select('versements.*');

        return DataTables::of($query)
            ->editColumn('date_versement', fn (Versement $versement) => $versement->date_versement->format('d/m/Y'))
            ->editColumn('montant', fn (Versement $versement) => Money::format($versement->montant).' FCFA')
            ->addColumn('mode_paiement_libelle', fn (Versement $versement) => $versement->modePaiement->libelle)
            ->addColumn('enregistre_par', fn (Versement $versement) => $versement->user?->name ?? '—')
            ->make(true);
    }

    public function store(StoreVersementRequest $request): JsonResponse
    {
        $versement = $this->versementService->enregistrer($request->validated() + ['user_id' => $request->user()->id]);

        return response()->json([
            'message' => 'Versement de '.Money::format($versement->montant)." FCFA enregistré pour {$versement->gestionnaire_nom}.",
            'versement' => $versement,
        ], 201);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Versement::class);

        $versements = $this->filtrer(Versement::query(), $request)->with(['modePaiement', 'user'])->orderByDesc('date_versement')->get();

        return Excel::download(new VersementsExport($versements), 'versements-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        Gate::authorize('viewAny', Versement::class);

        $versements = $this->filtrer(Versement::query(), $request)->with(['modePaiement', 'user'])->orderByDesc('date_versement')->get();

        return Pdf::loadView('exports.pdf.versements', ['versements' => $versements])
            ->setPaper('a4', 'landscape')
            ->download('versements-'.now()->format('Y-m-d-His').'.pdf');
    }

    /**
     * Bornes (Y-m-d) de la période demandée (date_debut/date_fin), ou à
     * défaut le mois en cours.
     *
     * @return array{0: string, 1: string}
     */
    private function bornesPeriode(Request $request): array
    {
        if (! $request->filled('date_debut') && ! $request->filled('date_fin')) {
            return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
        }

        return [
            $request->filled('date_debut') ? $request->string('date_debut')->toString() : now()->startOfMonth()->toDateString(),
            $request->filled('date_fin') ? $request->string('date_fin')->toString() : now()->endOfMonth()->toDateString(),
        ];
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        // Un gestionnaire limité à ses propres versements (pas de flotte.vehicule.voir)
        // ne voit jamais que les siens, quel que soit le gestionnaire_id envoyé.
        $gestionnaireId = $request->user()->can('flotte.vehicule.voir')
            ? $request->integer('gestionnaire_id')
            : $request->user()->id;

        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_versement', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_versement', '<=', $request->string('date_fin')))
            ->when($gestionnaireId, fn (Builder $q) => $q->where('gestionnaire_id', $gestionnaireId))
            ->when($request->filled('mode_paiement_id'), fn (Builder $q) => $q->where('mode_paiement_id', $request->integer('mode_paiement_id')));
    }
}
