<?php

namespace App\Support;

use App\Models\Parametre;
use Carbon\Carbon;

class FenetreStatutJournalier
{
    /**
     * Vrai si l'heure actuelle (fuseau Africa/Abidjan) se situe dans la
     * fenêtre pendant laquelle un gestionnaire peut ajuster lui-même le
     * statut de ses véhicules (CONTEXTE.md §5). Réglable via la page
     * Paramètres, hors fenêtre seuls admin/superadmin gardent la main.
     */
    public static function estOuverte(?Carbon $maintenant = null): bool
    {
        $maintenant ??= now();

        $debut = Parametre::valeur('flotte.statut_journalier.heure_debut_fenetre', '08:00');
        $fin = Parametre::valeur('flotte.statut_journalier.heure_fin_fenetre', '12:00');

        $heureActuelle = $maintenant->format('H:i');

        return $heureActuelle >= $debut && $heureActuelle <= $fin;
    }

    /**
     * Bornes du jour (début/fin) de la fenêtre, en Carbon — utile pour
     * afficher un compte à rebours côté client.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function bornesDuJour(?Carbon $maintenant = null): array
    {
        $maintenant ??= now();

        $debut = Parametre::valeur('flotte.statut_journalier.heure_debut_fenetre', '08:00');
        $fin = Parametre::valeur('flotte.statut_journalier.heure_fin_fenetre', '12:00');

        return [
            Carbon::parse($maintenant->toDateString().' '.$debut, $maintenant->getTimezone()),
            Carbon::parse($maintenant->toDateString().' '.$fin, $maintenant->getTimezone()),
        ];
    }
}
