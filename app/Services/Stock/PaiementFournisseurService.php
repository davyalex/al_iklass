<?php

namespace App\Services\Stock;

use App\Exceptions\Stock\TropPercuException;
use App\Models\Achat;
use App\Models\Caisse;
use App\Models\MouvementCaisse;
use App\Models\PaiementFournisseur;
use Illuminate\Support\Facades\DB;

class PaiementFournisseurService
{
    /**
     * @param  array{
     *     achat_id: int,
     *     date_paiement?: ?string,
     *     montant: float,
     *     mode_paiement_id: int,
     *     reference?: ?string,
     *     user_id: int,
     * }  $data
     *
     * @throws TropPercuException
     */
    public function enregistrer(array $data): PaiementFournisseur
    {
        return DB::transaction(function () use ($data) {
            /** @var Achat $achat */
            $achat = Achat::lockForUpdate()->findOrFail($data['achat_id']);

            $montant = (float) $data['montant'];

            if ($montant > (float) $achat->montant_restant) {
                throw new TropPercuException($achat, $montant);
            }

            $paiement = PaiementFournisseur::create([
                'achat_id' => $achat->id,
                'date_paiement' => $data['date_paiement'] ?? now(),
                'montant' => $montant,
                'mode_paiement_id' => $data['mode_paiement_id'],
                'reference' => $data['reference'] ?? null,
                'fournisseur_nom' => $achat->fournisseur?->nom ?? $achat->fournisseur_nom,
                'user_id' => $data['user_id'],
            ]);

            $montantPaye = (float) $achat->montant_paye + $montant;
            $montantRestant = (float) $achat->montant_total - $montantPaye;

            $achat->update([
                'montant_paye' => $montantPaye,
                'montant_restant' => $montantRestant,
                'statut_paiement' => Achat::deriveStatutPaiement($montantPaye, $montantRestant),
            ]);

            $caisse = Caisse::where('type', 'depenses_fournisseurs')->firstOrFail();

            MouvementCaisse::create([
                'caisse_id' => $caisse->id,
                'sens' => 'sortie',
                'montant' => $montant,
                'mode_paiement_id' => $data['mode_paiement_id'],
                'reference' => $data['reference'] ?? null,
                'motif' => "Paiement fournisseur — achat #{$achat->id}",
                'origine_type' => PaiementFournisseur::class,
                'origine_id' => $paiement->id,
                'user_id' => $data['user_id'],
                'date_mouvement' => now(),
            ]);

            activity()
                ->performedOn($achat)
                ->withProperties(['montant' => $montant, 'montant_restant' => $achat->montant_restant])
                ->log("Paiement fournisseur de {$montant} FCFA enregistré pour l'achat #{$achat->id} ({$achat->fournisseur_nom}).");

            return $paiement;
        });
    }
}
