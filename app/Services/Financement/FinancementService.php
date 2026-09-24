<?php

namespace App\Services\Financement;

use App\Exceptions\Financement\RemboursementExcessifException;
use App\Models\Caisse;
use App\Models\Financement;
use App\Models\MouvementCaisse;
use App\Models\Preteur;
use App\Models\RemboursementFinancement;
use Illuminate\Support\Facades\DB;

class FinancementService
{
    /**
     * @param  array{
     *     preteur_id: int,
     *     reference?: ?string,
     *     date_financement?: ?string,
     *     montant_total: float,
     *     commentaire?: ?string,
     *     user_id: int,
     * }  $data
     */
    public function declarer(array $data): Financement
    {
        return DB::transaction(function () use ($data) {
            $preteur = Preteur::findOrFail($data['preteur_id']);
            $montantTotal = (float) $data['montant_total'];
            $dateFinancement = $data['date_financement'] ?? now();

            $financement = Financement::create([
                'reference' => ($data['reference'] ?? null) ?: $this->genererReference(),
                'preteur_id' => $preteur->id,
                'preteur_nom' => $preteur->nom,
                'date_financement' => $dateFinancement,
                'montant_total' => $montantTotal,
                'montant_rembourse' => 0,
                'montant_restant' => $montantTotal,
                'statut' => 'en_cours',
                'commentaire' => $data['commentaire'] ?? null,
                'user_id' => $data['user_id'],
            ]);

            $caisse = Caisse::where('type', 'emprunt')->firstOrFail();

            MouvementCaisse::create([
                'caisse_id' => $caisse->id,
                'sens' => 'entree',
                'montant' => $montantTotal,
                'reference' => $financement->reference,
                'motif' => "Emprunt {$financement->reference} — {$preteur->nom}",
                'origine_type' => Financement::class,
                'origine_id' => $financement->id,
                'user_id' => $data['user_id'],
                'date_mouvement' => $dateFinancement,
            ]);

            activity()
                ->performedOn($financement)
                ->withProperties(['montant_total' => $montantTotal, 'preteur' => $preteur->nom])
                ->log("Emprunt {$financement->reference} déclaré auprès de « {$preteur->nom} » pour {$montantTotal} FCFA.");

            return $financement;
        });
    }

    /**
     * @param  array{
     *     financement_id: int,
     *     date_remboursement?: ?string,
     *     montant: float,
     *     mode_paiement_id?: ?int,
     *     reference?: ?string,
     *     user_id: int,
     * }  $data
     *
     * @throws RemboursementExcessifException
     */
    public function rembourser(array $data): RemboursementFinancement
    {
        return DB::transaction(function () use ($data) {
            /** @var Financement $financement */
            $financement = Financement::lockForUpdate()->findOrFail($data['financement_id']);

            $montant = (float) $data['montant'];

            if ($montant > (float) $financement->montant_restant) {
                throw new RemboursementExcessifException($financement, $montant);
            }

            $remboursement = RemboursementFinancement::create([
                'financement_id' => $financement->id,
                'preteur_nom' => $financement->preteur_nom,
                'date_remboursement' => $data['date_remboursement'] ?? now(),
                'montant' => $montant,
                'mode_paiement_id' => $data['mode_paiement_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'user_id' => $data['user_id'],
            ]);

            $montantRembourse = (float) $financement->montant_rembourse + $montant;
            $montantRestant = (float) $financement->montant_total - $montantRembourse;

            $financement->update([
                'montant_rembourse' => $montantRembourse,
                'montant_restant' => $montantRestant,
                'statut' => Financement::deriverStatut($montantRestant),
            ]);

            $caisse = Caisse::where('type', 'emprunt')->firstOrFail();

            MouvementCaisse::create([
                'caisse_id' => $caisse->id,
                'sens' => 'sortie',
                'montant' => $montant,
                'mode_paiement_id' => $data['mode_paiement_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'motif' => "Remboursement {$financement->reference} — {$financement->preteur_nom}",
                'origine_type' => RemboursementFinancement::class,
                'origine_id' => $remboursement->id,
                'user_id' => $data['user_id'],
                'date_mouvement' => $data['date_remboursement'] ?? now(),
            ]);

            activity()
                ->performedOn($financement)
                ->withProperties(['montant' => $montant, 'montant_restant' => $montantRestant])
                ->log("Remboursement de {$montant} FCFA enregistré pour l'emprunt {$financement->reference} ({$financement->preteur_nom}).");

            return $remboursement;
        });
    }

    private function genererReference(): string
    {
        $annee = now()->year;
        $sequence = Financement::withTrashed()->whereYear('date_financement', $annee)->count() + 1;

        return sprintf('FIN-%d-%04d', $annee, $sequence);
    }
}
