<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permissions disponibles
    |--------------------------------------------------------------------------
    |
    | Toutes les permissions de l'application, regroupées par domaine.
    | Pour ajouter une permission : l'ajouter dans le groupe concerné
    | ci-dessous, puis rejouer le seeder :
    |
    |   php artisan db:seed --class=RolePermissionSeeder
    |
    */
    'permissions' => [

        'stock' => [
            'stock.tableau_bord.voir',
            'stock.article.gerer',
            'stock.fournisseur.gerer',
            'stock.bon_commande.gerer',
            'stock.achat.gerer',
            'stock.paiement.gerer',
            'stock.sortie.interne',
            'stock.sortie.vente',
            'stock.demande.creer',
            'stock.inventaire.gerer',
        ],

        'utilisateurs' => [
            'utilisateurs.voir',
            'utilisateurs.gerer',
            // Modifier ses propres informations de compte depuis "Mon profil".
            // Les autres rôles consultent leur profil en lecture seule et
            // passent par un administrateur pour toute modification.
            'profil.modifier',
        ],

        'administration' => [
            'roles.voir',
            'roles.gerer',
            'audit.voir',
            'unites.voir',
            'unites.gerer',
            'parametres.voir',
            'parametres.gerer',
        ],

        'flotte' => [
            'flotte.vehicule.voir',
            'flotte.vehicule.gerer',
            'flotte.vehicule.voir_affectes',
            'flotte.vehicule.statut.gerer',
            'flotte.vehicule.remise_circulation',
            'flotte.versement.gerer',
            'flotte.dette.gerer',
            'flotte.dette.regler',
        ],

        // Vue consolidée des 4 caisses (versements, ventes externes, emprunt,
        // paiements fournisseurs) : oversight admin, transverse à Flotte et Stock.
        'caisse' => [
            'caisse.voir',
        ],

        // Échéances d'entretien/administratives par véhicule (vidange, assurance,
        // visite technique...). Transverse à Flotte : géré par gestionnaire_stock
        // (planification, aspect administratif) et chef_mecanicien (réalisation).
        'operations' => [
            'operations.type.gerer',
            'operations.voir',
            'operations.gerer',
            'operations.realiser',
        ],

        // Interventions (pannes/réparations) déclarées et clôturées par le
        // chef mécanicien. La demande de pièces reste hors application pour
        // l'instant (gérée directement avec le gestionnaire de stock).
        'interventions' => [
            'interventions.type.gerer',
            'interventions.voir',
            'interventions.declarer',
        ],

        // Prêts & financements : emprunts contractés par l'entreprise (banque
        // ou personne) et leurs remboursements. Calque du module Fournisseur
        // (préteur = fournisseur, financement = achat, remboursement = paiement).
        'financements' => [
            'financements.type.gerer',
            'financements.voir',
            'financements.preteur.gerer',
            'financements.gerer',
            'financements.rembourser',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions par rôle
    |--------------------------------------------------------------------------
    |
    | Pour chaque rôle, la liste des groupes ci-dessus à assigner en entier,
    | ou un sous-ensemble via ['only' => [...]] / ['except' => [...]].
    |
    | "superadmin" n'a besoin d'aucune permission explicite : Gate::before
    | (AppServiceProvider) lui donne un accès total.
    |
    */
    'roles' => [

        'superadmin' => [],

        'admin' => ['stock', 'utilisateurs', 'administration', 'caisse', 'operations', 'interventions', 'financements', 'flotte' => ['except' => ['flotte.vehicule.voir_affectes']]],

        'gestionnaire_stock' => [
            'stock' => ['except' => ['stock.demande.creer']],
            'administration' => ['only' => ['unites.voir', 'unites.gerer']],
            'operations' => ['only' => ['operations.voir', 'operations.gerer']],
            'financements' => ['except' => ['financements.type.gerer']],
        ],

        // Le chef mécanicien n'a accès qu'aux interventions : il déclare une
        // panne (véhicule, description, statut) puis clôture lui-même avec
        // son rapport depuis ce même module — jamais la Flotte ni les
        // opérations programmées.
        'chef_mecanicien' => [
            'stock' => ['only' => ['stock.demande.creer']],
            'interventions' => ['only' => ['interventions.voir', 'interventions.declarer']],
        ],

        // Le rôle "gestionnaire" (parc) voit uniquement les véhicules qui lui sont
        // attribués, peut ajuster leur statut journalier (fenêtre horaire —
        // cf. App\Support\FenetreStatutJournalier) et enregistrer/consulter
        // uniquement ses propres versements.
        'gestionnaire' => [
            'flotte' => ['only' => ['flotte.vehicule.voir_affectes', 'flotte.vehicule.statut.gerer', 'flotte.versement.gerer', 'flotte.dette.regler']],
        ],

    ],

];
