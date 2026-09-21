<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Models\ModePaiement;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Versement;
use App\Support\Money;
use App\Support\VehiculeKpis;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GestionnaireController extends Controller
{
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

        return response()->json([
            'gestionnaire' => $gestionnaire->only(['id', 'name', 'username', 'telephone', 'email']),
            'kpis' => [
                'total_tout_temps' => Money::format(Versement::where('gestionnaire_id', $gestionnaire->id)->sum('montant')),
                'nombre_versements' => Versement::where('gestionnaire_id', $gestionnaire->id)->count(),
                'solde_du' => Money::format($gestionnaire->dette),
            ],
            'versements_recents' => $versementsRecents->map(fn (Versement $versement) => [
                'date' => $versement->date_versement->format('d/m/Y'),
                'montant' => Money::format($versement->montant),
                'mode_paiement' => $versement->modePaiement->libelle,
                'vehicule_code' => $versement->vehicule_code,
                'reference' => $versement->reference,
                'commentaire' => $versement->commentaire,
            ]),
        ]);
    }
}
