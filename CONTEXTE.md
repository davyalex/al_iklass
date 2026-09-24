# AL-IKLASS — Cahier des charges du projet

Ce document est le **résumé fonctionnel de l'application** : ce qu'elle fait, pour qui, et comment. Il complète `CLAUDE.md` (conventions techniques) et sert de référence unique pour comprendre le périmètre métier complet — utile à l'équipe technique comme à un profane découvrant l'outil.

---

## 1. Résumé de l'application

AL-IKLASS est un **ERP simplifié de gestion de flotte** pour une entreprise de transport à Abidjan (Côte d'Ivoire), en **FCFA**. Il couvre, de bout en bout, le cycle d'exploitation d'un parc de véhicules de transport :

- Le suivi quotidien des véhicules (statut, recette, versement des gestionnaires) ;
- L'entretien (interventions mécaniques, opérations programmées comme la vidange ou l'assurance) ;
- La gestion des pièces détachées (achats, ventes, stock, inventaires) ;
- Les financements de l'entreprise (emprunts et leurs remboursements) ;
- Le pilotage transverse (tableau de bord, caisses, administration des accès).

L'application est pensée **mobile-first** pour des utilisateurs non techniques : listes en cartes, peu de changements de page, confirmations explicites, alertes visuelles par badges.

---

## 2. Les acteurs et leur parcours

Cinq rôles, chacun avec un périmètre précis (permissions fines via Spatie, détaillées module par module en §4). Le `superadmin` a un accès total automatique (support technique) et n'est pas détaillé ci-dessous.

### Administrateur / Gérant

Le profil à accès métier complet — contrôle, comptabilité, arbitrages.

- Consulte le **tableau de bord** : vue consolidée de toute l'activité (parc, recette du jour, stock, entretien, caisses, financements) avec graphiques.
- Gère les **véhicules** (créer/modifier/archiver, changer un statut à tout moment, même hors fenêtre horaire) et les **gestionnaires**.
- Pilote tout le **stock** : articles, catégories, unités, fournisseurs, bons de commande, réceptions, paiements, sorties, inventaires.
- Gère les **opérations programmées** (planification et référentiel des types) et consulte les interventions.
- **Annule une dette** de gestionnaire (motif obligatoire, tracé).
- Consulte la **vue consolidée des 4 caisses**.
- Gère les **prêts & financements** : déclare des emprunts, gère les prêteurs et le référentiel des types de prêteur, enregistre des remboursements.
- Administre les **utilisateurs, rôles/permissions, unités, paramètres**, et consulte le **journal d'audit**.

### Gestionnaire (de parc)

Rattaché à un sous-ensemble de véhicules, son parcours est quotidien et répétitif :

1. Chaque matin, ses véhicules repassent automatiquement **« en circulation »**.
2. Entre **08h et 12h** (fenêtre configurable), il ajuste lui-même le statut de ses véhicules si besoin (repos, dépannage...).
3. En fin de journée, il **verse** l'argent collecté dans la caisse des versements.
4. Il consulte et, si besoin, **règle sa propre dette** (montant non versé la veille, basculé automatiquement).

Hors de cette fenêtre horaire, seuls admin/superadmin peuvent encore modifier un statut.

### Gestionnaire de stock

Le second profil « administratif », centré sur les pièces et les finances associées :

- Gère l'intégralité du cycle stock : **articles**, **fournisseurs**, **bons de commande**, **réceptions (achats)**, **paiements fournisseurs**, **sorties** (internes et externes/ventes), **inventaires**.
- Planifie les **opérations programmées** (vidange, assurance, visite technique...).
- Gère les **prêts & financements** au quotidien (déclarer un emprunt, gérer les prêteurs, enregistrer un remboursement) — sauf le référentiel des types de prêteur, réservé à l'admin.

### Chef mécanicien

Le seul rôle « terrain » de l'atelier — un unique menu, **Interventions**, aucun accès à la Flotte :

