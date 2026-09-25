<?php

namespace App\Services\Flotte;

use App\Models\OperationProgrammee;
use App\Models\User;
use App\Notifications\OperationProgrammeeRappelNotification;
use Illuminate\Notifications\DatabaseNotification;

class NotificationOperationProgrammeeService
{
    /**
     * Envoie une notification aux utilisateurs concernés (permission
     * "operations.voir") pour chaque opération planifiée dont le badge
     * d'alerte (rappel/échéance) est actif — une seule fois par couple
     * (opération, niveau d'alerte) : une opération qui passe de "à venir" à
     * "dépassée" déclenche une nouvelle notification, mais ne répète pas
     * la même alerte chaque jour.
     *
     * @return int nombre de notifications envoyées
     */
    public function notifier(): int
    {
        $destinataires = User::permission('operations.voir')->get();

        if ($destinataires->isEmpty()) {
            return 0;
        }

        $operations = OperationProgrammee::query()->where('statut', 'planifiee')->get();
        $nombreEnvoyees = 0;

        foreach ($operations as $operation) {
            $statutAlerte = $operation->badge();

            if ($statutAlerte === null) {
                continue;
            }

            foreach ($destinataires as $destinataire) {
                if ($this->dejaNotifie($destinataire, $operation, $statutAlerte)) {
                    continue;
                }

                $destinataire->notify(new OperationProgrammeeRappelNotification($operation, $statutAlerte));
                $nombreEnvoyees++;
            }
        }

        return $nombreEnvoyees;
    }

    private function dejaNotifie(User $destinataire, OperationProgrammee $operation, string $statutAlerte): bool
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $destinataire->id)
            ->where('type', OperationProgrammeeRappelNotification::class)
            ->where('data->operation_id', $operation->id)
            ->where('data->statut_alerte', $statutAlerte)
            ->exists();
    }
}
