#!/bin/bash
#
# AL-IKLASS — script post-déploiement (cPanel mutualisé)
#
# À exécuter après chaque `git pull` en production (voir CLAUDE.md §3).
# Usage : ./deploy.sh   (depuis la racine du projet, via SSH)
#
# Intégration cPanel Git Version Control : ce script peut aussi être appelé
# automatiquement depuis un fichier .cpanel.yml (section "deployment"), avec
# `/bin/bash $DEPLOYPATH/deploy.sh` comme tâche post-pull.

set -euo pipefail

echo "==> Mode maintenance activé"
php artisan down --retry=60 || true

echo "==> Dépendances PHP (jamais composer update en prod)"
composer install --no-dev --optimize-autoloader --no-interaction

# ------------------------------------------------------------------
# Assets front (Chart.js, Bootstrap, DataTables, Select2, SweetAlert2...).
# public/build/ n'est PAS versionné (voir .gitignore) : il doit être
# régénéré à chaque déploiement qui touche resources/js ou resources/css.
#
# Si npm est absent OU incompatible avec la glibc de l'hébergement (fréquent
# en mutualisé), le bloc ci-dessous l'échoue proprement sans interrompre le
# reste du déploiement : buildez alors en local avec `npm run build` et
# envoyez le contenu de public/build/ par SFTP.
# ------------------------------------------------------------------
echo "==> Build des assets front (npm)"
if command -v npm >/dev/null 2>&1 && npm ci --no-audit --no-fund && npm run build; then
    echo "    Build front OK"
else
    echo "    ATTENTION : build npm indisponible ou en échec sur ce serveur (Node.js"
    echo "    absent ou incompatible avec la glibc de l'hébergement — fréquent en"
    echo "    mutualisé). Assets NON reconstruits ici : buildez en local avec"
    echo "    'npm run build' et envoyez le contenu de public/build/ par SFTP."
fi

echo "==> Nettoyage des caches (avant migration, pour éviter une config obsolète)"
php artisan optimize:clear

echo "==> Migrations de base de données"
php artisan migrate --force

# ------------------------------------------------------------------
# Permissions & rôles (config/permissions.php → base de données).
#
# Additif uniquement : crée les permissions manquantes et les attribue aux
# rôles par défaut, mais ne retire jamais une permission déjà attribuée
# (contrairement à RolePermissionSeeder::syncPermissions(), qui écraserait
# les personnalisations faites depuis Admin > Rôles). Sans risque à chaque
# déploiement, y compris de routine.
# ------------------------------------------------------------------
echo "==> Permissions : ajout des permissions/rôles manquants (additif, aucune personnalisation écrasée)"
php artisan permissions:synchroniser

echo "==> Reconstruction des caches (config, routes, vues, évènements)"
php artisan optimize

echo "==> Lien symbolique storage (idempotent)"
php artisan storage:link || true

echo "==> Mode maintenance désactivé"
php artisan up

echo "==> Déploiement terminé."
