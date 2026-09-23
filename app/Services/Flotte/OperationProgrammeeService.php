<?php

namespace App\Services\Flotte;

use App\Models\OperationProgrammee;
use App\Models\TypeOperation;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperationProgrammeeService
{
    /**
     * Planifie une échéance pour un véhicule + type d'opération. Rejette s'il
     * existe déjà une ligne 'planifiee' pour ce couple (une seule échéance
     * active par véhicule et par type — imposé ici, pas par contrainte DB).
     *
     * @param  array{vehicule_id: int, type_operation_id: int, date_echeance: string, rappel_jours: int, periodicite_jours?: int|null, commentaire?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function planifier(array $data, User $auteur): OperationProgrammee
    {
        $vehicule = Vehicule::findOrFail($data['vehicule_id']);
        $typeOperation = TypeOperation::findOrFail($data['type_operation_id']);

        $dejaPlanifiee = OperationProgrammee::where('vehicule_id', $vehicule->id)
            ->where('type_operation_id', $typeOperation->id)
            ->where('statut', 'planifiee')
            ->exists();

        if ($dejaPlanifiee) {
            throw ValidationException::withMessages([
                'type_operation_id' => "Une échéance {$typeOperation->libelle} est déjà planifiée pour ce véhicule.",
            ]);
        }

        return OperationProgrammee::create([
            'vehicule_id' => $vehicule->id,
            'vehicule_code' => $vehicule->code,
            'type_operation_id' => $typeOperation->id,
            'type_operation_code' => $typeOperation->code,
            'type_operation_libelle' => $typeOperation->libelle,
            'date_echeance' => $data['date_echeance'],
            'rappel_jours' => $data['rappel_jours'],
            'periodicite_jours' => $data['periodicite_jours'] ?? $typeOperation->periodicite_jours,
            'statut' => 'planifiee',
            'commentaire' => $data['commentaire'] ?? null,
            'user_id' => $auteur->id,
        ]);
    }

    /**
     * Clôture une opération réalisée et enchaîne immédiatement le cycle
     * suivant (nouvelle ligne 'planifiee', échéance = date de réalisation +
     * périodicité) dans la même transaction — pas de renouvellement si aucune
     * périodicité n'est définie (opération ponctuelle).
     *
     * @return array{cloturee: OperationProgrammee, suivante: ?OperationProgrammee}
     *
     * @throws ValidationException
     */
    public function realiser(OperationProgrammee $operation, User $auteur, ?string $commentaire = null): array
    {
        if ($operation->statut !== 'planifiee') {
            throw ValidationException::withMessages([
                'operation' => 'Cette opération a déjà été réalisée.',
            ]);
        }

        return DB::transaction(function () use ($operation, $auteur, $commentaire) {
            $dateRealisation = now()->toDateString();

            $operation->statut = 'realisee';
            $operation->date_realisation = $dateRealisation;
            $operation->realise_par_id = $auteur->id;

            if ($commentaire !== null) {
                $operation->commentaire = $commentaire;
            }

            $operation->save();

            $suivante = null;

            if ($operation->periodicite_jours) {
                $suivante = OperationProgrammee::create([
                    'vehicule_id' => $operation->vehicule_id,
                    'vehicule_code' => $operation->vehicule_code,
                    'type_operation_id' => $operation->type_operation_id,
                    'type_operation_code' => $operation->type_operation_code,
                    'type_operation_libelle' => $operation->type_operation_libelle,
                    'date_echeance' => now()->addDays($operation->periodicite_jours)->toDateString(),
                    'rappel_jours' => $operation->rappel_jours,
                    'periodicite_jours' => $operation->periodicite_jours,
                    'statut' => 'planifiee',
                    'user_id' => $auteur->id,
                ]);
            }

            return ['cloturee' => $operation, 'suivante' => $suivante];
        });
    }
}
