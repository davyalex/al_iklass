<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreArticleRequest;
use App\Http\Requests\Stock\UpdateArticleRequest;
use App\Models\Article;
use App\Models\CategorieArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Article::class);

        $articles = Article::query()
            ->with('categorie')
            ->when(request('categorie_id'), fn ($q) => $q->where('categorie_id', request('categorie_id')))
            ->orderBy('nom')
            ->paginate(24)
            ->withQueryString();

        $categories = CategorieArticle::where('actif', true)->orderBy('libelle')->get();

        return view('stock.articles.index', compact('articles', 'categories'));
    }

    public function show(Article $article): JsonResponse
    {
        Gate::authorize('view', $article);

        return response()->json($article);
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $article = Article::create($request->validated() + ['actif' => $request->boolean('actif', true)]);

        return response()->json([
            'message' => "Article « {$article->nom} » créé.",
            'article' => $article,
        ], 201);
    }

    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        $article->update($request->validated() + ['actif' => $request->boolean('actif', true)]);

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
}
