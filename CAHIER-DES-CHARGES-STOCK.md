# Cahier des charges — Module Stock, achats & ventes

Module en cours de développement (Sprint 2). Voir `CONTEXTE.md` pour le métier global et `CLAUDE.md` pour les conventions.

---

## 1. Périmètre & objectif

Gérer les **pièces/articles** de l'entreprise : les **entrées** (achats auprès de fournisseurs, avec dettes fournisseurs), les **sorties** (usage interne sur un véhicule du parc, ou **vente** à un véhicule externe), les **seuils d'alerte**, et un **tableau de bord** de KPI (affichage **mensuel par défaut**, filtrable).

Règles structurantes (rappel) :
- **Toute sortie est liée à un véhicule** (interne = code du parc ; externe = infos saisies + prix de vente + motif).
- **Sortie externe = vente** → alimente la **caisse ventes_externes**.
- **Snapshots** sur toutes les lignes d'historique ; **soft delete** partout où il y a un historique.

---

## 2. Acteurs & permissions (Spatie)

| Permission | Gest. stock | Admin/Gérant | Superadmin | Chef mécanicien |
|---|---|---|---|---|
| `stock.dashboard.view` | oui | oui | oui | — |
| `stock.article.manage` | oui | oui | oui | — |
| `stock.fournisseur.manage` | oui | oui | oui | — |
| `stock.achat.manage` | oui | oui | oui | — |
| `stock.paiement.manage` | oui | oui | oui | — |
| `stock.sortie.interne` | oui | oui | oui | — |
| `stock.sortie.vente` | oui | oui | oui | — |
| `stock.demande.create` | — | oui | oui | oui |

> La **demande de pièces** émise par le chef mécanicien est traitée au Sprint 6 ; on pose ici l'interface (la sortie interne peut référencer une `intervention_id` nullable).

---

## 3. Modèle de données

### 3.1 Référentiels (tables, pas d'ENUM)

- **`categories_article`** : `id`, `code`, `libelle`, `actif`
- **`modes_paiement`** *(partagé)* : `id`, `code` (`especes`, `wave`, `virement`, `cheque`), `libelle`, `actif`

### 3.2 `articles`

| Champ | Type | Note |
|---|---|---|
| id | bigint PK | |
| reference | string, unique | référence fournisseur/interne |
| nom | string | |
| description | text, nullable | |
| categorie_id | FK → categories_article, nullable | |
| unite | string, nullable | pièce, litre… |
| quantite_stock | int, default 0 | état courant |
| prix_achat | decimal(12,2) | dernier prix d'achat unitaire |
| prix_vente | decimal(12,2), nullable | prix de vente conseillé |
| seuil_alerte | int, default 0 | |
| actif | boolean, default true | |
| timestamps, softDeletes | | |

### 3.3 `fournisseurs`

`id`, `nom`, `telephone`, `email` (nullable), `adresse` (nullable), `actif`, timestamps, softDeletes.

### 3.4 `achats` (en-tête d'entrée)

| Champ | Type | Note |
|---|---|---|
| id | bigint PK | |
| reference | string, nullable | n° bon d'achat |
| fournisseur_id | FK → fournisseurs | |
| fournisseur_nom | string | **snapshot** |
| date_achat | date | |
| montant_total | decimal(12,2) | Σ des lignes |
| montant_paye | decimal(12,2), default 0 | |
| montant_restant | decimal(12,2) | dette fournisseur = total − payé |
| statut_paiement | string | `comptant` / `partiel` / `credit` |
| commentaire | text, nullable | |
| user_id | FK → users | auteur |
| timestamps | | |

### 3.5 `achat_lignes`

`id`, `achat_id` (FK), `article_id` (FK), **`article_reference`** (snapshot), **`article_nom`** (snapshot), `quantite` (int), `prix_unitaire` (decimal), `montant` (decimal).

### 3.6 `paiements_fournisseur`

`id`, `achat_id` (FK), `date_paiement` (date), `montant` (decimal), `mode_paiement_id` (FK), `reference` (nullable), **`fournisseur_nom`** (snapshot), `user_id`, timestamps.

### 3.7 `mouvements_stock` (registre central — le ledger)

| Champ | Type | Note |
|---|---|---|
| id | bigint PK | |
| article_id | FK → articles | |
| article_reference / article_nom | string | **snapshot** |
| type | string | `entree` / `sortie` |
| nature | string, nullable | pour une sortie : `interne` / `externe` |
| quantite | int | |
| prix_unitaire | decimal(12,2) | prix d'achat (valorisation) |
| prix_vente | decimal(12,2), nullable | rempli si sortie externe (vente) |
| motif | string | |
| vehicule_id | FK → vehicules, nullable | sortie interne |
| vehicule_code | string, nullable | **snapshot** (interne) |
| vehicule_externe | string, nullable | plaque/description (externe) |
| acheteur | string, nullable | vente externe |
| achat_id | FK → achats, nullable | entrée liée à un achat |
| intervention_id | FK, nullable | interface Sprint 6 |
| caisse_mouvement_id | FK → mouvements_caisse, nullable | encaissement d'une vente |
| user_id | FK → users | |
| date_mouvement | datetime | |
| timestamps | | |

### 3.8 Caisses *(partagées — posées ici pour la vente externe)*

- **`caisses`** : `id`, `type` (`versements` / `ventes_externes` / `emprunt`), `libelle`, `actif`
- **`mouvements_caisse`** : `id`, `caisse_id` (FK), `sens` (`entree`/`sortie`), `montant` (decimal), `mode_paiement_id` (nullable), `reference` (nullable), `motif`, `origine_type` + `origine_id` (morph, nullable), `user_id`, `date_mouvement`, timestamps.

