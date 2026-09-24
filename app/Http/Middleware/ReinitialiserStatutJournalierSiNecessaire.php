<?php

namespace App\Http\Middleware;

use App\Services\Flotte\StatutJournalierService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReinitialiserStatutJournalierSiNecessaire
{
    public function __construct(private readonly StatutJournalierService $statutJournalierService) {}

    /**
     * Filet de sécurité : si le cron n'a pas déclenché la réinitialisation
     * quotidienne des statuts (ex. planificateur système absent en local),
     * ce middleware la déclenche au premier accès du jour à une page Flotte.
     * Sans effet si déjà fait aujourd'hui (cf. StatutJournalierService).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->statutJournalierService->reinitialiserSiNecessaire();

        return $next($request);
    }
}
