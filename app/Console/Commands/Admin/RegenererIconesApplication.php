<?php

namespace App\Console\Commands\Admin;

use App\Services\Admin\IdentiteApplicationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('application:regenerer-icones')]
#[Description('Régénère favicons, icônes et logo PDF depuis le logo actuel de l\'application')]
class RegenererIconesApplication extends Command
{
    public function handle(IdentiteApplicationService $identiteApplication): int
    {
        if (! $identiteApplication->regenererIcones()) {
            $this->warn('Aucun logo téléversé : les icônes par défaut restent utilisées.');

            return self::SUCCESS;
        }

        $this->info('Favicons, icônes et logo PDF régénérés.');

        return self::SUCCESS;
    }
}
