<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flotte\StoreTypeOperationRequest;
use App\Http\Requests\Flotte\UpdateTypeOperationRequest;
use App\Models\TypeOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class TypeOperationController extends Controller
{
    // Accès protégé par le middleware de route 'permission:operations.type.gerer' (routes/flotte.php).
    public function store(StoreTypeOperationRequest $request): JsonResponse
    {
        $type = TypeOperation::create($request->validated() + [
            'code' => $this->genererCodeUnique($request->validated('libelle')),
            'actif' => $request->boolean('actif', true),
        ]);

        return response()->json([
            'message' => "Type d'opération « {$type->libelle} » créé.",
            'type' => $type,
        ], 201);
    }

    /**
     * Dérive un code stable (identifiant snapshotté sur chaque programmation)
     * à partir du libellé saisi — pas de champ code exposé à l'utilisateur.
     */
    private function genererCodeUnique(string $libelle): string
    {
        $base = Str::slug($libelle, '_');
        $code = $base;
        $suffixe = 2;

        while (TypeOperation::where('code', $code)->exists()) {
            $code = "{$base}_{$suffixe}";
            $suffixe++;
        }

        return $code;
    }

    public function update(UpdateTypeOperationRequest $request, TypeOperation $type): JsonResponse
    {
        $type->update($request->validated() + ['actif' => $request->boolean('actif', true)]);

        return response()->json([
            'message' => "Type d'opération « {$type->libelle} » mis à jour.",
            'type' => $type,
        ]);
    }
}
