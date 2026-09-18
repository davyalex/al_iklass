<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreFournisseurRequest;
use App\Models\Fournisseur;
use App\Models\PaiementFournisseur;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FournisseurController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Fournisseur::class);

        $fournisseurs = Fournisseur::query()
            ->withCount('achats')
            ->withSum('achats as solde_du', 'montant_restant')
            ->orderBy('nom')
            ->paginate(20);

        return view('stock.fournisseurs.index', compact('fournisseurs'));
    }

    public function show(Fournisseur $fournisseur): JsonResponse
    {
        Gate::authorize('view', $fournisseur);

        return response()->json($fournisseur);
    }

    public function compte(Fournisseur $fournisseur): View
    {
        Gate::authorize('view', $fournisseur);

        $bonsCommande = $fournisseur->bonsCommande()->with('lignes')->orderByDesc('date_commande')->get();
        $achats = $fournisseur->achats()->orderByDesc('date_achat')->get();
        $paiements = $this->paiementsDuFournisseur($fournisseur);
        $kpis = $this->calculerKpisCompte($achats, $bonsCommande);
        $mouvements = $this->construireHistorique($achats, $paiements);

        return view('stock.fournisseurs.compte', compact('fournisseur', 'bonsCommande', 'achats', 'paiements', 'kpis', 'mouvements'));
    }

    public function comptePdf(Fournisseur $fournisseur): Response
    {
        Gate::authorize('view', $fournisseur);

        $bonsCommande = $fournisseur->bonsCommande()->with('lignes')->orderByDesc('date_commande')->get();
        $achats = $fournisseur->achats()->orderByDesc('date_achat')->get();
        $paiements = $this->paiementsDuFournisseur($fournisseur);
        $kpis = $this->calculerKpisCompte($achats, $bonsCommande);
        $mouvements = $this->construireHistorique($achats, $paiements);

        return Pdf::loadView('exports.pdf.fournisseur-compte', compact('fournisseur', 'bonsCommande', 'achats', 'paiements', 'kpis', 'mouvements'))
            ->setPaper('a4', 'portrait')
            ->stream('compte-'.Str::slug($fournisseur->nom).'.pdf');
    }

    public function store(StoreFournisseurRequest $request): JsonResponse
    {
        $fournisseur = Fournisseur::create($request->validated() + ['actif' => $request->boolean('actif', true)]);

        return response()->json([
            'message' => "Fournisseur « {$fournisseur->nom} » créé.",
            'fournisseur' => $fournisseur,
        ], 201);
    }

    public function update(StoreFournisseurRequest $request, Fournisseur $fournisseur): JsonResponse
    {
        $fournisseur->update($request->validated() + ['actif' => $request->boolean('actif', true)]);

        return response()->json([
            'message' => "Fournisseur « {$fournisseur->nom} » mis à jour.",
            'fournisseur' => $fournisseur,
        ]);
    }

    public function destroy(Fournisseur $fournisseur): JsonResponse
    {
        Gate::authorize('delete', $fournisseur);

        $fournisseur->delete();

        return response()->json(['message' => "Fournisseur « {$fournisseur->nom} » archivé."]);
    }

    private function paiementsDuFournisseur(Fournisseur $fournisseur): Collection
    {
        return PaiementFournisseur::whereHas('achat', fn ($q) => $q->where('fournisseur_id', $fournisseur->id))
            ->with('modePaiement', 'achat')
            ->orderByDesc('date_paiement')
            ->get();
    }

    /**
     * @return array{total_achats: float, total_bons_commande: float, solde_du: float, deja_regle: float, reste_a_regler: float}
     */
    private function calculerKpisCompte(Collection $achats, Collection $bonsCommande): array
    {
        $totalRestant = (float) $achats->sum('montant_restant');

        return [
            'total_achats' => (float) $achats->sum('montant_total'),
            'total_bons_commande' => (float) $bonsCommande->flatMap->lignes->sum('montant_estime'),
            'solde_du' => $totalRestant,
            'deja_regle' => (float) $achats->sum('montant_paye'),
            'reste_a_regler' => $totalRestant,
        ];
    }

    /**
     * Historique chronologique des mouvements du compte (achats + paiements),
     * pour tracer les échanges avec ce fournisseur en un seul coup d'œil.
     *
     * @return SupportCollection<int, array{date: Carbon, type: string, libelle: string, montant: float}>
     */
    private function construireHistorique(Collection $achats, Collection $paiements): SupportCollection
    {
        $lignesAchats = $achats->map(fn ($achat) => [
            'date' => $achat->date_achat,
            'type' => 'achat',
            'libelle' => 'Achat '.($achat->reference ?? "#{$achat->id}"),
            'montant' => (float) $achat->montant_total,
        ]);

        $lignesPaiements = $paiements->map(fn ($paiement) => [
            'date' => $paiement->date_paiement,
            'type' => 'paiement',
            'libelle' => 'Paiement '.($paiement->reference ?? "#{$paiement->id}").' — '.($paiement->modePaiement?->libelle ?? ''),
            'montant' => (float) $paiement->montant,
        ]);

        return $lignesAchats->concat($lignesPaiements)->sortByDesc('date')->values();
    }
}
