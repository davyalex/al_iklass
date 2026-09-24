<?php

namespace App\Http\Controllers;

use App\Services\Admin\IdentiteApplicationService;
use Illuminate\Http\JsonResponse;

/**
 * Manifeste d'application web : permet d'« ajouter à l'écran d'accueil » sur
 * smartphone/tablette avec le nom et le logo paramétrés de l'entreprise.
 */
class ManifestController extends Controller
{
    public function __invoke(IdentiteApplicationService $identiteApplication): JsonResponse
    {
        $nom = $identiteApplication->nom();

        return response()->json([
            'name' => $nom,
            'short_name' => mb_strimwidth($nom, 0, 12, ''),
            'description' => 'Gestion de flotte, stock et finances',
            'lang' => 'fr',
            'start_url' => route('dashboard', absolute: false),
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#f2f4f7',
            'theme_color' => '#073763',
            'icons' => [
                ['src' => $identiteApplication->icone('icone-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $identiteApplication->icone('icone-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $identiteApplication->icone('icone-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ])->header('Content-Type', 'application/manifest+json');
    }
}
