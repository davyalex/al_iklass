<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreCategorieArticleRequest;
use App\Http\Requests\Stock\UpdateCategorieArticleRequest;
use App\Models\CategorieArticle;
use Illuminate\Http\JsonResponse;

class CategorieArticleController extends Controller
{
    private const PREFIXE_CODE = 'CAT';

    public function store(StoreCategorieArticleRequest $request): JsonResponse
    {
        $categorie = CategorieArticle::create([
            'code' => $this->genererCode(),
            'libelle' => $request->validated('libelle'),
            'actif' => $request->boolean('actif', true),
        ]);

        return response()->json([
            'message' => "Catégorie « {$categorie->libelle} » créée.",
            'categorie' => $categorie,
        ] + $this->listes(), 201);
    }

    public function update(UpdateCategorieArticleRequest $request, CategorieArticle $categorieArticle): JsonResponse
    {
        $categorieArticle->update([
            'libelle' => $request->validated('libelle'),
            'actif' => $request->boolean('actif', true),
        ]);

        return response()->json([
            'message' => "Catégorie « {$categorieArticle->libelle} » mise à jour.",
            'categorie' => $categorieArticle,
        ] + $this->listes());
    }

    public function destroy(CategorieArticle $categorieArticle): JsonResponse
    {
        $categorieArticle->delete();

        return response()->json([
            'message' => "Catégorie « {$categorieArticle->libelle} » archivée.",
        ] + $this->listes());
    }

    private function genererCode(): string
    {
        $dernierNumero = CategorieArticle::withTrashed()
            ->where('code', 'like', self::PREFIXE_CODE.'%')
            ->get()
            ->map(fn ($c) => (int) substr($c->code, strlen(self::PREFIXE_CODE)))
            ->max() ?? 0;

        return self::PREFIXE_CODE.str_pad((string) ($dernierNumero + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{categoriesActives: array, categoriesToutes: array}
     */
    private function listes(): array
    {
        return [
            'categoriesActives' => CategorieArticle::where('actif', true)->orderBy('libelle')->get(),
            'categoriesToutes' => CategorieArticle::orderBy('libelle')->get(),
        ];
    }
}
