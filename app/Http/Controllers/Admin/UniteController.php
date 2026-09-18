<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUniteRequest;
use App\Http\Requests\Admin\UpdateUniteRequest;
use App\Models\Unite;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UniteController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Unite::class);

        $unites = Unite::withCount('articles')->orderBy('libelle')->get();

        return view('admin.unites.index', compact('unites'));
    }

    public function store(StoreUniteRequest $request): JsonResponse
    {
        $unite = Unite::create($request->validated() + ['actif' => $request->boolean('actif', true)]);

        return response()->json([
            'message' => "Unité « {$unite->libelle} » créée.",
            'unite' => $unite,
        ], 201);
    }

    public function update(UpdateUniteRequest $request, Unite $unite): JsonResponse
    {
        $unite->update($request->validated() + ['actif' => $request->boolean('actif', true)]);

        return response()->json([
            'message' => "Unité « {$unite->libelle} » mise à jour.",
            'unite' => $unite,
        ]);
    }

    public function destroy(Unite $unite): JsonResponse
    {
        Gate::authorize('delete', $unite);

        $unite->delete();

        return response()->json(['message' => "Unité « {$unite->libelle} » archivée."]);
    }
}