---

## 4. Règles de gestion

### Entrées / achats
- Un achat crée les `achat_lignes`, **incrémente** `quantite_stock` de chaque article et écrit un `mouvements_stock` de type `entree` par ligne.
- `montant_restant = montant_total − montant_paye` ; `statut_paiement` déduit (`comptant` si restant = 0, `credit` si payé = 0, sinon `partiel`).
- Le **dernier `prix_achat`** de l'article est mis à jour au prix de la ligne.
- Un **paiement fournisseur** ultérieur augmente `montant_paye`, recalcule le restant et le statut.

### Sorties internes
- Rattachées à un **véhicule du parc** (`vehicule_id`), avec motif. **Décrémentent** `quantite_stock`. Valorisées au `prix_achat`. Pas de mouvement de caisse.
- Interdire une sortie si `quantite > quantite_stock` (voir alerte seuil).

### Sorties externes (ventes)
- Saisir : article, quantité, **prix de vente**, infos du **véhicule externe**, acheteur, motif.
- **Décrémentent** le stock, écrivent un `mouvements_stock` (`sortie`/`externe`) **et** un `mouvements_caisse` (`entree`) sur la caisse **ventes_externes**.

### Seuils & alertes
- Un article est **en alerte** quand `quantite_stock ≤ seuil_alerte`.
- Si une demande/sortie dépasse le stock disponible : **message** + proposition de **déclencher un achat**.

### Transversal
- Chaque opération ci-dessus s'exécute dans **`DB::transaction()`** et écrit ses **snapshots**.
- Observers pour l'invalidation du cache des KPI.

---

## 5. Écrans & UX (Bootstrap 5.3, mobile-first)

- **Tableau de bord Stock** : KPI en **cartes** (section 6), filtre de période (mois par défaut) + filtres article/catégorie.
- **Liste des articles** : cartes avec nom + référence + **badge d'alerte** si sous seuil ; recherche (Select2).
- **Achats / Fournisseurs / Paiements** : formulaires en **modales** (jQuery/AJAX), confirmations **SweetAlert2**.
- **Sortie** : modale unique avec bascule **interne / vente externe** (champs conditionnels : véhicule du parc *ou* infos externes + prix de vente).
- **Historiques** (mouvements de stock, achats, paiements) : **Yajra DataTables server-side**, filtrables.

---

## 6. KPIs (dashboard — mois par défaut, filtrable)

| Indicateur | Calcul |
|---|---|
| État du stock | nombre d'articles actifs, quantité totale |
| Montant du stock restant | Σ `quantite_stock × prix_achat` (valorisation) |
| Montant du stock utilisé (interne) | Σ sorties `interne` du mois, valorisées au `prix_achat` |
| Montant vendu (externe) | Σ sorties `externe` du mois au `prix_vente` |
| Montant des achats du mois | Σ `montant_total` des achats du mois |
| Dettes fournisseurs | Σ `montant_restant` des achats |
| Articles en alerte | nombre d'articles où `quantite_stock ≤ seuil_alerte` |

---

## 7. Critères d'acceptation

- Un achat incrémente le stock, crée les mouvements `entree` et calcule correctement la dette fournisseur.
- Un paiement partiel met à jour restant et statut ; l'historique par fournisseur est consultable.
- Une sortie interne décrémente le stock et reste rattachée à un véhicule du parc.
- Une vente externe décrémente le stock, enregistre le prix de vente + infos véhicule externe, et alimente la caisse ventes_externes.
- Une sortie supérieure au stock disponible est refusée avec message + proposition d'achat.
- Les KPI du dashboard sont exacts pour le mois en cours et se recalculent avec les filtres.
- Chaque ligne d'historique conserve ses snapshots même après modification/archivage de l'article, du fournisseur ou du véhicule.

---

## 8. Plan d'implémentation (ordre conseillé)

1. **Migrations** : référentiels (`categories_article`, `modes_paiement`), `caisses` + `mouvements_caisse`, `fournisseurs`, `articles`, `achats` + `achat_lignes`, `paiements_fournisseur`, `mouvements_stock`.
2. **Seeders** : catégories de base, modes de paiement, les 3 caisses, permissions Spatie du module + attribution aux rôles.
3. **Modèles & relations** (+ casts `decimal`, `SoftDeletes`, scopes utiles) et **Observers** de cache.
4. **Policies** et vérification des permissions.
5. **Services** dans des transactions : `AchatService`, `PaiementFournisseurService`, `SortieStockService` (interne/vente), avec écriture des snapshots et des mouvements de caisse.
6. **Contrôleurs + Form Requests** (achats, fournisseurs, paiements, sorties, articles).
7. **Vues Blade** : dashboard KPI (cartes), liste articles (cartes + badge), modales de saisie, historiques **DataTables server-side**.
8. **Tests** : couvrir les critères d'acceptation (achat, paiement partiel, sortie interne, vente externe, refus sur stock insuffisant, exactitude des KPI, persistance des snapshots).

---

## 9. Points d'interface avec les autres modules

- **Véhicules (Sprint 1)** : les sorties internes référencent `vehicules` (déjà en place).
- **Recette/caisses (Sprint 4)** : la caisse `versements` et le modèle `mouvements_caisse` posés ici seront réutilisés.
- **Mouvements flotte (Sprint 6)** : la **demande de pièces** du chef mécanicien alimentera les sorties internes via `intervention_id`.
- **Opérations programmées (Sprint 5)** : une vidange peut consommer des pièces (sortie interne).
