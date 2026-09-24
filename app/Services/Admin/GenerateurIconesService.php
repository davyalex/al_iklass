<?php

namespace App\Services\Admin;

use GdImage;
use Illuminate\Contracts\Filesystem\Filesystem;
use RuntimeException;

/**
 * Déclinaisons du logo de l'application générées à chaque téléversement :
 * favicons (PNG + .ico multi-tailles), icône Apple (écran d'accueil iOS),
 * icônes PWA (écran d'accueil Android) et une version compacte pour l'en-tête
 * des PDF. Le logo est toujours centré sans déformation dans un carré.
 *
 * Les icônes « écran d'accueil » ont un fond blanc (iOS remplit la
 * transparence en noir) ; les favicons gardent la transparence.
 */
class GenerateurIconesService
{
    public const DOSSIER = 'icones';

    /**
     * Fichiers produits : nom => [taille en px, fond blanc ?, marge en % du côté].
     *
     * @var array<string, array{0: int, 1: bool, 2: float}>
     */
    public const DECLINAISONS = [
        'favicon-16.png' => [16, false, 0.0],
        'favicon-32.png' => [32, false, 0.04],
        'favicon-48.png' => [48, false, 0.04],
        'apple-touch-icon.png' => [180, true, 0.1],
        'icone-192.png' => [192, true, 0.1],
        'icone-512.png' => [512, true, 0.1],
        'icone-maskable-512.png' => [512, true, 0.2],
    ];

    public const FAVICON_ICO = 'favicon.ico';

    public const LOGO_PDF = 'logo-pdf.png';

    /**
     * Génère toutes les déclinaisons dans le dossier `icones/` du disque donné.
     */
    public function generer(string $contenuImageSource, Filesystem $disque): void
    {
        $source = @imagecreatefromstring($contenuImageSource);
        if (! $source instanceof GdImage) {
            throw new RuntimeException('Image illisible : impossible de générer les icônes.');
        }

        $pngsFavicon = [];
        foreach (self::DECLINAISONS as $nomFichier => [$taille, $fondBlanc, $marge]) {
            $png = $this->encoderPng($this->carre($source, $taille, $fondBlanc, $marge));
            $disque->put(self::DOSSIER.'/'.$nomFichier, $png);

            if (str_starts_with($nomFichier, 'favicon-')) {
                $pngsFavicon[$taille] = $png;
            }
        }

        $disque->put(self::DOSSIER.'/'.self::FAVICON_ICO, $this->construireIco($pngsFavicon));
        $disque->put(self::DOSSIER.'/'.self::LOGO_PDF, $this->encoderPng($this->redimensionnerDansCadre($source, 360, 144)));
    }

    public function supprimer(Filesystem $disque): void
    {
        $disque->deleteDirectory(self::DOSSIER);
    }

    /**
     * Carré de `$taille` px contenant le logo centré, sans déformation.
     */
    private function carre(GdImage $source, int $taille, bool $fondBlanc, float $marge): GdImage
    {
        $canevas = $this->canevas($taille, $taille, $fondBlanc);
        $zone = (int) max(1, round($taille * (1 - 2 * $marge)));
        [$largeur, $hauteur] = $this->dimensionsContenues(imagesx($source), imagesy($source), $zone, $zone);

        imagecopyresampled(
            $canevas, $source,
            intdiv($taille - $largeur, 2), intdiv($taille - $hauteur, 2), 0, 0,
            $largeur, $hauteur, imagesx($source), imagesy($source),
        );

        return $canevas;
    }

    /**
     * Logo réduit pour tenir dans le cadre donné, proportions conservées, fond transparent.
     */
    private function redimensionnerDansCadre(GdImage $source, int $largeurMax, int $hauteurMax): GdImage
    {
        [$largeur, $hauteur] = $this->dimensionsContenues(imagesx($source), imagesy($source), $largeurMax, $hauteurMax);
        $canevas = $this->canevas($largeur, $hauteur, false);
        imagecopyresampled($canevas, $source, 0, 0, 0, 0, $largeur, $hauteur, imagesx($source), imagesy($source));

        return $canevas;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function dimensionsContenues(int $largeurSource, int $hauteurSource, int $largeurMax, int $hauteurMax): array
    {
        $ratio = min($largeurMax / $largeurSource, $hauteurMax / $hauteurSource);

        return [
            (int) max(1, round($largeurSource * $ratio)),
            (int) max(1, round($hauteurSource * $ratio)),
        ];
    }

    private function canevas(int $largeur, int $hauteur, bool $fondBlanc): GdImage
    {
        $canevas = imagecreatetruecolor($largeur, $hauteur);
        imagealphablending($canevas, false);
        imagesavealpha($canevas, true);
        imagefill($canevas, 0, 0, $fondBlanc
            ? imagecolorallocate($canevas, 255, 255, 255)
            : imagecolorallocatealpha($canevas, 0, 0, 0, 127));
        imagealphablending($canevas, true);

        return $canevas;
    }

    private function encoderPng(GdImage $image): string
    {
        imagesavealpha($image, true);
        ob_start();
        imagepng($image, null, 9);

        return (string) ob_get_clean();
    }

    /**
     * Fichier .ico contenant directement les PNG (format accepté par tous les
     * navigateurs actuels et par Windows depuis Vista).
     *
     * @param  array<int, string>  $pngsParTaille
     */
    private function construireIco(array $pngsParTaille): string
    {
        ksort($pngsParTaille);
        $entete = pack('vvv', 0, 1, count($pngsParTaille));
        $repertoire = '';
        $donnees = '';
        $decalage = 6 + 16 * count($pngsParTaille);

        foreach ($pngsParTaille as $taille => $png) {
            $repertoire .= pack(
                'CCCCvvVV',
                $taille >= 256 ? 0 : $taille, $taille >= 256 ? 0 : $taille,
                0, 0, 1, 32, strlen($png), $decalage,
            );
            $donnees .= $png;
            $decalage += strlen($png);
        }

        return $entete.$repertoire.$donnees;
    }
}
