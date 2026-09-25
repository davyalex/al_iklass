<?php

namespace App\Notifications;

use App\Models\OperationProgrammee;
use Illuminate\Notifications\Notification;

class OperationProgrammeeRappelNotification extends Notification
{
    /**
     * @param  'depasse'|'jour_j'|'a_venir'  $statutAlerte  Badge de l'opération au moment de l'envoi (App\Models\OperationProgrammee::badge())
     */
    public function __construct(
        private readonly OperationProgrammee $operation,
        private readonly string $statutAlerte,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'operation_id' => $this->operation->id,
            'vehicule_id' => $this->operation->vehicule_id,
            'vehicule_code' => $this->operation->vehicule_code,
            'type_operation_libelle' => $this->operation->type_operation_libelle,
            'date_echeance' => $this->operation->date_echeance->format('Y-m-d'),
            'statut_alerte' => $this->statutAlerte,
            'message' => $this->message(),
        ];
    }

    private function message(): string
    {
        $vehicule = $this->operation->vehicule_code;
        $type = $this->operation->type_operation_libelle;
        $date = $this->operation->date_echeance->format('d/m/Y');

        return match ($this->statutAlerte) {
            'depasse' => "{$type} en retard pour {$vehicule} (échéance dépassée le {$date}).",
            'jour_j' => "{$type} à faire aujourd'hui pour {$vehicule}.",
            default => "{$type} à prévoir pour {$vehicule} avant le {$date}.",
        };
    }
}
