<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreBonCommandeRequest;
use App\Models\Article;
use App\Models\BonCommande;
use App\Models\Fournisseur;
use App\Services\Stock\BonCommandeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BonCommandeController extends Controller
{
    public function __construct(private readonly BonCommandeService $bonCommandeService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', BonCommande::class);

        $fournisseurs = Fournisseur::where('actif', true)->orderBy('nom')->get();
        $articles = Article::where('actif', true)->orderBy('nom')->get();

        return view('stock.bons-commande.index', compact('fournisseurs', 'articles'));
    }

    public function show(BonCommande $bonCommande): JsonResponse
    {
        Gate::authorize('view', $bonCommande);

        return response()->json($bonCommande->load('lignes'));
    }

    public function data(): JsonResponse
    {
        Gate::authorize('viewAny', BonCommande::class);

        // select('bons_commande.*') doit venir AVANT withSum() : un select() après withSum()
        // remplace tout le tableau de colonnes selectionnees (y compris les sous-requetes
        // d'agregat ajoutees par withSum), ce qui faisait remonter 0/0 au lieu du vrai total.
        $query = BonCommande::query()
            ->select('bons_commande.*')
            ->withSum('lignes as total_commande', 'quantite_commandee')
            ->withSum('lignes as total_recu', 'quantite_recue');

        return DataTables::of($query)
            ->editColumn('date_commande', fn (BonCommande $bc) => $bc->date_commande->format('d/m/Y'))
            ->addColumn('taux_reception', fn (BonCommande $bc) => ((int) $bc->total_recu).' / '.((int) $bc->total_commande))
            ->addColumn('statut_badge', function (BonCommande $bc) {
                $classes = [
                    'en_attente' => 'bg-secondary',
                    'partiellement_recu' => 'bg-warning text-dark',
                    'recu' => 'bg-success',
                    'annule' => 'bg-danger',
                ];
                $libelles = [
                    'en_attente' => 'En attente',
                    'partiellement_recu' => 'Partiellement reçu',
                    'recu' => 'Reçu',
                    'annule' => 'Annulé',
                ];

                return '<span class="badge '.($classes[$bc->statut] ?? 'bg-secondary').'">'.($libelles[$bc->statut] ?? $bc->statut).'</span>';
            })
            ->rawColumns(['statut_badge'])
            ->make(true);
    }

    public function store(StoreBonCommandeRequest $request): JsonResponse
    {
        $bonCommande = $this->bonCommandeService->creer($request->validated() + ['user_id' => $request->user()->id]);

        return response()->json([
            'message' => "Bon de commande #{$bonCommande->id} créé.",
            'bonCommande' => $bonCommande,
        ], 201);
    }

    public function annuler(BonCommande $bonCommande): JsonResponse
    {
        Gate::authorize('update', $bonCommande);

        try {
            $this->bonCommandeService->annuler($bonCommande);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['message' => "Bon de commande #{$bonCommande->id} annulé."]);
    }

    public function destroy(BonCommande $bonCommande): JsonResponse
    {
        Gate::authorize('delete', $bonCommande);

        try {
            $this->bonCommandeService->supprimer($bonCommande);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['message' => "Bon de commande #{$bonCommande->id} supprimé."]);
    }

    public function pdf(BonCommande $bonCommande): Response
    {
        Gate::authorize('view', $bonCommande);

        $bonCommande->load('lignes');

        // stream() (pas download()) : ouvre le PDF dans un nouvel onglet pour aperçu + impression
        // navigateur, plutôt que de forcer un téléchargement — plus adapté à un document à imprimer.
        return Pdf::loadView('exports.pdf.bon-commande', ['bonCommande' => $bonCommande])
            ->setPaper('a4', 'portrait')
            ->stream("bon-commande-{$bonCommande->reference}.pdf");
    }
}
