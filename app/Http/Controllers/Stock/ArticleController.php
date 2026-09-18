<?php

namespace App\Http\Controllers\Stock;

use App\Exports\Stock\ArticlesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreArticleRequest;
use App\Http\Requests\Stock\UpdateArticleRequest;
use App\Models\Article;
use App\Models\CategorieArticle;
use App\Models\Unite;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Article::class);

        $articles = $this->filtrer(Article::query()->with(['categorie', 'unite']), $request)
            ->orderBy('nom')
            ->paginate(12)
            ->withQueryString();

        $categories = CategorieArticle::where('actif', true)->orderBy('libelle')->get();
        $categoriesToutes = CategorieArticle::orderBy('libelle')->get();
        $unites = Unite::where('actif', true)->orderBy('libelle')->get();

        return view('stock.articles.index', compact('articles', 'categories', 'categoriesToutes', 'unites'));
    }

    public function show(Article $article): JsonResponse
    {
        Gate::authorize('view', $article);

        return response()->json($article);
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $article = Article::create([
            ...$request->validated(),
            'reference' => $request->validated('reference') ?: $this->genererReference(),
            'actif' => $request->boolean('actif', true),
        ]);

        return response()->json([
            'message' => "Article « {$article->nom} » créé.",
            'article' => $article,
        ], 201);
    }

    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        $article->update([
            ...$request->validated(),
            'reference' => $request->validated('reference') ?: $this->genererReference(),
            'actif' => $request->boolean('actif', true),
        ]);

        return response()->json([
            'message' => "Article « {$article->nom} » mis à jour.",
            'article' => $article,
        ]);
    }

    public function destroy(Article $article): JsonResponse
    {
        Gate::authorize('delete', $article);

        $article->delete();

        return response()->json(['message' => "Article « {$article->nom} » archivé."]);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Article::class);

        $articles = $this->filtrer(Article::query()->with(['categorie', 'unite']), $request)->orderBy('nom')->get();

        return Excel::download(new ArticlesExport($articles), 'articles-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        Gate::authorize('viewAny', Article::class);

        $articles = $this->filtrer(Article::query()->with(['categorie', 'unite']), $request)->orderBy('nom')->get();

        return Pdf::loadView('exports.pdf.articles', ['articles' => $articles])
            ->setPaper('a4', 'landscape')
            ->download('articles-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('categorie_id'), fn (Builder $q) => $q->where('categorie_id', $request->integer('categorie_id')))
            ->when($request->boolean('en_alerte'), fn (Builder $q) => $q->enAlerte());
    }

    private function genererReference(): string
    {
        $dernierNumero = Article::withTrashed()
            ->where('reference', 'like', 'ART%')
            ->get()
            ->map(fn ($a) => (int) substr($a->reference, 3))
            ->max() ?? 0;

        return 'ART'.str_pad((string) ($dernierNumero + 1), 4, '0', STR_PAD_LEFT);
    }
}
