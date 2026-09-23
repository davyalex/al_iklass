<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flotte\ReglerDetteRequest;
use App\Models\HistoriqueDette;
use App\Models\User;
use App\Services\Flotte\DetteJournalierService;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Gestion de la dette des gestionnaires : bascules automatiques, annulations
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

        // Gestionnaires actuellement en dette, dans le périmètre déjà scopé
        // (tous pour un admin, uniquement soi-même sinon).
        $gestionnairesEnDette = User::role('gestionnaire')
            ->when(! $peutVoirTout, fn (Builder $q) => $q->whereKey($request->user()->id))
            ->where('dette', '>', 0)
            ->orderBy('name')
            ->get();

        $kpis = $this->calculerKpis($request, $peutVoirTout);

        return view('flotte.dettes.index', compact('gestionnairesEnDette', 'kpis', 'peutVoirTout'));
    }

    public function kpis(Request $request): JsonResponse
    {
        $this->autoriserAcces($request);

        return response()->json($this->calculerKpis($request, $request->user()->can('flotte.dette.gerer')));
    }

    /**
     * Détail de la dette d'un gestionnaire : les jours qui ont généré de la
     * dette (bascule), avec pour chacun ce qu'il devait verser, ce qu'il a
     * versé et ce qu'il reste — et séparément les règlements/annulations
     * qui ont depuis réduit le solde.
     */
    public function detail(User $gestionnaire): JsonResponse
    {
        $this->autoriserAccesGestionnaire($gestionnaire);

        $jours = HistoriqueDette::where('gestionnaire_id', $gestionnaire->id)
            ->where('type', 'bascule')
            ->orderByDesc('date_reference')
            ->get()
            ->map(fn (HistoriqueDette $h) => [
                'date' => $h->date_reference?->format('d/m/Y'),
                'attendu' => Money::format((float) $h->attendu),
                'deja_verse' => Money::format((float) $h->deja_verse),
                'reste' => Money::format((float) $h->montant),
            ]);

        $mouvements = HistoriqueDette::where('gestionnaire_id', $gestionnaire->id)
            ->whereIn('type', ['reglement', 'annulation'])
            ->with('user')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (HistoriqueDette $h) => [
                'date' => $h->created_at->format('d/m/Y'),
                'type' => $h->type,
                'montant' => Money::format((float) $h->montant),
                'motif' => $h->motif,
                'auteur' => $h->user?->name,
            ]);

        return response()->json([
            'gestionnaire' => $gestionnaire->only(['id', 'name']),
            'solde_du' => Money::format((float) $gestionnaire->dette),
            'jours' => $jours,
            'mouvements' => $mouvements,
        ]);
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

    private function autoriserAcces(Request $request): void
    {
        abort_unless(
            $request->user()->can('flotte.dette.gerer') || $request->user()->can('flotte.dette.regler'),
            403
        );
    }

    private function autoriserAccesGestionnaire(User $gestionnaire): void
    {
        $user = request()->user();

        abort_unless(
            $user->can('flotte.dette.gerer') || ($user->can('flotte.dette.regler') && $gestionnaire->id === $user->id),
            403
        );
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
