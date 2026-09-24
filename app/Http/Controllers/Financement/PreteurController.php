<?php

namespace App\Http\Controllers\Financement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Financement\StorePreteurRequest;
use App\Models\Preteur;
use App\Models\RemboursementFinancement;
use App\Models\TypePreteur;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PreteurController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Preteur::class);

        $preteurs = Preteur::query()
            ->withCount('financements')
            ->withSum('financements as solde_du', 'montant_restant')
            ->when($request->filled('nom'), fn (Builder $q) => $q->where('nom', 'like', '%'.$request->string('nom').'%'))
            ->when($request->filled('type_preteur_id'), fn (Builder $q) => $q->where('type_preteur_id', $request->integer('type_preteur_id')))
            ->when($request->filled('actif'), fn (Builder $q) => $q->where('actif', $request->input('actif') === '1'))
            ->orderBy('nom')
            ->paginate(20)
            ->appends($request->query());

        $typesPreteur = TypePreteur::where('actif', true)->orderBy('libelle')->get();

        return view('financements.preteurs.index', compact('preteurs', 'typesPreteur'));
    }

    public function show(Preteur $preteur): JsonResponse
    {
        Gate::authorize('view', $preteur);

        return response()->json($preteur);
    }

    public function compte(Preteur $preteur): View
    {
        Gate::authorize('view', $preteur);

        $financements = $preteur->financements()->orderByDesc('date_financement')->get();
        $remboursements = $this->remboursementsDuPreteur($preteur);
        $kpis = $this->calculerKpisCompte($financements);
        $mouvements = $this->construireHistorique($financements, $remboursements);

        return view('financements.preteurs.compte', compact('preteur', 'financements', 'remboursements', 'kpis', 'mouvements'));
    }

    public function comptePdf(Preteur $preteur): Response
    {
        Gate::authorize('view', $preteur);

        $financements = $preteur->financements()->orderByDesc('date_financement')->get();
        $remboursements = $this->remboursementsDuPreteur($preteur);
        $kpis = $this->calculerKpisCompte($financements);
        $mouvements = $this->construireHistorique($financements, $remboursements);

        return Pdf::loadView('exports.pdf.preteur-compte', compact('preteur', 'financements', 'remboursements', 'kpis', 'mouvements'))
            ->setPaper('a4', 'portrait')
            ->stream('compte-'.Str::slug($preteur->nom).'.pdf');
    }

    public function store(StorePreteurRequest $request): JsonResponse
    {
        $type = TypePreteur::findOrFail($request->validated('type_preteur_id'));

        $preteur = Preteur::create($request->validated() + [
            'type_preteur_code' => $type->code,
            'type_preteur_libelle' => $type->libelle,
            'actif' => $request->boolean('actif', true),
        ]);

        return response()->json([
            'message' => "Prêteur « {$preteur->nom} » créé.",
            'preteur' => $preteur,
        ], 201);
    }

    public function update(StorePreteurRequest $request, Preteur $preteur): JsonResponse
    {
        $type = TypePreteur::findOrFail($request->validated('type_preteur_id'));

        $preteur->update($request->validated() + [
            'type_preteur_code' => $type->code,
            'type_preteur_libelle' => $type->libelle,
            'actif' => $request->boolean('actif', true),
        ]);

        return response()->json([
            'message' => "Prêteur « {$preteur->nom} » mis à jour.",
            'preteur' => $preteur,
        ]);
    }

    public function destroy(Preteur $preteur): JsonResponse
    {
        Gate::authorize('delete', $preteur);

        $preteur->delete();

        return response()->json(['message' => "Prêteur « {$preteur->nom} » archivé."]);
    }

    private function remboursementsDuPreteur(Preteur $preteur): Collection
    {
        return RemboursementFinancement::whereHas('financement', fn ($q) => $q->where('preteur_id', $preteur->id))
            ->with('modePaiement', 'financement')
            ->orderByDesc('date_remboursement')
            ->get();
    }

    /**
     * @return array{total_emprunte: float, deja_rembourse: float, reste_a_rembourser: float}
     */
    private function calculerKpisCompte(Collection $financements): array
    {
        return [
            'total_emprunte' => (float) $financements->sum('montant_total'),
            'deja_rembourse' => (float) $financements->sum('montant_rembourse'),
            'reste_a_rembourser' => (float) $financements->sum('montant_restant'),
        ];
    }

    /**
     * Historique chronologique des mouvements du compte (financements reçus +
     * remboursements effectués), pour tracer les échanges avec ce prêteur en
     * un seul coup d'œil.
     *
     * @return SupportCollection<int, array{date: Carbon, type: string, libelle: string, montant: float}>
     */
    private function construireHistorique(Collection $financements, Collection $remboursements): SupportCollection
    {
        $lignesFinancements = $financements->map(fn ($financement) => [
            'date' => $financement->date_financement,
            'type' => 'financement',
            'libelle' => 'Emprunt '.($financement->reference ?? "#{$financement->id}"),
            'montant' => (float) $financement->montant_total,
        ]);

        $lignesRemboursements = $remboursements->map(fn ($remboursement) => [
            'date' => $remboursement->date_remboursement,
            'type' => 'remboursement',
            'libelle' => 'Remboursement '.($remboursement->reference ?? "#{$remboursement->id}").' — '.($remboursement->modePaiement?->libelle ?? ''),
            'montant' => (float) $remboursement->montant,
        ]);

        return $lignesFinancements->concat($lignesRemboursements)->sortByDesc('date')->values();
    }
}
