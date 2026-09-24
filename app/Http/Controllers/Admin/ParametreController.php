<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateParametreRequest;
use App\Http\Requests\Admin\UploadLogoRequest;
use App\Models\Parametre;
use App\Services\Admin\IdentiteApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
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

    public function uploaderLogo(UploadLogoRequest $request, IdentiteApplicationService $identiteApplication): JsonResponse
    {
        Gate::authorize('update', Parametre::where('cle', 'application.logo')->firstOrFail());

        $identiteApplication->remplacerLogo($request->file('logo'));

        return response()->json([
            'message' => 'Logo mis à jour. Favicon et icônes régénérés.',
            'url' => $identiteApplication->logoUrl(),
            'icones' => $this->urlsIcones($identiteApplication),
        ]);
    }

    public function retirerLogo(IdentiteApplicationService $identiteApplication): JsonResponse
    {
        Gate::authorize('update', Parametre::where('cle', 'application.logo')->firstOrFail());

        $identiteApplication->retirerLogo();

        return response()->json([
            'message' => 'Logo retiré. Les icônes par défaut sont rétablies.',
            'url' => null,
            'icones' => $this->urlsIcones($identiteApplication),
        ]);
    }

    /**
     * URLs des icônes à jour, pour rafraîchir la page sans rechargement.
     *
     * @return array{favicon: string, apple: string, sidebar: string}
     */
    private function urlsIcones(IdentiteApplicationService $identiteApplication): array
    {
        return [
            'favicon' => $identiteApplication->icone('favicon-32.png'),
            'apple' => $identiteApplication->icone('apple-touch-icon.png'),
            'sidebar' => $identiteApplication->aUnLogo()
                ? $identiteApplication->icone('icone-192.png')
                : asset('icones/favicon.svg'),
        ];
    }
}
