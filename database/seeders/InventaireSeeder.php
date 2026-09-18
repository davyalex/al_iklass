<?php

namespace Database\Seeders;

use App\Models\Inventaire;
use App\Models\User;
use App\Services\Stock\InventaireService;
use Illuminate\Database\Seeder;

class InventaireSeeder extends Seeder
{
    /**
     * Crée un inventaire de démonstration en brouillon (non validé), pour permettre de
     * tester la saisie des comptages et la validation directement en prod.
     */
    public function run(): void
    {
        if (Inventaire::where('reference', 'INV-DEMO-0001')->exists()) {
            return;
        }

        $utilisateur = User::where('username', 'gestionnaire.demo')->first() ?? User::first();

        if (! $utilisateur) {
            $this->command?->warn('InventaireSeeder ignoré : aucun utilisateur trouvé.');

            return;
        }

        app(InventaireService::class)->creer([
            'reference' => 'INV-DEMO-0001',
            'commentaire' => 'Inventaire de démonstration (seeder)',
            'user_id' => $utilisateur->id,
        ]);
    }
}