1. Une voiture arrive au garage : il **déclare une panne** (véhicule, type de panne, description) → le statut du véhicule bascule automatiquement (dépannage/maintenance).
2. Il peut **modifier** l'intervention en cours si besoin (type, description, statut).
3. Une fois réparée, il **clôture l'intervention** avec un **rapport** et une **date** — le véhicule repasse automatiquement **en circulation**. C'est la même mécanique que la remise en circulation côté admin (un seul point de vérité), déclenchée ici depuis Interventions.
4. La **demande de pièces** au gestionnaire de stock reste **hors application** pour l'instant (échange direct).

---

## 3. Glossaire

- **Recette / quota** : montant qu'un véhicule doit rapporter par jour. Fixé **par véhicule** et **modifiable**.
- **Versement** : remise par le gestionnaire de l'argent collecté vers la caisse centrale.
- **Caisse** : registre d'argent. Quatre caisses : *versements*, *ventes externes*, *emprunt*, *dépenses fournisseurs*.
- **Dette (gestionnaire)** : cumul **global** de ce qu'un gestionnaire n'a pas versé (pas par véhicule).
- **Statut journalier** : état d'un véhicule pour la journée — `en_circulation`, `dépannage`, `maintenance`, `à l'arrêt`.
- **Sortie interne / externe** : consommation par un véhicule du parc / **vente** à un véhicule externe.
- **Intervention** : panne déclarée et suivie par le chef mécanicien, jusqu'à clôture avec rapport.
- **Opération programmée** : échéance d'entretien/administrative récurrente (vidange, assurance, visite technique), renouvelée automatiquement à chaque réalisation.
- **Financement** : emprunt contracté par l'entreprise auprès d'une banque ou d'une personne, remboursé en paiements libres.

---

## 4. Fonctionnalités par module

### Module 1 — Véhicules & gestionnaires

Fiche véhicule (code, marque/modèle, immatriculation, chauffeur, recette journalière, gestionnaire affecté, statut). Liste en cartes avec badge de statut. Historique des changements de statut conservé (snapshot). Page « État du parc » : photo du parc à une date passée ou sur un intervalle, avec situation financière agrégée par gestionnaire.

### Module 2 — Stock, achats & ventes

- **Articles** : référence auto-générée, catégorie et unité (référentiels dédiés), prix d'achat/vente, seuil d'alerte. Archivage en soft delete.
- **Fournisseurs** : fiche + **page compte** (KPI achats/réglé/restant + historique chronologique achats/paiements, export PDF) — le modèle repris pour le module Financements.
- **Bons de commande** : référence auto, lignes par article/quantité/prix estimé. Statut recalculé automatiquement après chaque réception (`en_attente` / `partiellement_recu` / `recu` / `annulé`) ; réception partielle possible ligne par ligne.
- **Achats (réceptions)** : avec ou sans bon de commande. Impacte le stock (entrée) et génère la dette fournisseur. Statut de paiement dérivé automatiquement (`comptant` / `partiel` / `crédit`).
- **Paiements fournisseurs** : rattachés à un achat, plafonnés au restant dû (rejet explicite au-delà), génèrent une sortie de caisse (`dépenses_fournisseurs`).
- **Sorties de stock** : *interne* (consommation par un véhicule du parc, valorisée au prix d'achat) ou *externe* (vente à un tiers, prix de vente saisi, génère une entrée de caisse `ventes_externes`). Vérification systématique du stock disponible.
- **Inventaires** : comptage physique en brouillon, validation qui ajuste le stock théorique et journalise chaque écart (mouvement d'ajustement). Jamais de suppression d'un inventaire validé.
- **État du stock** : tableau de bord dédié (valeur du stock, alertes, achats/consommation/ventes de la période).

### Module 3 — Statut journalier

Chaque matin, tous les véhicules non archivés repassent automatiquement **« en circulation »**. Le gestionnaire ajuste pendant la fenêtre 08h–12h (configurable) ; passé ce délai, verrou pour tout le monde sauf admin/superadmin. Seuls les véhicules en circulation génèrent une recette.

### Module 4 — Recette, versement & caisses

Circuit financier à deux niveaux : le véhicule en circulation produit sa recette (quota), le gestionnaire la verse dans la caisse des versements. Le montant à verser du jour se réinitialise chaque jour ; s'il n'est pas soldé, il **bascule automatiquement en dette** (cumul global du gestionnaire), en même temps que la réinitialisation des statuts. Seul l'admin peut annuler une dette (motif obligatoire, tracé). Vue consolidée admin des 4 caisses.

### Module 5 — Opérations programmées

Échéances d'entretien/administratives par véhicule et type (vidange, assurance, visite technique — référentiel extensible avec périodicité par défaut). Badge visuel selon l'échéance (à venir / jour J / dépassé, fenêtre de rappel configurable par échéance). Réaliser une opération clôture l'échéance courante et **planifie automatiquement la suivante** (renouvellement en une action).

### Module 6 — Mouvements flotte & interventions

Le chef mécanicien déclare une panne (type, description) → statut véhicule mis à jour automatiquement ; il modifie si besoin ; il clôture avec rapport + date → remise en circulation automatique. KPI (interventions en cours / du mois / sur période) et filtres. Historique dédié avec export.

### Module 7 — Prêts & financements

Emprunts contractés par l'entreprise (banque ou personne), remboursés en paiements libres jusqu'à solde. Structure calquée sur le module Fournisseur : **Prêteurs** (fiche + page compte avec KPI et historique), **Financements** (déclaration d'un emprunt, référence auto, montant total/remboursé/restant, statut), **Remboursements** (paiement contre un emprunt en cours, plafonné au restant dû). Chaque emprunt reçu/remboursé écrit un mouvement sur la caisse `emprunt`.

### Module 8 — Tableau de bord administrateur

Vue consolidée pour l'admin/superadmin : disponibilité du parc, recette du jour (attendu vs versé), dette globale, valeur du stock et alertes, achats/ventes du mois, entretien en cours/à venir, soldes des 4 caisses, encours de financements — avec graphiques (répartition du parc, versements sur 30 jours, soldes des caisses) et listes courtes (gestionnaires les plus endettés, articles en alerte).

### Module 9 — Intégration réelle de l'API Wave *(à venir)*

« Wave » n'est aujourd'hui qu'un mode de paiement déclaratif (liste déroulante) lors d'un versement ou d'un remboursement. L'intégration réelle avec l'API Wave (paiement/réconciliation automatique) reste à construire.

---

## 5. Règles de gestion transversales

### 5.1 Historisation par snapshot

Toute écriture d'historique conserve une **copie figée** des valeurs au moment de l'opération, **en plus** des clés étrangères. L'historique reste exact même si une fiche est modifiée, réaffectée ou archivée, et même si un identifiant change. Rien qui porte un historique n'est supprimé physiquement (**soft delete**).

### 5.2 Circuit financier (à deux niveaux)

1. **Niveau 1** — un véhicule *en circulation* produit une recette (son quota). Le gestionnaire collecte l'argent.
2. **Niveau 2** — le gestionnaire **verse** le montant attendu dans la **caisse des versements** (Wave ou espèces).

Montant à verser du jour = Σ des quotas des véhicules *en circulation* ce jour-là, réinitialisé chaque jour. Non soldé la veille → bascule en dette (cumul global). Seul l'admin peut annuler une dette, motif obligatoire, tracé.

### 5.3 Moteur de statut journalier

Réinitialisation automatique quotidienne (`en_circulation`), fenêtre d'ajustement 08h–12h pour le gestionnaire (verrou ensuite, sauf admin/superadmin). Le chef mécanicien peut remettre un véhicule en circulation avec rapport, depuis Interventions.

### 5.4 Caisses

Modèle `caisses` (type) + `mouvements_caisse`. Quatre types :

- **versements** — recettes remises par les gestionnaires ;
- **ventes_externes** — pièces vendues à des véhicules externes ;
- **emprunt** — fonds empruntés par l'entreprise (banque ou personne), et leurs remboursements ;
- **depenses_fournisseurs** — paiements aux fournisseurs de pièces.

### 5.5 Notifications & alertes

- **Email + notification interne (cloche)**, déclenchées par le scheduler (échéances d'opérations, seuils de stock, dettes) — *transverse, pas encore construit*.
- Alertes **visuelles par badges** : *à venir / arrivé / dépassé* pour les opérations ; *en alerte* pour les articles sous seuil.
