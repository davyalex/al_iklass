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
        ],

        'administration' => [
            'roles.voir',
            'roles.gerer',
            'audit.voir',
            'unites.voir',
            'unites.gerer',
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

        'admin' => ['stock', 'utilisateurs', 'administration'],

        'gestionnaire_stock' => [
            'stock' => ['except' => ['stock.demande.creer']],
            'administration' => ['only' => ['unites.voir', 'unites.gerer']],
        ],

        'chef_mecanicien' => [
            'stock' => ['only' => ['stock.demande.creer']],
        ],

        // Le rôle "gestionnaire" (parc) n'a aucune permission côté module Stock.
        'gestionnaire' => [],

    ],

];
