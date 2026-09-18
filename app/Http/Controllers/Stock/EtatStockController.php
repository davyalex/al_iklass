<?php

namespace App\Http\Controllers\Stock;

use App\Exports\Stock\EtatStockExport;
use App\Http\Controllers\Controller;
use App\Models\Article;
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
    public function index(): View
    {
        Gate::authorize('viewAny', Article::class);

        $articles = Article::where('actif', true)->orderBy('nom')->get();
        $kpis = $this->calculerKpis();

        return view('stock.etat-stock.index', compact('articles', 'kpis'));
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Article::class);

        $query = $this->filtrer(Article::query()->with(['categorie', 'unite']), $request);

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

        $articles = $this->filtrer(Article::query()->with(['categorie', 'unite']), $request)->orderBy('nom')->get();

        return Excel::download(new EtatStockExport($articles), 'etat-stock-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        Gate::authorize('viewAny', Article::class);

        $articles = $this->filtrer(Article::query()->with(['categorie', 'unite']), $request)->orderBy('nom')->get();

        return Pdf::loadView('exports.pdf.etat-stock', ['articles' => $articles])
            ->setPaper('a4', 'landscape')
            ->download('etat-stock-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('article_id'), fn (Builder $q) => $q->where('id', $request->integer('article_id')))
            ->when($request->boolean('en_alerte'), fn (Builder $q) => $q->enAlerte());
    }

    /**
     * @return array{valeur_stock: float, en_alerte: int, total_pieces: int}
     */
    private function calculerKpis(): array
    {
        return [
            'valeur_stock' => (float) Article::query()->selectRaw('COALESCE(SUM(quantite_stock * prix_achat), 0) as total')->value('total'),
            'en_alerte' => Article::actif()->enAlerte()->count(),
            'total_pieces' => (int) Article::actif()->sum('quantite_stock'),
        ];
    }
}
