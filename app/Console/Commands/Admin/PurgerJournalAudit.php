<?php

namespace App\Console\Commands\Admin;

use App\Services\Admin\AuditService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('audit:purger {--jours=30 : Nombre de jours d\'historique conservés}')]
#[Description("Vide le journal d'audit de ses entrées anciennes (planifié le 1er de chaque mois)")]
class PurgerJournalAudit extends Command
{
    public function handle(AuditService $service): int
    {
        $jours = max(0, (int) $this->option('jours'));

        $nombre = $service->purger($jours);

        $this->info("{$nombre} entrée(s) du journal d'audit supprimée(s).");

        return self::SUCCESS;
    }
}
