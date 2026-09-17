<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>



# CLAUDE.md — AL-IKLASS

Contexte projet pour Claude Code. À lire à chaque session.
Les spécifications détaillées sont dans `docs/`.

---

## 1. Le projet

AL-IKLASS est une application web de **gestion de flotte** pour une entreprise de transport à Abidjan (devise **FCFA**). Périmètre : parc de véhicules & chauffeurs, recettes journalières & versements, **stock de pièces (achats + ventes)**, opérations programmées (vidange/assurance…), prêts/financements, et pilotage administrateur.

Esprit **ERP simplifié type Odoo**, pour utilisateurs non techniques : listes en **cartes**, tableaux de bord de contrôle, **peu de changements de page**. Application utilisée **majoritairement sur mobile** → responsive **mobile-first** impératif.

---

## 2. Stack technique

- **Laravel** (dernière version stable), PHP 8.2+
- **MySQL**
- **Blade + Bootstrap 5.3 + jQuery**
- **Yajra DataTables** — *toujours en server-side* — pour les vues tabulaires et historiques
- **SweetAlert2** (confirmations/alertes), **Select2** (listes déroulantes riches)
- **Spatie laravel-permission** (rôles & permissions), **Spatie MediaLibrary** (fichiers/documents)
- Interactivité : **AJAX + modales (jQuery)**, pas de SPA. *(Livewire non retenu par défaut.)*

### Déploiement & exploitation
- Hébergement **cPanel mutualisé**, déploiement via **Git + `composer install`** (jamais `composer update` en prod)
- **Scheduler Laravel via cron** ; queues avec le pattern `--stop-when-empty`
- **Cache** : invalidation centralisée **au niveau modèle via Observers** (pas d'appels cache dans les contrôleurs)

---

## 3. Commandes utiles

```bash
# Local
php artisan migrate --seed
php artisan test
php artisan queue:work --stop-when-empty
npm run dev            # ou npm run build

# Production (cPanel)
git pull && composer install --no-dev -o && php artisan migrate --force && php artisan optimize
```

---

## 4. Conventions de code

- **Validation** : Form Requests dédiées (jamais de validation dans le contrôleur).
- **Autorisation** : Policies + permissions Spatie. Vérifier la **permission**, pas seulement le rôle.
- **Logique métier multi-écritures** : dans des **classes Service**, enveloppée dans `DB::transaction()`.
- **Cache** : invalidation via **Observers** sur les modèles concernés.
- **Argent** : colonnes `decimal(12,2)`, jamais de `float`. Devise **FCFA**. Timezone `Africa/Abidjan`.
- **Nommage** : tables au pluriel français (`vehicules`, `articles`, `mouvements_stock`) ; modèles au singulier.
- **Migrations** : une par table, clés étrangères explicites, `softDeletes()` sur tout ce qui porte un historique.
- **Référentiels** : toute liste de valeurs (statuts, types, modes de paiement…) est une **table**, jamais un ENUM figé dans le code.

---

## 5. Règles d'or (invariants — à ne jamais violer)

Détail métier dans `docs/CONTEXTE.md`.

1. **Snapshots** — chaque ligne d'historique (mouvement de stock, versement, paiement, remboursement, annulation de dette) stocke une **copie figée** des valeurs clés (code, nom, montants) **en plus** des clés étrangères. L'historique doit survivre à une modification, une réaffectation ou un archivage.
2. **Jamais de suppression physique** d'un enregistrement porteur d'historique → **soft delete / archivage**.
3. **Toute sortie de stock est liée à un véhicule** : interne = véhicule du parc ; externe = infos saisies + prix de vente.
4. **Toute opération financière** (achat, paiement fournisseur, vente, versement) passe par une **transaction** et écrit un **mouvement de caisse** quand de l'argent entre ou sort.
5. **Audit** — les opérations sensibles sont journalisées (qui, quoi, quand).

---

## 6. Rôles

`superadmin/développeur` (accès total, support) · `admin/gérant` (accès métier total) · `gestionnaire` (parc) · `gestionnaire de stock` · `chef mécanicien`. Permissions fines via Spatie.

## 7. Caisses

Trois caisses via `caisses` (type) + `mouvements_caisse` : **versements**, **ventes_externes**, **emprunt**.

---

## 8. Modules & état d'avancement

Ordre de développement :

1. Véhicules & gestionnaires
2. **Stock, achats & ventes ← EN COURS**
3. Statut journalier
4. Recette, versement & caisses
5. Opérations programmées
6. Mouvements flotte & interventions
7. Prêts & financements
8. Tableau de bord administrateur
9. Intégration réelle de l'API Wave

> **Focus actuel : Module Stock** — spécification complète dans `docs/CAHIER-DES-CHARGES-STOCK.md`.

---

## 9. UI / UX

- Bootstrap 5.3, **mobile-first**.
- Listes principales (véhicules, opérations, articles) en **cartes** : icône + code + **badge** de statut/alerte.
- Historiques et données tabulaires en **DataTables server-side**.
- Confirmations et retours via **SweetAlert2** ; listes déroulantes via **Select2**.
- **Cloche de notifications** avec compteur de non-lus.
- Éviter les rechargements complets : **AJAX + modales**.
