<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flotte\RemiseEnCirculationRequest;
use App\Http\Requests\Flotte\StoreVehiculeRequest;
use App\Http\Requests\Flotte\UpdateVehiculeRequest;
use App\Http\Requests\Flotte\UpdateVehiculeStatutRequest;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Support\VehiculeKpis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VehiculeController extends Controller
{
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

        return view('flotte.vehicules.index', compact('vehicules', 'statuts', 'vehiculesParStatut', 'gestionnaires', 'kpis'));
    }

    public function show(Vehicule $vehicule): JsonResponse
    {
        Gate::authorize('view', $vehicule);

        return response()->json($vehicule->load(['statut', 'gestionnaire']));
    }

    public function historique(Vehicule $vehicule): JsonResponse
    {
        Gate::authorize('view', $vehicule);

        $sorties = $vehicule->mouvementsStock()
            ->orderByDesc('date_mouvement')
            ->limit(20)
            ->get()
            ->map(fn ($mouvement) => [
                'type' => 'mouvement_stock',
                'date' => $mouvement->date_mouvement,
                'libelle' => ($mouvement->type === 'sortie' ? 'Sortie' : 'Entrée').' — '.$mouvement->article_nom.' (qté '.$mouvement->quantite.')',
                'detail' => $mouvement->nature,
            ]);

        $changementsStatut = $vehicule->historiqueStatuts()
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($historique) => [
                'type' => 'changement_statut',
                'date' => $historique->created_at,
                'libelle' => ($historique->ancien_statut_libelle ?? 'Création').' → '.$historique->nouveau_statut_libelle,
                'detail' => $historique->user?->name,
            ]);

        $evenements = $sorties->concat($changementsStatut)->sortByDesc('date')->values();

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
        $statutEnCirculationId = StatutVehicule::where('code', 'en_circulation')->value('id');

        $vehicule->commentaireHistorique = $request->validated('rapport');
        $vehicule->update(['statut_id' => $statutEnCirculationId]);

        return response()->json([
            'message' => "Véhicule « {$vehicule->code} » remis en circulation.",
            'vehicule' => $vehicule->load(['statut', 'gestionnaire']),
        ]);
    }
}
