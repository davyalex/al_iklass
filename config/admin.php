<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identifiants par défaut du compte superadmin
    |--------------------------------------------------------------------------
    |
    | Utilisés par AdminUserSeeder pour créer (et resynchroniser à chaque
    | reseed) le compte superadmin. Modifiables librement dans le .env :
    | le prochain `php artisan db:seed --class=AdminUserSeeder` applique
    | les nouvelles valeurs, y compris le mot de passe.
    |
    | Si SUPERADMIN_PASS est vide, un mot de passe aléatoire (5 chiffres)
    | est généré et affiché une seule fois lors de la création du compte.
    |
    */

    'superadmin' => [
        'name' => env('SUPERADMIN_NAME', 'Alex Kouamelan'),
        'username' => env('SUPERADMIN_USERNAME', 'superadmin'),
        'email' => env('SUPERADMIN_EMAIL', 'alexkouamelan96@gmail.com'),
        'telephone' => env('SUPERADMIN_TELEPHONE', '0000000000'),
        'password' => env('SUPERADMIN_PASS'),
    ],

];
