<?php

namespace App\Http\Controllers\Stock;

use App\Exports\Stock\MouvementsStockExport;
use App\Http\Controllers\Controller;
use App\Models\Article;
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

class MouvementStockController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', MouvementStock::class);

        // Tous les articles (même archivés) : l'historique doit rester
        // consultable pour un article qui a depuis été désactivé.
        $articles = Article::orderBy('nom')->get();
        $articlePreselectionne = $request->filled('article_id')
            ? Article::find($request->integer('article_id'))
            : null;

        return view('stock.mouvements.index', compact('articles', 'articlePreselectionne'));
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MouvementStock::class);

        $query = $this->filtrer(MouvementStock::query()->with(['achat', 'inventaire']), $request)
            ->select('mouvements_stock.*');

        return DataTables::of($query)
            ->editColumn('date_mouvement', fn (MouvementStock $m) => $m->date_mouvement->format('d/m/Y H:i'))
            ->addColumn('article_libelle', fn (MouvementStock $m) => $m->article_reference.' — '.$m->article_nom)
            ->addColumn('type_badge', fn (MouvementStock $m) => $this->typeBadge($m))
            ->addColumn('origine', fn (MouvementStock $m) => $this->origineLibelle($m))
            ->editColumn('prix_unitaire', fn (MouvementStock $m) => Money::format((float) $m->prix_unitaire).' FCFA')
            ->rawColumns(['type_badge'])
            ->make(true);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', MouvementStock::class);

        $mouvements = $this->filtrer(MouvementStock::query()->with(['achat', 'inventaire']), $request)
            ->orderByDesc('date_mouvement')
            ->get();

        return Excel::download(new MouvementsStockExport($mouvements), 'mouvements-stock-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        Gate::authorize('viewAny', MouvementStock::class);

        $mouvements = $this->filtrer(MouvementStock::query()->with(['achat', 'inventaire']), $request)
            ->orderByDesc('date_mouvement')
            ->get();

        return Pdf::loadView('exports.pdf.mouvements', ['mouvements' => $mouvements])
            ->setPaper('a4', 'landscape')
            ->download('mouvements-stock-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_mouvement', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_mouvement', '<=', $request->string('date_fin')))
            ->when($request->filled('article_id'), fn (Builder $q) => $q->where('article_id', $request->integer('article_id')))
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->string('type')));
    }

    public static function typeBadge(MouvementStock $m): string
    {
        return match (true) {
            $m->type === 'entree' && $m->nature === 'ajustement' => '<span class="badge bg-info text-dark">Entrée (ajustement)</span>',
            $m->type === 'entree' => '<span class="badge bg-success">Entrée</span>',
            $m->nature === 'interne' => '<span class="badge bg-warning text-dark">Sortie interne</span>',
            $m->nature === 'externe' => '<span class="badge bg-danger">Sortie externe</span>',
            default => '<span class="badge bg-info text-dark">Sortie (ajustement)</span>',
        };
    }

    public static function typeLibelle(MouvementStock $m): string
    {
        return match (true) {
            $m->type === 'entree' && $m->nature === 'ajustement' => 'Entrée (ajustement)',
            $m->type === 'entree' => 'Entrée',
            $m->nature === 'interne' => 'Sortie interne',
            $m->nature === 'externe' => 'Sortie externe',
            default => 'Sortie (ajustement)',
        };
    }

    public static function origineLibelle(MouvementStock $m): string
    {
        return match (true) {
            $m->achat_id !== null => 'Achat '.($m->achat?->reference ?? '#'.$m->achat_id),
            $m->inventaire_id !== null => 'Inventaire '.($m->inventaire?->reference ?? '#'.$m->inventaire_id),
            $m->nature === 'interne' => trim('Véhicule '.($m->vehicule_code ?? '—').($m->motif ? ' — '.$m->motif : '')),
            $m->nature === 'externe' => 'Vente à '.($m->acheteur ?: '—'),
            default => $m->motif ?? '—',
        };
    }
}
