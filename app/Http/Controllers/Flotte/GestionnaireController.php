<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flotte\AnnulerDetteRequest;
use App\Models\HistoriqueDette;
use App\Models\ModePaiement;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Versement;
use App\Services\Flotte\DetteJournalierService;
use App\Support\Money;
use App\Support\VehiculeKpis;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GestionnaireController extends Controller
{
    public function __construct(private readonly DetteJournalierService $detteService) {}

    public function index(): View
    {
        Gate::authorize('flotte.vehicule.voir');

        $gestionnaires = User::role('gestionnaire')
            ->with(['vehiculesAttribues' => fn ($query) => $query->with('statut')->orderBy('code')])
            ->orderBy('name')
            ->get();

        $statuts = StatutVehicule::where('actif', true)->orderBy('id')->get();

        $kpis = VehiculeKpis::calculer(Vehicule::with('statut')->get(), $statuts);

        $versementsDuJour = Versement::whereDate('date_versement', today())
            ->selectRaw('gestionnaire_id, SUM(montant) as total')
            ->groupBy('gestionnaire_id')
            ->pluck('total', 'gestionnaire_id');

        $modesPaiement = ModePaiement::where('actif', true)->orderBy('libelle')->get();

        $vehicules = Vehicule::whereNotNull('gestionnaire_id')->orderBy('code')->get(['id', 'code']);

        return view('flotte.gestionnaires.index', compact('gestionnaires', 'statuts', 'kpis', 'versementsDuJour', 'modesPaiement', 'vehicules'));
    }

    public function compte(User $gestionnaire): JsonResponse
    {
        Gate::authorize('flotte.vehicule.voir');

        abort_unless($gestionnaire->hasRole('gestionnaire'), 404);

        $versementsRecents = Versement::where('gestionnaire_id', $gestionnaire->id)
            ->with('modePaiement')
            ->orderByDesc('date_versement')
            ->limit(10)
            ->get();

        $historiqueDette = HistoriqueDette::where('gestionnaire_id', $gestionnaire->id)
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'gestionnaire' => $gestionnaire->only(['id', 'name', 'username', 'telephone', 'email']),
            'kpis' => [
                'total_tout_temps' => Money::format(Versement::where('gestionnaire_id', $gestionnaire->id)->sum('montant')),
                'nombre_versements' => Versement::where('gestionnaire_id', $gestionnaire->id)->count(),
                'solde_du' => Money::format($gestionnaire->dette),
                'solde_du_brut' => (float) $gestionnaire->dette,
            ],
            'versements_recents' => $versementsRecents->map(fn (Versement $versement) => [
                'date' => $versement->date_versement->format('d/m/Y'),
                'montant' => Money::format($versement->montant),
                'mode_paiement' => $versement->modePaiement->libelle,
                'vehicule_code' => $versement->vehicule_code,
                'reference' => $versement->reference,
                'commentaire' => $versement->commentaire,
            ]),
            'historique_dette' => $historiqueDette->map(fn (HistoriqueDette $h) => [
                'date' => $h->type === 'bascule'
                    ? $h->date_reference?->format('d/m/Y')
                    : $h->created_at->format('d/m/Y H:i'),
                'type' => $h->type,
                'montant' => Money::format($h->montant),
                'auteur' => $h->user?->name,
                'motif' => $h->motif,
            ]),
        ]);
    }

    public function annulerDette(User $gestionnaire, AnnulerDetteRequest $request): JsonResponse
    {
        abort_unless($gestionnaire->hasRole('gestionnaire'), 404);

        try {
            $this->detteService->annuler(
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
            'message' => "Dette de {$gestionnaire->name} mise à jour.",
            'solde_du' => Money::format($gestionnaire->dette),
            'solde_du_brut' => (float) $gestionnaire->dette,
        ]);
    }
}
