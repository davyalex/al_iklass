<?php

namespace App\Observers;

use App\Models\Parametre;
use App\Services\Admin\IdentiteApplicationService;

class ParametreObserver
{
    public function __construct(private IdentiteApplicationService $identiteApplication) {}

    /**
     * Le nom et le logo sont mis en cache (sidebar, connexion, favicons, PDF) :
     * toute modification d'un paramètre d'identité vide ce cache.
     */
    public function saved(Parametre $parametre): void
    {
        $this->oublierSiIdentite($parametre);
    }

    public function deleted(Parametre $parametre): void
    {
        $this->oublierSiIdentite($parametre);
    }

    private function oublierSiIdentite(Parametre $parametre): void
    {
        if (str_starts_with($parametre->cle, 'application.')) {
            $this->identiteApplication->oublier();
        }
    }
}
