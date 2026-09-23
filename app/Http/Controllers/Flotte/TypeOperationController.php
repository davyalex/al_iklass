<?php

namespace App\Http\Controllers\Flotte;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flotte\StoreTypeOperationRequest;
use App\Http\Requests\Flotte\UpdateTypeOperationRequest;
use App\Models\TypeOperation;
use Illuminate\Http\JsonResponse;

class TypeOperationController extends Controller
{
    // Accès protégé par le middleware de route 'permission:operations.type.gerer' (routes/flotte.php).
    public function store(StoreTypeOperationRequest $request): JsonResponse
    {
        $type = TypeOperation::create($request->validated() + ['actif' => $request->boolean('actif', true)]);

        return response()->json([
            'message' => "Type d'opération « {$type->libelle} » créé.",
            'type' => $type,
        ], 201);
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
