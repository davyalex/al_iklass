<?php

namespace App\Http\Controllers\Flotte;

use App\Exports\Flotte\HistoriqueInterventionsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Flotte\StoreInterventionRequest;
use App\Models\Intervention;
use App\Models\StatutVehicule;
use App\Models\TypePanne;
use App\Models\Vehicule;
use App\Services\Flotte\InterventionService;
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

class InterventionController extends Controller
{
    public function __construct(private readonly InterventionService $service) {}

    // Accès protégé par le middleware de route 'permission:interventions.*' (routes/flotte.php).
    public function index(): View
    {
        $vehicules = Vehicule::orderBy('code')->get(['id', 'code', 'marque', 'modele']);
        $typesPanne = TypePanne::where('actif', true)->orderBy('id')->get();
        // Le référentiel complet (actifs + inactifs) n'alimente que la modale
        // de gestion des types ; le formulaire de déclaration reste sur les actifs.
        $tousLesTypesPanne = TypePanne::orderBy('id')->get();
        $statutsPanne = StatutVehicule::where('actif', true)->where('code', '!=', 'en_circulation')->orderBy('id')->get();

        $interventionsEnCours = Intervention::where('statut', 'en_cours')
            ->orderByDesc('date_debut')
            ->get();

        return view('flotte.interventions.index', compact(
            'vehicules', 'typesPanne', 'tousLesTypesPanne', 'statutsPanne', 'interventionsEnCours'
        ));
    }

    public function historique(): View
    {
        $vehicules = Vehicule::orderBy('code')->get(['id', 'code']);
        $typesPanne = TypePanne::where('actif', true)->orderBy('id')->get();

        return view('flotte.interventions.historique', compact('vehicules', 'typesPanne'));
    }

    public function historiqueData(Request $request): JsonResponse
    {
        $query = $this->filtrerHistorique(Intervention::where('statut', 'terminee'), $request)
            ->with('clotureePar')
            ->select('interventions.*');

        return DataTables::of($query)
            ->editColumn('date_debut', fn (Intervention $i) => $i->date_debut->format('d/m/Y'))
            ->editColumn('date_fin', fn (Intervention $i) => $i->date_fin?->format('d/m/Y'))
            ->addColumn('type_panne_libelle', fn (Intervention $i) => $i->type_panne_libelle ?? '—')
            ->addColumn('cloturee_par', fn (Intervention $i) => $i->clotureePar?->name ?? '—')
            ->make(true);
    }

    public function historiqueExportExcel(Request $request): BinaryFileResponse
    {
        $interventions = $this->filtrerHistorique(Intervention::where('statut', 'terminee'), $request)
            ->with('clotureePar')
            ->orderByDesc('date_fin')
            ->get();

        return Excel::download(new HistoriqueInterventionsExport($interventions), 'historique-interventions-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function historiqueExportPdf(Request $request): Response
    {
        $interventions = $this->filtrerHistorique(Intervention::where('statut', 'terminee'), $request)
            ->with('clotureePar')
            ->orderByDesc('date_fin')
            ->get();

        return Pdf::loadView('exports.pdf.historique-interventions', ['interventions' => $interventions])
            ->setPaper('a4', 'landscape')
            ->download('historique-interventions-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrerHistorique(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('vehicule_id'), fn (Builder $q) => $q->where('vehicule_id', $request->integer('vehicule_id')))
            ->when($request->filled('type_panne_id'), fn (Builder $q) => $q->where('type_panne_id', $request->integer('type_panne_id')))
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_fin', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_fin', '<=', $request->string('date_fin')));
    }

    public function detail(Vehicule $vehicule): JsonResponse
    {
        $active = Intervention::where('vehicule_id', $vehicule->id)
            ->where('statut', 'en_cours')
            ->first();

        $historique = Intervention::where('vehicule_id', $vehicule->id)
            ->where('statut', 'terminee')
            ->with('clotureePar')
            ->orderByDesc('date_fin')
            ->get()
            ->map(fn (Intervention $i) => $this->formaterIntervention($i));

        return response()->json([
            'vehicule' => $vehicule->only(['id', 'code']),
            'active' => $active ? $this->formaterIntervention($active) : null,
            'historique' => $historique,
        ]);
    }

    public function store(StoreInterventionRequest $request): JsonResponse
    {
        try {
            $intervention = $this->service->declarer($request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'message' => "Panne déclarée pour {$intervention->vehicule_code}.",
            'intervention' => $this->formaterIntervention($intervention),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function formaterIntervention(Intervention $intervention): array
    {
        return [
            'id' => $intervention->id,
            'vehicule_code' => $intervention->vehicule_code,
            'type_panne_libelle' => $intervention->type_panne_libelle,
            'description' => $intervention->description,
            'statut' => $intervention->statut,
            'date_debut' => $intervention->date_debut->format('d/m/Y'),
            'date_fin' => $intervention->date_fin?->format('d/m/Y'),
            'rapport' => $intervention->rapport,
            'cloturee_par' => $intervention->relationLoaded('clotureePar') ? $intervention->clotureePar?->name : null,
        ];
    }
}
