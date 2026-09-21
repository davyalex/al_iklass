<?php

namespace App\Console\Commands\Flotte;

use App\Models\StatutVehicule;
use App\Models\Vehicule;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('flotte:reinitialiser-statuts-journaliers')]
#[Description('Remet chaque matin tous les véhicules non archivés en statut "en circulation" (CONTEXTE.md §5)')]
class ReinitialiserStatutsJournaliers extends Command
{
    public function handle(): int
    {
        $statutEnCirculation = StatutVehicule::where('code', 'en_circulation')->first();

        if (! $statutEnCirculation) {
            $this->error('Statut "en_circulation" introuvable — vérifiez le seeder StatutVehiculeSeeder.');

            return self::FAILURE;
        }

        $vehicules = Vehicule::where('statut_id', '!=', $statutEnCirculation->id)
            ->orWhereNull('statut_id')
            ->get();

        foreach ($vehicules as $vehicule) {
            $vehicule->update(['statut_id' => $statutEnCirculation->id]);
        }

        $this->info("{$vehicules->count()} véhicule(s) remis en circulation.");

        return self::SUCCESS;
    }
}
