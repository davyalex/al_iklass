<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flotte\RemiseEnCirculationRequest;
use App\Http\Requests\Flotte\StoreVehiculeRequest;
use App\Http\Requests\Flotte\UpdateVehiculeRequest;
use App\Http\Requests\Flotte\UpdateVehiculeStatutRequest;
use App\Models\ModePaiement;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Services\Flotte\InterventionService;
use App\Support\FenetreStatutJournalier;
use App\Support\VehiculeKpis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VehiculeController extends Controller
{
    public function __construct(private readonly InterventionService $interventionService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Vehicule::class);

        $peutVoirTout = $request->user()->can('flotte.vehicule.voir') || $request->user()->can('flotte.vehicule.remise_circulation');

        $vehicules = Vehicule::query()
            ->with(['statut', 'gestionnaire'])
            ->when(
                ! $peutVoirTout && $request->user()->can('flotte.vehicule.voir_affectes'),
                fn ($query) => $query->where('gestionnaire_id', $request->user()->id)
            )
            ->orderBy('code')
            ->get();

        $statuts = StatutVehicule::where('actif', true)->orderBy('id')->get();

        $vehiculesParStatut = $vehicules->groupBy('statut_id');

        $gestionnaires = $request->user()->can('flotte.vehicule.voir')
            ? User::role('gestionnaire')->orderBy('name')->get()
            : collect();

        $kpis = VehiculeKpis::calculer($vehicules, $statuts);
        $modesPaiement = ModePaiement::where('actif', true)->orderBy('libelle')->get();

        // Compte à rebours de la fenêtre horaire : uniquement pertinent pour un
        // gestionnaire soumis au verrou (flotte.vehicule.statut.gerer sans le
        // flotte.vehicule.gerer qui donne un accès permanent à l'admin).
        $fenetreStatut = null;
        if ($request->user()->can('flotte.vehicule.statut.gerer') && ! $request->user()->can('flotte.vehicule.gerer')) {
            [$debutFenetre, $finFenetre] = FenetreStatutJournalier::bornesDuJour();
            $fenetreStatut = ['debut' => $debutFenetre->toIso8601String(), 'fin' => $finFenetre->toIso8601String()];
        }

        return view('flotte.vehicules.index', compact('vehicules', 'statuts', 'vehiculesParStatut', 'gestionnaires', 'kpis', 'fenetreStatut', 'modesPaiement'));
    }

    public function show(Vehicule $vehicule): JsonResponse
    {
        Gate::authorize('view', $vehicule);

        return response()->json($vehicule->load(['statut', 'gestionnaire']));
    }

    public function historique(Vehicule $vehicule): JsonResponse
    {
        Gate::authorize('view', $vehicule);

        $mouvements = $vehicule->mouvementsStock()
            ->with('user')
            ->orderByDesc('date_mouvement')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($mouvement) => [
                'type' => 'mouvement_stock',
                'date' => $mouvement->date_mouvement,
                'libelle' => ($mouvement->type === 'sortie' ? 'Sortie' : 'Entrée').' — '.$mouvement->article_nom.' (qté '.$mouvement->quantite.')',
                'auteur' => $mouvement->user?->name,
                'commentaire' => $mouvement->motif,
            ]);

        $changementsStatut = $vehicule->historiqueStatuts()
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($historique) => [
                'type' => 'changement_statut',
                'date' => $historique->created_at,
                'libelle' => ($historique->ancien_statut_libelle ?? 'Création').' → '.$historique->nouveau_statut_libelle,
                'auteur' => $historique->user?->name,
                'commentaire' => $historique->commentaire,
            ]);

        $evenements = $mouvements->concat($changementsStatut)->sortByDesc('date')->values();

        return response()->json(['evenements' => $evenements]);
    }

    public function store(StoreVehiculeRequest $request): JsonResponse
    {
        $vehicule = Vehicule::create($request->validated());

        return response()->json([
            'message' => "Véhicule « {$vehicule->code} » créé.",
            'vehicule' => $vehicule,
        ], 201);
    }

    public function update(UpdateVehiculeRequest $request, Vehicule $vehicule): JsonResponse
    {
        $vehicule->update($request->validated());

        return response()->json([
            'message' => "Véhicule « {$vehicule->code} » mis à jour.",
            'vehicule' => $vehicule,
        ]);
    }

    public function destroy(Vehicule $vehicule): JsonResponse
    {
        Gate::authorize('delete', $vehicule);

        $vehicule->delete();

        return response()->json(['message' => "Véhicule « {$vehicule->code} » archivé."]);
    }

    public function changerStatut(UpdateVehiculeStatutRequest $request, Vehicule $vehicule): JsonResponse
    {
        $vehicule->update($request->validated());

        return response()->json([
            'message' => "Statut du véhicule « {$vehicule->code} » mis à jour.",
            'vehicule' => $vehicule->load(['statut', 'gestionnaire']),
        ]);
    }

    public function remiseEnCirculation(RemiseEnCirculationRequest $request, Vehicule $vehicule): JsonResponse
    {
        // Clôture aussi, le cas échéant, l'intervention 'en_cours' du véhicule
        // avec ce même rapport (InterventionService::cloturer).
        $this->interventionService->cloturer($vehicule, $request->validated('rapport'), $request->user());

        return response()->json([
            'message' => "Véhicule « {$vehicule->code} » remis en circulation.",
            'vehicule' => $vehicule->fresh()->load(['statut', 'gestionnaire']),
        ]);
    }
}
