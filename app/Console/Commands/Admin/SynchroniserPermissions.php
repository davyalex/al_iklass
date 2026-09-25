<?php

namespace App\Console\Commands\Admin;

use App\Services\Admin\PermissionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('permissions:synchroniser')]
#[Description('Crée les permissions manquantes et les attribue aux rôles par défaut (config/permissions.php), sans jamais retirer une permission existante — sans risque à lancer à chaque déploiement')]
class SynchroniserPermissions extends Command
{
    public function handle(PermissionService $service): int
    {
        $resultat = $service->synchroniserDeFaconAdditive();

        $this->info("{$resultat['permissions_creees']} permission(s) créée(s).");
        $this->info("{$resultat['attributions_ajoutees']} attribution(s) de rôle ajoutée(s) (rien retiré).");

        return self::SUCCESS;
    }
}
