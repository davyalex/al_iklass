<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flotte\RealiserOperationRequest;
use App\Http\Requests\Flotte\StoreOperationProgrammeeRequest;
use App\Models\OperationProgrammee;
use App\Models\TypeOperation;
use App\Models\Vehicule;
use App\Services\Flotte\OperationProgrammeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OperationProgrammeeController extends Controller
{
    public function __construct(private readonly OperationProgrammeeService $service) {}

    // Accès protégé par le middleware de route 'permission:operations.*' (routes/flotte.php).
    public function index(): View
    {
        $vehicules = Vehicule::orderBy('code')->get(['id', 'code', 'marque', 'modele', 'immatriculation']);
        $typesOperation = TypeOperation::where('actif', true)->orderBy('id')->get();

        $operationsPlanifiees = OperationProgrammee::where('statut', 'planifiee')->get();
        $operationsParVehicule = $operationsPlanifiees->groupBy('vehicule_id');

        $kpisParType = $typesOperation->map(function (TypeOperation $type) use ($operationsPlanifiees) {
            $operations = $operationsPlanifiees->where('type_operation_id', $type->id);

            return [
                'type' => $type,
                'en_retard' => $operations->filter(fn (OperationProgrammee $o) => $o->badge() === 'rouge')->count(),
                'a_venir' => $operations->filter(fn (OperationProgrammee $o) => $o->badge() === 'jaune')->count(),
            ];
        });

        return view('flotte.operations.index', compact('vehicules', 'typesOperation', 'operationsParVehicule', 'kpisParType'));
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
