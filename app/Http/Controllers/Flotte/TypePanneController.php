<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flotte\StoreTypePanneRequest;
use App\Http\Requests\Flotte\UpdateTypePanneRequest;
use App\Models\TypePanne;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class TypePanneController extends Controller
{
    // Accès protégé par le middleware de route 'permission:interventions.type.gerer' (routes/flotte.php).
    public function store(StoreTypePanneRequest $request): JsonResponse
    {
        $type = TypePanne::create($request->validated() + [
            'code' => $this->genererCodeUnique($request->validated('libelle')),
            'actif' => $request->boolean('actif', true),
        ]);

        return response()->json([
            'message' => "Type de panne « {$type->libelle} » créé.",
            'type' => $type,
        ], 201);
    }

    /**
     * Dérive un code stable (identifiant snapshotté sur chaque intervention)
     * à partir du libellé saisi — pas de champ code exposé à l'utilisateur.
     */
    private function genererCodeUnique(string $libelle): string
    {
        $base = Str::slug($libelle, '_');
        $code = $base;
        $suffixe = 2;

        while (TypePanne::where('code', $code)->exists()) {
            $code = "{$base}_{$suffixe}";
            $suffixe++;
        }

        return $code;
    }

    public function update(UpdateTypePanneRequest $request, TypePanne $type): JsonResponse
    {
        $type->update($request->validated() + ['actif' => $request->boolean('actif', true)]);

        return response()->json([
            'message' => "Type de panne « {$type->libelle} » mis à jour.",
            'type' => $type,
        ]);
    }
}
