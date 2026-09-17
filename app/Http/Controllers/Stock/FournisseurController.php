<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreFournisseurRequest;
use App\Models\Fournisseur;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FournisseurController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Fournisseur::class);

        $fournisseurs = Fournisseur::query()
            ->withCount('achats')
            ->orderBy('nom')
            ->paginate(20);

        return view('stock.fournisseurs.index', compact('fournisseurs'));
    }

    public function show(Fournisseur $fournisseur): JsonResponse
    {
        Gate::authorize('view', $fournisseur);

        return response()->json($fournisseur);
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
}
