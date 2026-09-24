<?php

namespace App\Services\Flotte;

use App\Models\Intervention;
use App\Models\StatutVehicule;
use App\Models\TypePanne;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InterventionService
{
    /**
     * Déclare une panne : crée l'intervention et change le statut du véhicule
     * dans la même transaction. Rejette s'il existe déjà une intervention
     * 'en_cours' pour ce véhicule (une seule à la fois).
     *
     * @param  array{vehicule_id: int, type_panne_id?: int|null, description: string, statut_id: int}  $data
     *
     * @throws ValidationException
     */
    public function declarer(array $data, User $auteur): Intervention
    {
        $vehicule = Vehicule::findOrFail($data['vehicule_id']);

        $dejaEnCours = Intervention::where('vehicule_id', $vehicule->id)
            ->where('statut', 'en_cours')
            ->exists();

        if ($dejaEnCours) {
            throw ValidationException::withMessages([
                'vehicule_id' => 'Une intervention est déjà en cours pour ce véhicule.',
            ]);
        }

        $typePanne = ! empty($data['type_panne_id']) ? TypePanne::find($data['type_panne_id']) : null;

        return DB::transaction(function () use ($vehicule, $typePanne, $data, $auteur) {
            $intervention = Intervention::create([
                'vehicule_id' => $vehicule->id,
                'vehicule_code' => $vehicule->code,
                'type_panne_id' => $typePanne?->id,
                'type_panne_libelle' => $typePanne?->libelle,
                'description' => $data['description'],
                'statut' => 'en_cours',
                'date_debut' => now(),
                'declaree_par_id' => $auteur->id,
            ]);

            $vehicule->update(['statut_id' => $data['statut_id']]);

            return $intervention;
        });
    }

    /**
     * Remet le véhicule en circulation avec son rapport et clôture, le cas
     * échéant, son intervention 'en_cours' avec ce même rapport — seul point
     * d'entrée pour "remettre un véhicule en état" (utilisé aussi bien par
     * l'admin depuis la Flotte que par le chef mécanicien depuis les
     * Interventions). Retourne null (sans erreur) s'il n'y a pas
     * d'intervention ouverte : une simple correction de statut reste
     * possible sans panne déclarée.
     */
    public function cloturer(Vehicule $vehicule, string $rapport, User $auteur, ?string $dateFin = null): ?Intervention
    {
        return DB::transaction(function () use ($vehicule, $rapport, $auteur, $dateFin) {
            $statutEnCirculationId = StatutVehicule::where('code', 'en_circulation')->value('id');

            $vehicule->commentaireHistorique = $rapport;
            $vehicule->update(['statut_id' => $statutEnCirculationId]);

            $intervention = Intervention::where('vehicule_id', $vehicule->id)
                ->where('statut', 'en_cours')
                ->first();

            if (! $intervention) {
                return null;
            }

            $intervention->update([
                'statut' => 'terminee',
                'date_fin' => $dateFin ? Carbon::parse($dateFin) : now(),
                'rapport' => $rapport,
                'cloturee_par_id' => $auteur->id,
            ]);

            return $intervention;
        });
    }
}
