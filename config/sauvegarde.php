<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Binaires MySQL pour la sauvegarde/restauration (Admin > Paramètres)
    |--------------------------------------------------------------------------
    |
    | À renseigner via .env (MYSQLDUMP_PATH / MYSQL_PATH) uniquement si les
    | binaires ne sont pas dans le PATH du serveur.
    |
    */

    'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),

    'mysql_path' => env('MYSQL_PATH', 'mysql'),

];
