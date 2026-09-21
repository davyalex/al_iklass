<?php

namespace App\Http\Controllers\Stock;

use App\Exports\Stock\EtatStockExport;
use App\Http\Controllers\Controller;
use App\Models\Achat;
use App\Models\Article;
use App\Models\CategorieArticle;
use App\Models\MouvementStock;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class EtatStockController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Article::class);

        $articles = Article::where('actif', true)->orderBy('nom')->get();
        $categories = CategorieArticle::where('actif', true)->orderBy('libelle')->get();
        // Au premier chargement, aucun filtre n'est encore appliqué : la période
        // par défaut est donc le mois en cours (cahier des charges §6).
        $kpis = $this->calculerKpis($request);

        return view('stock.etat-stock.index', compact('articles', 'categories', 'kpis'));
    }

    public function kpis(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Article::class);

        return response()->json($this->calculerKpis($request));
    }

    public function detail(Article $article): JsonResponse
    {
        Gate::authorize('view', $article);

        $article->load(['categorie', 'unite']);

        $mouvements = MouvementStock::where('article_id', $article->id)
            ->orderByDesc('date_mouvement')
            ->limit(10)
            ->get();

        return response()->json([
            'article' => $article,
            'mouvements' => $mouvements,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Article::class);

        $query = $this->filtrer(Article::query()->with(['categorie', 'unite']), $request)
            ->orderByRaw('(quantite_stock <= seuil_alerte) desc')
            ->orderBy('nom');

        return DataTables::of($query)
            ->editColumn('prix_achat', fn (Article $a) => Money::format((float) $a->prix_achat).' FCFA')
            ->addColumn('categorie_libelle', fn (Article $a) => $a->categorie?->libelle ?? 'Sans catégorie')
            ->addColumn('unite_libelle', fn (Article $a) => $a->unite?->libelle ?? '—')
            ->addColumn('statut_badge', fn (Article $a) => $a->actif
                ? '<span class="badge bg-light text-dark border">Actif</span>'
                : '<span class="badge bg-secondary">Inactif</span>')
            ->addColumn('en_alerte', fn (Article $a) => $a->quantite_stock <= $a->seuil_alerte)
            ->rawColumns(['statut_badge'])
            ->make(true);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Article::class);

        $articles = $this->filtrer(Article::query()->with(['categorie', 'unite']), $request)
            ->orderByRaw('(quantite_stock <= seuil_alerte) desc')
            ->orderBy('nom')
            ->get();

        return Excel::download(new EtatStockExport($articles), 'etat-stock-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        Gate::authorize('viewAny', Article::class);

        $articles = $this->filtrer(Article::query()->with(['categorie', 'unite']), $request)
            ->orderByRaw('(quantite_stock <= seuil_alerte) desc')
            ->orderBy('nom')
            ->get();

        return Pdf::loadView('exports.pdf.etat-stock', ['articles' => $articles])
            ->setPaper('a4', 'landscape')
            ->download('etat-stock-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('article_id'), fn (Builder $q) => $q->where('id', $request->integer('article_id')))
            ->when($request->filled('categorie_id'), fn (Builder $q) => $q->where('categorie_id', $request->integer('categorie_id')))
            ->when($request->boolean('en_alerte'), fn (Builder $q) => $q->enAlerte());
    }

    /**
     * Applique la période demandée (date_debut/date_fin) à la colonne donnée,
     * ou à défaut le mois en cours (cahier des charges §6 : "mois par défaut").
     */
    private function filtrerPeriode(Builder $query, Request $request, string $colonne): Builder
    {
        if (! $request->filled('date_debut') && ! $request->filled('date_fin')) {
            return $query->whereBetween($colonne, [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
        }

        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate($colonne, '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate($colonne, '<=', $request->string('date_fin')));
    }

    /**
     * KPI du tableau de bord Stock (cahier des charges §6) : indicateurs
     * instantanés (état actuel du stock, indépendants de toute période) et
     * indicateurs de période (mois en cours par défaut, filtrable).
     *
     * @return array{
     *     nb_articles: int, total_pieces: int, valeur_stock: float, en_alerte: int, dettes_fournisseurs: float,
     *     achats_periode: float, stock_utilise_interne: float, montant_vendu_externe: float,
     *     entrees_count: int, sorties_interne_count: int, sorties_externe_count: int,
     * }
     */
    private function calculerKpis(Request $request): array
    {
        $achatsPeriode = $this->filtrerPeriode(Achat::query(), $request, 'date_achat');
        $sortiesInternePeriode = $this->filtrerPeriode(MouvementStock::sorties()->interne(), $request, 'date_mouvement');
        $sortiesExternePeriode = $this->filtrerPeriode(MouvementStock::sorties()->externe(), $request, 'date_mouvement');
        $entreesPeriode = $this->filtrerPeriode(MouvementStock::entrees(), $request, 'date_mouvement');

        return [
            // Instantanés : l'état du stock "maintenant" ne dépend d'aucune période.
            'nb_articles' => Article::actif()->count(),
            'total_pieces' => (int) Article::actif()->sum('quantite_stock'),
            'valeur_stock' => (float) Article::query()->selectRaw('COALESCE(SUM(quantite_stock * prix_achat), 0) as total')->value('total'),
            'en_alerte' => Article::actif()->enAlerte()->count(),
            'dettes_fournisseurs' => (float) Achat::query()->sum('montant_restant'),

            // Période (mois en cours par défaut, filtrable) :
            'achats_periode' => (float) (clone $achatsPeriode)->sum('montant_total'),
            'stock_utilise_interne' => (float) (clone $sortiesInternePeriode)
                ->selectRaw('COALESCE(SUM(quantite * prix_unitaire), 0) as total')->value('total'),
            'montant_vendu_externe' => (float) (clone $sortiesExternePeriode)
                ->selectRaw('COALESCE(SUM(quantite * prix_vente), 0) as total')->value('total'),
            'entrees_count' => (clone $entreesPeriode)->count(),
            'sorties_interne_count' => (clone $sortiesInternePeriode)->count(),
            'sorties_externe_count' => (clone $sortiesExternePeriode)->count(),
        ];
    }
}
