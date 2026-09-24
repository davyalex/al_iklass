<?php

namespace App\Console\Commands\Flotte;

use App\Services\Flotte\StatutJournalierService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('flotte:reinitialiser-statuts-journaliers')]
#[Description('Remet chaque matin tous les véhicules non archivés en statut "en circulation" (CONTEXTE.md §5)')]
class ReinitialiserStatutsJournaliers extends Command
{
    public function handle(StatutJournalierService $service): int
    {
        $nombre = $service->reinitialiserSiNecessaire();

        $this->info("{$nombre} véhicule(s) remis en circulation.");

        return self::SUCCESS;
    }
}
