<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestaurerSauvegardeRequest;
use App\Models\Parametre;
use App\Services\Admin\SauvegardeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SauvegardeController extends Controller
{
    public function creer(SauvegardeService $service): JsonResponse
    {
        Gate::authorize('update', $this->parametreSauvegarde());

        $nom = $service->creer();
        $service->purger();

        return response()->json(['message' => "Sauvegarde « {$nom} » créée."]);
    }

    public function telecharger(string $nom, SauvegardeService $service): BinaryFileResponse
    {
        Gate::authorize('update', $this->parametreSauvegarde());

        return response()->download($service->cheminSecurise($nom));
    }

    public function restaurer(RestaurerSauvegardeRequest $request, string $nom, SauvegardeService $service): JsonResponse
    {
        Gate::authorize('update', $this->parametreSauvegarde());

        $service->restaurer($nom);

        return response()->json(['message' => "Base de données restaurée depuis « {$nom} »."]);
    }

    private function parametreSauvegarde(): Parametre
    {
        return Parametre::where('cle', 'sauvegarde.heure_execution')->firstOrFail();
    }
}
