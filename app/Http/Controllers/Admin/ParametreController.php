<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateParametreRequest;
use App\Http\Requests\Admin\UploadLogoRequest;
use App\Models\Parametre;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ParametreController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Parametre::class);

        $parametresParGroupe = Parametre::orderBy('ordre')->get()->groupBy('groupe');

        return view('admin.parametres.index', compact('parametresParGroupe'));
    }

    public function update(UpdateParametreRequest $request, Parametre $parametre): JsonResponse
    {
        Gate::authorize('update', $parametre);

        $parametre->update($request->validated());

        return response()->json([
            'message' => "« {$parametre->libelle} » mis à jour.",
            'parametre' => $parametre,
        ]);
    }

    public function uploaderLogo(UploadLogoRequest $request): JsonResponse
    {
        $parametre = Parametre::where('cle', 'application.logo')->firstOrFail();
        Gate::authorize('update', $parametre);

        if ($parametre->valeur) {
            Storage::disk('public')->delete($parametre->valeur);
        }

        $chemin = $request->file('logo')->store('logos', 'public');
        $parametre->update(['valeur' => $chemin]);

        return response()->json([
            'message' => 'Logo mis à jour.',
            'url' => Storage::disk('public')->url($chemin),
        ]);
    }
}
