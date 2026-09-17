# AL-IKLASS — Contexte métier

Ce document décrit le « pourquoi » du projet : le domaine, les acteurs et les règles de gestion transversales. Il complète `CLAUDE.md` (conventions techniques) et les cahiers des charges par module.

---

## 1. L'entreprise

AL-IKLASS gère l'exploitation d'une **flotte de véhicules de transport** à Abidjan (Côte d'Ivoire). L'argent est en **FCFA**. Chaque jour, des véhicules circulent, produisent une recette, et des gestionnaires reversent cet argent à l'entreprise.

En parallèle : entretien des véhicules (atelier + pièces), **achats de pièces** auprès de fournisseurs, **ventes de pièces**, échéances administratives (assurance, vidange, visite technique…) et **financements** (prêts).

---

## 2. Les acteurs (rôles)

| Rôle | Périmètre |
|---|---|
| Superadmin / Développeur | Accès total, support technique |
| Administrateur / Gérant | Accès métier total : contrôle, comptabilité, annulation de dette, stock, enregistrements |
| Gestionnaire (de parc) | Véhicules attribués, statuts journaliers, recettes, versements |
| Gestionnaire de stock | Articles, achats, fournisseurs, sorties & ventes |
| Chef mécanicien | Interventions, demandes de pièces, remise en circulation avec rapport |

---

## 3. Glossaire

- **Recette / quota** : montant qu'un véhicule doit rapporter par jour. Fixé **par véhicule** et **modifiable**.
- **Versement** : remise par le gestionnaire de l'argent collecté vers la caisse centrale.
- **Caisse** : registre d'argent. Trois caisses : *versements*, *ventes externes*, *emprunt/prêt*.
- **Dette (gestionnaire)** : cumul **global** de ce qu'un gestionnaire n'a pas versé (pas par véhicule).
- **Statut journalier** : état d'un véhicule pour la journée — `repos`, `en_circulation`, `réparation`, `dépannage`, `autre`.
- **Sortie interne / externe** : consommation par un véhicule du parc / **vente** à un véhicule externe.

---

## 4. Circuit financier (à deux niveaux)

1. **Niveau 1** — un véhicule *en circulation* produit une recette (son quota). Le gestionnaire collecte l'argent.
2. **Niveau 2** — le gestionnaire **verse** le montant attendu dans la **caisse des versements** (Wave ou espèces).

- **Montant à verser du jour** = Σ des quotas des véhicules *en circulation* ce jour-là. **Réinitialisé chaque jour.**
- Si l'attendu de la veille n'est pas soldé au moment de la mise à jour du jour, il **bascule en dette** (cumul global du gestionnaire).
- Seul l'**administrateur** peut **annuler une dette**, avec **motif obligatoire** et **trace** (snapshot + audit).

---

## 5. Moteur de statut journalier

- Chaque matin, **tous les véhicules actifs** repassent automatiquement **« en circulation »** (badge vert).
- Le gestionnaire ajuste les statuts pendant une **fenêtre 08h–12h** (configurable, **globale** dans les paramètres).
- Après la fenêtre : **verrou** pour le gestionnaire ; seuls **admin/superadmin** peuvent encore modifier.
- Le **chef mécanicien** peut remettre un véhicule en circulation **avec rapport**.
- **Seuls les véhicules en circulation** génèrent une recette.

---

## 6. Historisation par snapshot

Toute écriture d'historique conserve une **copie figée** des valeurs au moment de l'opération, **en plus** des clés étrangères. L'historique reste exact même si une fiche est modifiée, réaffectée ou archivée, et même si un identifiant change. Rien qui porte un historique n'est supprimé physiquement (**soft delete**).

---

## 7. Caisses

Modèle `caisses` (type) + `mouvements_caisse`. Types :

- **versements** — recettes remises par les gestionnaires ;
- **ventes_externes** — pièces vendues à des véhicules externes ;
- **emprunt** — fonds empruntés par l'entreprise (banque ou personne).

---

## 8. Notifications & alertes

- **Email + notification interne (cloche)**, déclenchées par le **scheduler** (échéances d'opérations, seuils de stock, dettes).
- Alertes **visuelles par badges** : *à venir / arrivé / dépassé* pour les opérations ; *en alerte* pour les articles sous seuil.
