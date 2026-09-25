<?php

namespace App\Console\Commands\Admin;

use App\Models\Parametre;
use App\Services\Admin\SauvegardeService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sauvegarde:creer {--force : Ignore l\'heure configurée et sauvegarde immédiatement}')]
#[Description('Sauvegarde la base de données (planifié chaque minute, ne s\'exécute réellement qu\'à l\'heure configurée dans Paramètres)')]
class SauvegarderBaseDeDonnees extends Command
{
    public function handle(SauvegardeService $service): int
    {
        $heureConfiguree = Parametre::valeur('sauvegarde.heure_execution', '02:00');

        if (! $this->option('force') && now()->format('H:i') !== $heureConfiguree) {
            return self::SUCCESS;
        }

        $nom = $service->creer();
        $nombreSupprimes = $service->purger();

        $this->info("Sauvegarde créée : {$nom}");

        if ($nombreSupprimes > 0) {
            $this->info("{$nombreSupprimes} ancienne(s) sauvegarde(s) supprimée(s) (rétention).");
        }

        return self::SUCCESS;
    }
}
