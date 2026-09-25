<?php

namespace App\Console\Commands\Flotte;

use App\Services\Flotte\NotificationOperationProgrammeeService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('operations:notifier')]
#[Description('Notifie (cloche) les utilisateurs concernés des opérations programmées en rappel, échéance ou en retard (planifié quotidiennement)')]
class NotifierOperationsProgrammees extends Command
{
    public function handle(NotificationOperationProgrammeeService $service): int
    {
        $nombre = $service->notifier();

        $this->info("{$nombre} notification(s) envoyée(s).");

        return self::SUCCESS;
    }
}
