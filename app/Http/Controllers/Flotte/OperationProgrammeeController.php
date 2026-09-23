<?php

namespace App\Http\Controllers\Flotte;

use App\Exports\Flotte\HistoriqueOperationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Flotte\RealiserOperationRequest;
use App\Http\Requests\Flotte\StoreOperationProgrammeeRequest;
use App\Models\OperationProgrammee;
use App\Models\TypeOperation;
use App\Models\Vehicule;
use App\Services\Flotte\OperationProgrammeeService;
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

class OperationProgrammeeController extends Controller
{
    public function __construct(private readonly OperationProgrammeeService $service) {}

    // Accès protégé par le middleware de route 'permission:operations.*' (routes/flotte.php).
    public function index(): View
    {
        $vehicules = Vehicule::orderBy('code')->get(['id', 'code', 'marque', 'modele', 'immatriculation']);
        $typesOperation = TypeOperation::where('actif', true)->orderBy('id')->get();
        // Le référentiel complet (actifs + inactifs) n'alimente que la modale
        // de gestion des types ; la grille/KPI/filtres restent sur les actifs.
        $tousLesTypesOperation = TypeOperation::orderBy('id')->get();

        $operationsPlanifiees = OperationProgrammee::where('statut', 'planifiee')->get();
        $operationsParType = $operationsPlanifiees->groupBy('type_operation_id');

        $kpisParType = $typesOperation->map(function (TypeOperation $type) use ($operationsParType) {
            $operations = $operationsParType->get($type->id, collect());

            return [
                'type' => $type,
                'a_venir' => $operations->filter(fn (OperationProgrammee $o) => $o->badge() === 'a_venir')->count(),
                'jour_j' => $operations->filter(fn (OperationProgrammee $o) => $o->badge() === 'jour_j')->count(),
                'depasse' => $operations->filter(fn (OperationProgrammee $o) => $o->badge() === 'depasse')->count(),
            ];
        });

        return view('flotte.operations.index', compact('vehicules', 'typesOperation', 'tousLesTypesOperation', 'operationsParType', 'kpisParType'));
    }

    public function historique(): View
    {
        $vehicules = Vehicule::orderBy('code')->get(['id', 'code']);
        $typesOperation = TypeOperation::where('actif', true)->orderBy('id')->get();

        return view('flotte.operations.historique', compact('vehicules', 'typesOperation'));
    }

    public function historiqueData(Request $request): JsonResponse
    {
        $query = $this->filtrerHistorique(OperationProgrammee::where('statut', 'realisee'), $request)
            ->with('realisePar')
            ->select('operations_programmees.*');

        return DataTables::of($query)
            ->editColumn('date_realisation', fn (OperationProgrammee $o) => $o->date_realisation->format('d/m/Y'))
            ->editColumn('date_echeance', fn (OperationProgrammee $o) => $o->date_echeance->format('d/m/Y'))
            ->addColumn('realise_par', fn (OperationProgrammee $o) => $o->realisePar?->name ?? '—')
            ->make(true);
    }

    public function historiqueExportExcel(Request $request): BinaryFileResponse
    {
        $operations = $this->filtrerHistorique(OperationProgrammee::where('statut', 'realisee'), $request)
            ->with('realisePar')
            ->orderByDesc('date_realisation')
            ->get();

        return Excel::download(new HistoriqueOperationsExport($operations), 'historique-operations-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function historiqueExportPdf(Request $request): Response
    {
        $operations = $this->filtrerHistorique(OperationProgrammee::where('statut', 'realisee'), $request)
            ->with('realisePar')
            ->orderByDesc('date_realisation')
            ->get();

        return Pdf::loadView('exports.pdf.historique-operations', ['operations' => $operations])
            ->setPaper('a4', 'landscape')
            ->download('historique-operations-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrerHistorique(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('vehicule_id'), fn (Builder $q) => $q->where('vehicule_id', $request->integer('vehicule_id')))
            ->when($request->filled('type_operation_id'), fn (Builder $q) => $q->where('type_operation_id', $request->integer('type_operation_id')))
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_realisation', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_realisation', '<=', $request->string('date_fin')));
    }

    public function detail(Vehicule $vehicule): JsonResponse
    {
        $actives = OperationProgrammee::where('vehicule_id', $vehicule->id)
            ->where('statut', 'planifiee')
            ->orderBy('date_echeance')
            ->get()
            ->map(fn (OperationProgrammee $o) => $this->formaterOperation($o));

        $historique = OperationProgrammee::where('vehicule_id', $vehicule->id)
            ->where('statut', 'realisee')
            ->with('realisePar')
            ->orderByDesc('date_realisation')
            ->get()
            ->map(fn (OperationProgrammee $o) => $this->formaterOperation($o));

        return response()->json([
            'vehicule' => $vehicule->only(['id', 'code']),
            'actives' => $actives,
            'historique' => $historique,
        ]);
    }

    public function store(StoreOperationProgrammeeRequest $request): JsonResponse
    {
        try {
            $operation = $this->service->planifier($request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'message' => "Échéance {$operation->type_operation_libelle} planifiée pour {$operation->vehicule_code}.",
            'operation' => $this->formaterOperation($operation),
        ], 201);
    }

    public function realiser(OperationProgrammee $operation, RealiserOperationRequest $request): JsonResponse
    {
        try {
            $resultat = $this->service->realiser($operation, $request->user(), $request->validated('commentaire'));
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'message' => "{$operation->type_operation_libelle} réalisée pour {$operation->vehicule_code}.",
            'cloturee' => $this->formaterOperation($resultat['cloturee']),
            'suivante' => $resultat['suivante'] ? $this->formaterOperation($resultat['suivante']) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formaterOperation(OperationProgrammee $operation): array
    {
        return [
            'id' => $operation->id,
            'vehicule_code' => $operation->vehicule_code,
            'type_operation_code' => $operation->type_operation_code,
            'type_operation_libelle' => $operation->type_operation_libelle,
            'date_echeance' => $operation->date_echeance->format('d/m/Y'),
            'rappel_jours' => $operation->rappel_jours,
            'statut' => $operation->statut,
            'date_realisation' => $operation->date_realisation?->format('d/m/Y'),
            'commentaire' => $operation->commentaire,
            'badge' => $operation->badge(),
            'realise_par' => $operation->relationLoaded('realisePar') ? $operation->realisePar?->name : null,
        ];
    }
}
