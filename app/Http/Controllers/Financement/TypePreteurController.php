<?php

namespace App\Http\Controllers\Financement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Financement\StoreTypePreteurRequest;
use App\Http\Requests\Financement\UpdateTypePreteurRequest;
use App\Models\TypePreteur;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class TypePreteurController extends Controller
{
    // Accès protégé par le middleware de route 'permission:financements.type.gerer' (routes/financements.php).
    public function store(StoreTypePreteurRequest $request): JsonResponse
    {
        $type = TypePreteur::create($request->validated() + [
            'code' => $this->genererCodeUnique($request->validated('libelle')),
            'actif' => $request->boolean('actif', true),
        ]);

        return response()->json([
            'message' => "Type de prêteur « {$type->libelle} » créé.",
            'type' => $type,
        ], 201);
    }

    /**
     * Dérive un code stable (identifiant snapshotté sur chaque prêteur)
     * à partir du libellé saisi — pas de champ code exposé à l'utilisateur.
     */
    private function genererCodeUnique(string $libelle): string
    {
        $base = Str::slug($libelle, '_');
        $code = $base;
        $suffixe = 2;

        while (TypePreteur::where('code', $code)->exists()) {
            $code = "{$base}_{$suffixe}";
            $suffixe++;
        }

        return $code;
    }

    public function update(UpdateTypePreteurRequest $request, TypePreteur $type): JsonResponse
    {
        $type->update($request->validated() + ['actif' => $request->boolean('actif', true)]);

        return response()->json([
            'message' => "Type de prêteur « {$type->libelle} » mis à jour.",
            'type' => $type,
        ]);
    }
}
