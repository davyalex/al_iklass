<?php

namespace App\Services\Admin;

use App\Models\Parametre;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Identité visuelle de l'application (nom, logo, favicons) telle que
 * paramétrée dans Administration > Paramètres, lue partout (sidebar, page de
 * connexion, favicons, manifeste PWA, PDF). Mise en cache ; le cache est vidé
 * par App\Observers\ParametreObserver à chaque modification.
 *
 * Sans logo téléversé, les icônes par défaut de public/icones/ sont utilisées.
 */
class IdentiteApplicationService
{
    public const CLE_CACHE = 'application.identite';

    public const NOM_PAR_DEFAUT = 'AL-IKLASS';

    public function __construct(private GenerateurIconesService $generateurIcones) {}

    /**
     * Remplace le logo et régénère favicons, icônes et logo PDF. L'ancien
     * logo n'est supprimé qu'une fois les nouvelles icônes produites.
     */
    public function remplacerLogo(UploadedFile $fichier): string
    {
        $disque = Storage::disk('public');
        $parametre = Parametre::where('cle', 'application.logo')->firstOrFail();
        $ancienChemin = $parametre->valeur;

        $nouveauChemin = $fichier->store('logos', 'public');
        $this->generateurIcones->generer($disque->get($nouveauChemin), $disque);

        $parametre->update(['valeur' => $nouveauChemin]);

        if ($ancienChemin && $ancienChemin !== $nouveauChemin) {
            $disque->delete($ancienChemin);
        }

        return $nouveauChemin;
    }

    /**
     * Retire le logo : retour aux icônes par défaut.
     */
    public function retirerLogo(): void
    {
        $disque = Storage::disk('public');
        $parametre = Parametre::where('cle', 'application.logo')->firstOrFail();

        if ($parametre->valeur) {
            $disque->delete($parametre->valeur);
        }
        $this->generateurIcones->supprimer($disque);

        $parametre->update(['valeur' => '']);
    }

    /**
     * Régénère les icônes depuis le logo actuel (ex : après une restauration
     * du stockage). Retourne false s'il n'y a pas de logo.
     */
    public function regenererIcones(): bool
    {
        $disque = Storage::disk('public');
        $chemin = Parametre::valeur('application.logo');

        if (! $chemin || ! $disque->exists($chemin)) {
            return false;
        }

        $this->generateurIcones->generer($disque->get($chemin), $disque);
        $this->oublier();

        return true;
    }

    /**
     * @return array{nom: string, logo: ?string, version: ?int, icones_generees: bool}
     */
    public function donnees(): array
    {
        return Cache::rememberForever(self::CLE_CACHE, function (): array {
            $parametres = Parametre::whereIn('cle', ['application.nom', 'application.logo'])->get()->keyBy('cle');
            $logo = $parametres->get('application.logo');
            $cheminLogo = $logo?->valeur ?: null;

            return [
                'nom' => $parametres->get('application.nom')?->valeur ?: self::NOM_PAR_DEFAUT,
                'logo' => $cheminLogo,
                'version' => $logo?->updated_at?->timestamp,
                'icones_generees' => $cheminLogo !== null
                    && Storage::disk('public')->exists(GenerateurIconesService::DOSSIER.'/favicon-32.png'),
            ];
        });
    }

    public function nom(): string
    {
        return $this->donnees()['nom'];
    }

    public function aUnLogo(): bool
    {
        return $this->donnees()['logo'] !== null;
    }

    public function logoUrl(): ?string
    {
        $donnees = $this->donnees();

        return $donnees['logo'] === null
            ? null
            : Storage::disk('public')->url($donnees['logo']).'?v='.$donnees['version'];
    }

    /**
     * URL d'une déclinaison du logo (ex : 'favicon-32.png', 'favicon.ico') :
     * celle générée depuis le logo téléversé, sinon l'icône par défaut.
     */
    public function icone(string $fichier): string
    {
        $donnees = $this->donnees();

        if ($donnees['icones_generees']) {
            return Storage::disk('public')->url(GenerateurIconesService::DOSSIER.'/'.$fichier).'?v='.$donnees['version'];
        }

        return asset(GenerateurIconesService::DOSSIER.'/'.$fichier);
    }

    /**
     * Logo compact en data URI pour les PDF (dompdf n'a pas accès aux URL publiques).
     */
    public function logoPdfDataUri(): ?string
    {
        if (! $this->donnees()['icones_generees']) {
            return null;
        }

        $contenu = Storage::disk('public')->get(GenerateurIconesService::DOSSIER.'/'.GenerateurIconesService::LOGO_PDF);

        return $contenu === null ? null : 'data:image/png;base64,'.base64_encode($contenu);
    }

    public function oublier(): void
    {
        Cache::forget(self::CLE_CACHE);
    }
}
