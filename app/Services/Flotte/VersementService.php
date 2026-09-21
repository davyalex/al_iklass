<?php

namespace App\Services\Flotte;

use App\Models\Caisse;
use App\Models\MouvementCaisse;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Versement;
use Illuminate\Support\Facades\DB;

class VersementService
{
    /**
     * @param  array{
     *     gestionnaire_id: int,
     *     vehicule_id?: ?int,
     *     montant: float,
     *     mode_paiement_id: int,
     *     date_versement?: ?string,
     *     reference?: ?string,
     *     commentaire?: ?string,
     *     user_id: int,
     * }  $data
     */
    public function enregistrer(array $data): Versement
    {
        return DB::transaction(function () use ($data) {
            $gestionnaire = User::findOrFail($data['gestionnaire_id']);
            $vehicule = ! empty($data['vehicule_id']) ? Vehicule::find($data['vehicule_id']) : null;

            $versement = Versement::create([
                'gestionnaire_id' => $gestionnaire->id,
                'gestionnaire_nom' => $gestionnaire->name,
                'vehicule_id' => $vehicule?->id,
                'vehicule_code' => $vehicule?->code,
                'montant' => $data['montant'],
                'mode_paiement_id' => $data['mode_paiement_id'],
                'date_versement' => $data['date_versement'] ?? now(),
                'reference' => $data['reference'] ?? null,
                'user_id' => $data['user_id'],
                'commentaire' => $data['commentaire'] ?? null,
            ]);

            $caisse = Caisse::where('type', 'versements')->firstOrFail();
            $motif = "Versement gestionnaire — {$gestionnaire->name}".($vehicule ? " ({$vehicule->code})" : '');

            MouvementCaisse::create([
                'caisse_id' => $caisse->id,
                'sens' => 'entree',
                'montant' => $data['montant'],
                'mode_paiement_id' => $data['mode_paiement_id'],
                'reference' => $data['reference'] ?? null,
                'motif' => $motif,
                'origine_type' => Versement::class,
                'origine_id' => $versement->id,
                'user_id' => $data['user_id'],
                'date_mouvement' => now(),
            ]);

            return $versement;
        });
    }
}
