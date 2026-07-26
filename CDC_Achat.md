# Cahier des Charges Fonctionnel — Module Achat

**Maître d'ouvrage** : Direction des Systèmes d'Information — CHU-YO
**Application** : Système de Gestion du Parc Informatique
**Module concerné** : Achat & Approvisionnement
**Version** : 1.0
**Date** : 26 juillet 2026
**Nature du document** : Expression de besoin et exigences contractuelles

---

## Préambule — Portée et usage de ce document

Ce cahier des charges exprime **le besoin fonctionnel du module Achat**, indépendamment de son implémentation actuelle. Il constitue le référentiel d'exigences opposable pour :

- le pilotage des développements et évolutions,
- la recette fonctionnelle et la validation des livraisons,
- l'arbitrage des demandes de modification.

Chaque exigence porte un **niveau de couverture par l'existant**, établi à partir de l'analyse du code livré (cf. `SFD_Achat.md`) :

| Symbole | Signification |
|:---:|---|
| **✔** | Exigence couverte et conforme |
| **◐** | Exigence partiellement couverte — écart à traiter |
| **✘** | Exigence non couverte — à réaliser |

Les priorités suivent la méthode **MoSCoW** : **M** = indispensable, **S** = important, **C** = souhaitable, **W** = hors périmètre de la version courante.

Les éléments de volumétrie, de charge et de planning figurant aux chapitres 11 et 12 sont des **hypothèses de travail à confirmer par la maîtrise d'ouvrage**.

---

## Table des matières

1. [Contexte et enjeux](#1-contexte-et-enjeux)
2. [Objectifs du module](#2-objectifs-du-module)
3. [Périmètre](#3-périmètre)
4. [Acteurs et organisation](#4-acteurs-et-organisation)
5. [Processus métier cibles](#5-processus-métier-cibles)
6. [Exigences fonctionnelles](#6-exigences-fonctionnelles)
7. [Exigences non fonctionnelles](#7-exigences-non-fonctionnelles)
8. [Règles de gestion contractuelles](#8-règles-de-gestion-contractuelles)
9. [Contraintes](#9-contraintes)
10. [Livrables attendus](#10-livrables-attendus)
11. [Recette et critères d'acceptation](#11-recette-et-critères-dacceptation)
12. [Lotissement et trajectoire](#12-lotissement-et-trajectoire)
13. [Annexes](#13-annexes)

---

## 1. Contexte et enjeux

### 1.1 Contexte

Le CHU-YO exploite un parc informatique dont la gestion est assurée par l'application interne « Parc Info », organisée en modules fonctionnels (ParcInfo, Achat, Stock, GRH, Organisation).

Le module **Achat** occupe une position charnière dans cette architecture : il constitue **le point d'entrée unique du matériel dans le système d'information**. Tout équipement, licence ou consommable présent dans le parc doit avoir été acquis, réceptionné et intégré par ce module.

### 1.2 Problématique adressée

Avant l'existence du module, la chaîne d'approvisionnement souffrait de ruptures documentaires et de saisies redondantes :

| Problème constaté | Conséquence opérationnelle |
|---|---|
| Commandes suivies hors système (tableurs, courriers) | Absence de vision consolidée de l'engagement financier |
| Absence de lien entre la commande et le matériel livré | Impossible de retrouver le bon de commande d'origine d'un équipement en panne |
| Ressaisie manuelle des équipements à la réception | Erreurs de numéro de série, codes inventaire en doublon, délais d'immobilisation |
| Suivi des livraisons partielles non outillé | Reliquats oubliés, relances fournisseurs tardives |
| Stock des consommables tenu séparément | Écarts entre stock théorique et stock physique |
| Absence d'historique d'acquisition | Valeur d'achat et date d'acquisition perdues, amortissement non traçable |

### 1.3 Enjeux

| Enjeu | Description |
|---|---|
| **Traçabilité patrimoniale** | Pouvoir remonter de tout équipement du parc jusqu'à son bon de commande, son fournisseur, son prix d'achat et sa date d'acquisition. |
| **Fiabilité de l'inventaire** | Garantir l'unicité des numéros de série et des codes inventaire, et l'exhaustivité de la saisie à la réception. |
| **Maîtrise de la dépense** | Disposer d'états consolidés de la dépense d'équipement par période, par fournisseur et par nature. |
| **Efficience opérationnelle** | Supprimer la double saisie entre la réception et l'enregistrement au parc. |
| **Conformité administrative** | Produire les documents et justificatifs attendus lors des contrôles internes et des audits. |

---

## 2. Objectifs du module

### 2.1 Objectif général

> Doter le CHU-YO d'un outil couvrant l'intégralité de la chaîne d'approvisionnement informatique, du référencement d'un article à son intégration automatique dans le parc, en garantissant la traçabilité complète et la fiabilité de l'inventaire.

### 2.2 Objectifs opérationnels

| Réf. | Objectif | Indicateur de réussite |
|---|---|---|
| **OBJ-01** | Centraliser le référencement des articles achetables | 100 % des commandes émises depuis le catalogue |
| **OBJ-02** | Dématérialiser le cycle de vie des bons de commande | Aucun bon de commande émis hors application |
| **OBJ-03** | Automatiser la création des fiches d'équipement à la réception | Zéro ressaisie manuelle d'équipement acquis |
| **OBJ-04** | Assurer la traçabilité commande → livraison → équipement | Tout équipement du parc rattachable à un BC |
| **OBJ-05** | Outiller le suivi des livraisons partielles et des reliquats | État des reliquats disponible à tout moment |
| **OBJ-06** | Produire les états de dépense sur demande | Quatre rapports standards exportables en PDF |
| **OBJ-07** | Conserver les pièces justificatives dans l'application | Bons de commande signés et bordereaux physiques numérisés |

### 2.3 Bénéfices attendus

| Bénéfice | Bénéficiaire |
|---|---|
| Suppression de la double saisie à la réception | Magasinier |
| Vision temps réel de l'engagement financier | Direction, Contrôle de gestion |
| Historique d'acquisition exploitable pour l'amortissement | Comptabilité |
| Identification immédiate du fournisseur d'un matériel défaillant | Support technique |
| Anticipation des ruptures de consommables | Magasinier, Acheteur |

---

## 3. Périmètre

### 3.1 Périmètre fonctionnel inclus

| Domaine | Contenu |
|---|---|
| **Catalogue** | Référencement des articles achetables : équipements, consommables, licences, prestations |
| **Commande** | Émission, validation, modification, annulation et impression des bons de commande fournisseurs |
| **Réception** | Enregistrement des bordereaux de livraison, gestion des livraisons partielles et des reliquats |
| **Intégration** | Assistant de saisie d'inventaire et création automatique des enregistrements dans ParcInfo et Stock |
| **Stock** | Consultation du niveau de stock des consommables et alertes de seuil |
| **Restitution** | Tableau de bord, états statistiques, rapports exportables |
| **Documentation** | Rattachement de pièces jointes aux bons de commande et aux bordereaux |
| **Paramétrage** | Formats de numérotation, préfixes, patterns de code inventaire |

### 3.2 Périmètre exclu

| Exclusion | Justification / traitement alternatif |
|---|---|
| Demande d'achat et expression de besoin en amont | Traité hors application dans la version courante — candidat au lot 3 |
| Circuit d'approbation hiérarchique multi-niveaux | Une validation unique est retenue — candidat au lot 3 |
| Consultation fournisseurs, appels d'offres, marchés | Relève du service des marchés, hors SI parc informatique |
| Facturation, mandatement et règlement fournisseur | Relève du système comptable et financier |
| Gestion des immobilisations et calcul d'amortissement | Le module fournit les données sources ; le calcul relève de la comptabilité |
| Mouvements de stock manuels (entrée, sortie, inventaire) | Assurés par le module Stock |
| Gestion du cycle de vie des licences après acquisition | Assurée par le module ParcInfo |
| Gestion des retours et litiges fournisseurs | Non couvert — candidat au lot 4 |

### 3.3 Interfaces avec le système d'information

| Système | Nature de l'échange | Sens | Criticité |
|---|---|---|---|
| **ParcInfo — référentiels** | Marques, catégories d'équipement et leurs champs personnalisés, fournisseurs | Lecture | **Bloquante** |
| **ParcInfo — parc** | Fiches équipement, historique de changement | Écriture | **Bloquante** |
| **ParcInfo — logiciels** | Logiciels et licences | Écriture | Forte |
| **ParcInfo — consommables** | Consommables et mouvements | Écriture | Forte |
| **Stock** | Mouvements d'entrée et lots de valorisation | Écriture | Forte |
| **Core / Authentification** | Utilisateurs, rôles, permissions | Lecture | **Bloquante** |
| **Stockage de fichiers** | Pièces jointes et images d'article | Lecture / Écriture | Moyenne |

> **EXI-INT-00** — Le module ne doit pas dupliquer les référentiels détenus par ParcInfo. Toute marque, catégorie ou fournisseur est créé et maintenu dans ParcInfo, et seulement consommé par le module Achat.

---

## 4. Acteurs et organisation

### 4.1 Acteurs utilisateurs

| Acteur | Profil | Responsabilités attendues |
|---|---|---|
| **Acheteur** | Agent du service approvisionnement | Maintient le catalogue, saisit les bons de commande, suit les reliquats |
| **Validateur Achat** | Responsable hiérarchique | Contrôle et valide les bons de commande, prononce les annulations |
| **Magasinier** | Agent du magasin | Réceptionne physiquement, saisit les bordereaux, exécute l'assistant d'intégration |
| **Consultant** | Direction, contrôle de gestion | Consulte les états et rapports, sans droit de modification |
| **Administrateur** | Administrateur applicatif | Paramètre le module, gère les habilitations |

> **EXI-ORG-01** — La séparation des fonctions entre **celui qui commande** et **celui qui valide** doit être garantie techniquement. Un utilisateur ne disposant que du profil Acheteur ne doit en aucun cas pouvoir valider un bon de commande.
> **Couverture actuelle : ◐** — les actions de validation contrôlent la permission de modification et non une permission dédiée.

### 4.2 Matrice RACI

**R** = Réalise · **A** = Approuve · **C** = Consulté · **I** = Informé

| Activité | Acheteur | Validateur | Magasinier | Consultant | Administrateur |
|---|:---:|:---:|:---:|:---:|:---:|
| Référencer un article | **R** | I | C | | |
| Saisir un bon de commande | **R** | I | | | |
| Valider un bon de commande | C | **R/A** | I | | |
| Annuler un bon de commande | C | **R/A** | I | | |
| Réceptionner une livraison | I | I | **R** | | |
| Saisir l'inventaire (assistant) | | | **R** | | |
| Finaliser l'intégration au parc | I | I | **R/A** | | |
| Joindre une pièce justificative | **R** | **R** | **R** | | |
| Consulter les états | C | C | C | **R** | |
| Paramétrer le module | | | | | **R/A** |
| Gérer les habilitations | | | | | **R/A** |

### 4.3 Volumétrie de référence

*Hypothèses à confirmer par la maîtrise d'ouvrage.*

| Objet | Volume annuel estimé | Stock cumulé à 5 ans |
|---|---|---|
| Articles au catalogue | +150 | ~800 |
| Bons de commande | 300 à 500 | ~2 000 |
| Lignes de commande | 1 500 à 2 500 | ~10 000 |
| Bordereaux de livraison | 400 à 700 | ~3 000 |
| Équipements intégrés | 800 à 1 200 | ~5 000 |
| Documents joints | 800 à 1 400 | ~6 000 fichiers, ~15 Go |
| Utilisateurs simultanés | 5 à 15 | — |

---

## 5. Processus métier cibles

### 5.1 Processus principal — De l'engagement à l'intégration

```
 ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐
 │ Référen- │──▶│ Émission │──▶│Validation│──▶│Réception │──▶│Intégra-  │
 │ cement   │   │   du BC  │   │   du BC  │   │   du BL  │   │  tion    │
 └──────────┘   └──────────┘   └──────────┘   └──────────┘   └──────────┘
   Acheteur       Acheteur      Validateur     Magasinier     Magasinier
      │               │              │              │              │
      ▼               ▼              ▼              ▼              ▼
  Catalogue       BC brouillon    BC validé     BL brouillon   Équipements
   à jour         non engageant   engageant     reliquat suivi  au parc
                                                                Stock à jour
                                                                BC soldé
```

**Jalons de contrôle**

| Jalon | Contrôle attendu | Conséquence du franchissement |
|---|---|---|
| Émission | Le bon comporte au moins une ligne et un fournisseur identifié | Aucune : le brouillon reste librement modifiable |
| **Validation** | Contrôle hiérarchique du contenu et du montant | **Le bon devient non modifiable et engageant** |
| Réception | Les quantités reçues ne dépassent pas les quantités restant à livrer | Le bordereau reste modifiable tant qu'il est en brouillon |
| **Intégration** | Toutes les données d'inventaire sont saisies et cohérentes | **Création définitive et irréversible au parc** |

### 5.2 Processus de livraison partielle

Une commande peut être livrée en plusieurs fois. Le module doit assurer le suivi du reliquat sans intervention manuelle.

```
BC validé : 10 unités commandées
     │
     ├──▶ BL n°1 : 6 unités reçues ──▶ intégration ──▶ BC « partiel », reliquat = 4
     │
     └──▶ BL n°2 : 4 unités reçues ──▶ intégration ──▶ BC « livré », reliquat = 0
```

> **EXI-PRO-01** — Le reste à livrer de chaque ligne doit être calculé automatiquement et proposé par défaut à la saisie d'une nouvelle réception. Aucune saisie ne doit pouvoir dépasser ce reste. **Couverture : ✔**

### 5.3 Processus d'intégration selon la nature de l'article

| Nature de l'article | Saisie d'inventaire requise | Enregistrements générés |
|---|:---:|---|
| **Équipement** | Oui — une saisie par unité physique | Fiche équipement individuelle + entrée d'historique d'acquisition |
| **Licence** | Oui — une saisie par licence | Logiciel (si absent) + licence individuelle |
| **Consommable** | Non | Incrément du stock + mouvement d'entrée |
| **Prestation** | Non | Aucun — la prestation est constatée sans objet physique |

> **EXI-PRO-02** — Une livraison ne contenant ni équipement ni licence doit être intégrable **sans passer par l'assistant**, en une seule action. **Couverture : ✔**

### 5.4 Cas particuliers à traiter

| Cas | Traitement attendu | Couverture |
|---|---|---|
| Commande annulée avant validation | Passage en statut annulé, aucune trace comptable | ✔ |
| Commande annulée après validation, avant livraison | Passage en statut annulé, l'engagement est levé | ✔ |
| Commande partiellement livrée puis abandonnée | **Clôture du reliquat sans annulation du bon** | **✘ — non couvert** |
| Livraison excédentaire par rapport à la commande | Refus de la saisie et alerte à l'utilisateur | ✔ |
| Matériel livré non conforme, refusé à la réception | Non-saisie de l'unité concernée dans le bordereau | ◐ — pas de motif de refus tracé |
| Erreur de saisie constatée après intégration | **Correction depuis le module ParcInfo, hors module Achat** | ◐ — aucune procédure formalisée |
| Assistant ouvert par erreur | **Retour au statut brouillon possible** | **✘ — irréversible aujourd'hui** |

---

## 6. Exigences fonctionnelles

### 6.1 Domaine « Catalogue des articles » — EF-CAT

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **EF-CAT-01** | Le système doit permettre de référencer un article achetable caractérisé par un code unique, une désignation, une marque et une nature. | M | ✔ |
| **EF-CAT-02** | Le système doit distinguer quatre natures d'article : équipement, consommable, licence, prestation. | M | ✔ |
| **EF-CAT-03** | Les informations demandées à la saisie doivent s'adapter à la nature de l'article : catégorie pour un équipement, seuil d'alerte pour un consommable, durée de validité pour une licence. | M | ✔ |
| **EF-CAT-04** | Le système doit garantir l'unicité du code article et l'unicité de la référence constructeur au sein d'une même marque. | M | ✔ |
| **EF-CAT-05** | Le système doit permettre d'enregistrer un prix indicatif servant de valeur par défaut lors de la commande. | M | ✔ |
| **EF-CAT-06** | Le système doit permettre de rattacher un fournisseur préféré à un article. | S | ✔ |
| **EF-CAT-07** | Le système doit permettre de désactiver un article sans le supprimer, afin de le retirer des choix de commande tout en conservant l'historique. | M | ✔ |
| **EF-CAT-08** | Le système doit interdire la suppression d'un article déjà engagé dans une commande, et proposer sa désactivation. | M | ✔ |
| **EF-CAT-09** | Le système doit permettre de dupliquer un article pour accélérer le référencement de variantes. | C | ✔ |
| **EF-CAT-10** | Le système doit permettre de rechercher et filtrer le catalogue par nature, marque, catégorie et état d'activité. | M | ✔ |
| **EF-CAT-11** | Le système doit permettre d'associer une photographie et un lien vers la fiche technique du fabricant. | C | ✔ |
| **EF-CAT-12** | Le système doit permettre d'enregistrer le compte comptable d'imputation et le taux de TVA applicable à l'article. | S | ◐ |
| **EF-CAT-13** | Le système doit proposer une génération automatique du code article lorsque l'utilisateur ne le saisit pas. | C | **✘** |

**Précisions sur EF-CAT-12** — Le taux de TVA doit être **effectivement utilisé** dans le calcul des montants toutes taxes comprises. L'implémentation actuelle applique un taux uniforme de 18 % codé dans l'interface, ce qui produit des montants erronés pour tout article dérogatoire.

**Précisions sur EF-CAT-13** — L'interface annonce actuellement une génération automatique qui n'existe pas, ce qui met l'utilisateur en échec. Deux issues sont acceptables : implémenter la génération, ou supprimer la mention et rendre le champ explicitement obligatoire.

---

### 6.2 Domaine « Bons de commande » — EF-BC

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **EF-BC-01** | Le système doit permettre de créer un bon de commande à destination d'un fournisseur, comportant une ou plusieurs lignes d'articles. | M | ✔ |
| **EF-BC-02** | Le système doit attribuer automatiquement un numéro de commande unique, séquentiel et annualisé. | M | ✔ |
| **EF-BC-03** | Le système doit permettre de saisir un prix négocié par ligne, distinct du prix indicatif du catalogue. | M | ✔ |
| **EF-BC-04** | Le système doit calculer et afficher en temps réel le montant de chaque ligne et le montant total de la commande. | M | ✔ |
| **EF-BC-05** | Le système doit distinguer clairement les montants hors taxes et toutes taxes comprises, et appliquer le taux de TVA propre à chaque article. | M | ◐ |
| **EF-BC-06** | Le système doit gérer les états suivants : brouillon, validé, partiellement livré, livré, annulé. | M | ✔ |
| **EF-BC-07** | Le système doit permettre la modification et la suppression d'un bon de commande **tant qu'il est en brouillon uniquement**. | M | ◐ |
| **EF-BC-08** | Le système doit permettre la validation d'un bon de commande par un utilisateur habilité, en enregistrant son identité et l'horodatage. | M | ✔ |
| **EF-BC-09** | La validation doit rendre le bon de commande non modifiable et autoriser la création de bordereaux de livraison. | M | ✔ |
| **EF-BC-10** | Le système doit permettre l'annulation d'un bon de commande non encore livré. | M | ✔ |
| **EF-BC-11** | Le système doit interdire l'annulation d'un bon de commande partiellement ou totalement livré. | M | ✔ |
| **EF-BC-12** | Le système doit faire évoluer automatiquement l'état du bon de commande au fil des réceptions. | M | ✔ |
| **EF-BC-13** | Le système doit présenter pour chaque ligne la quantité commandée, la quantité livrée, le reste à livrer et un état de livraison. | M | ✔ |
| **EF-BC-14** | Le système doit permettre de rechercher et filtrer les bons de commande par fournisseur, état et recherche libre. | M | ✔ |
| **EF-BC-15** | Le système doit produire un bon de commande imprimable au format A4, aux couleurs de l'établissement, exportable en PDF. | M | ✔ |
| **EF-BC-16** | Le système doit présenter, depuis la fiche d'un bon de commande, l'ensemble des bordereaux de livraison et des équipements générés. | M | ✔ |
| **EF-BC-17** | Le système doit présenter une chronologie des événements du bon de commande : création, validation, réceptions. | S | ◐ |
| **EF-BC-18** | Le système doit permettre de clôturer le reliquat d'un bon de commande partiellement livré et abandonné, sans annuler les livraisons déjà intégrées. | S | **✘** |
| **EF-BC-19** | Le système doit alerter l'utilisateur sur les bons de commande validés dont le reliquat est ouvert depuis plus d'un délai paramétrable. | C | **✘** |

**Précisions sur EF-BC-07** — La règle métier est correctement implémentée, mais la fonction de suppression est **techniquement inaccessible** en raison d'une permission déclarée dans le code sans être créée en base. Correction indispensable.

**Précisions sur EF-BC-17** — La chronologie actuelle est reconstituée à l'affichage à partir de trois dates. Elle ne restitue ni les modifications successives, ni l'identité des intervenants sur chaque changement d'état. Une journalisation réelle des événements est attendue pour satisfaire pleinement l'exigence.

---

### 6.3 Domaine « Bordereaux de livraison » — EF-BL

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **EF-BL-01** | Le système doit permettre d'enregistrer une réception rattachée à un bon de commande validé ou partiellement livré. | M | ✔ |
| **EF-BL-02** | Le système doit interdire la création d'un bordereau pour un bon de commande en brouillon, annulé ou déjà totalement livré. | M | ✔ |
| **EF-BL-03** | Le système doit attribuer automatiquement un numéro de livraison unique, séquentiel et annualisé. | M | ✔ |
| **EF-BL-04** | Le système doit exiger la saisie de la référence du bordereau physique du fournisseur et en garantir l'unicité. | M | ✔ |
| **EF-BL-05** | Le système doit charger automatiquement les lignes du bon de commande avec, pour chacune, la quantité commandée, la quantité déjà livrée et le reste à livrer. | M | ✔ |
| **EF-BL-06** | Le système doit proposer par défaut le reste à livrer comme quantité reçue, tout en permettant sa modification à la baisse. | M | ✔ |
| **EF-BL-07** | Le système doit interdire la saisie d'une quantité reçue supérieure au reste à livrer. | M | ✔ |
| **EF-BL-08** | Le système doit permettre de retirer une ligne non concernée par la réception en cours. | M | ✔ |
| **EF-BL-09** | Le système doit permettre de consigner des observations de réception (matériel abîmé, quantité manquante). | M | ✔ |
| **EF-BL-10** | Le système doit gérer les états suivants : brouillon, en cours d'intégration, validé. | M | ✔ |
| **EF-BL-11** | Le système doit permettre la modification d'un bordereau tant qu'il est en brouillon. | M | ✔ |
| **EF-BL-12** | Le système doit permettre de revenir de l'état « en cours d'intégration » à l'état « brouillon » tant qu'aucune intégration n'a été finalisée. | S | **✘** |
| **EF-BL-13** | Le système doit interdire toute modification d'un bordereau validé. | M | ✔ |
| **EF-BL-14** | Le système doit interdire le changement du bon de commande de rattachement après création du bordereau. | S | **✘** |
| **EF-BL-15** | Le système doit produire un bordereau de réception imprimable et exportable en PDF. | M | ✔ |
| **EF-BL-16** | Le système doit permettre de rechercher et filtrer les bordereaux par bon de commande, état et recherche libre. | M | ✔ |
| **EF-BL-17** | Le système doit présenter, depuis la fiche d'un bordereau, les équipements générés par son intégration. | M | ✔ |
| **EF-BL-18** | Le système doit permettre de consigner le motif de refus d'une unité non conforme. | C | **✘** |

**Précisions sur EF-BL-12** — L'ouverture de l'assistant fait aujourd'hui basculer irréversiblement le bordereau hors du statut brouillon, le rendant définitivement non modifiable et non supprimable. Une ouverture accidentelle bloque donc le document. Un retour arrière est attendu tant qu'aucune intégration n'a été finalisée.

**Précisions sur EF-BL-14** — Le mode d'édition permet actuellement de rattacher un bordereau à un autre bon de commande, ce qui recharge intégralement ses lignes sans trace de l'opération. Ce comportement doit être neutralisé : un rattachement erroné doit se corriger par suppression et ressaisie.

---

### 6.4 Domaine « Assistant d'intégration » — EF-INT

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **EF-INT-01** | Le système doit proposer un assistant guidé de saisie d'inventaire, présentant une étape par article nécessitant une identification unitaire. | M | ✔ |
| **EF-INT-02** | L'assistant doit présenter autant de blocs de saisie que d'unités livrées pour chaque article. | M | ✔ |
| **EF-INT-03** | Pour un équipement, l'assistant doit exiger le numéro de série et permettre la saisie du code inventaire. | M | ✔ |
| **EF-INT-04** | Le système doit générer automatiquement le code inventaire lorsqu'il n'est pas saisi, selon un format paramétrable, en garantissant l'unicité y compris en cas d'accès concurrents. | M | ✔ |
| **EF-INT-05** | L'assistant doit présenter dynamiquement les caractéristiques techniques propres à la catégorie de l'équipement, telles que définies dans le module ParcInfo. | M | ✔ |
| **EF-INT-06** | Pour une licence, l'assistant doit exiger la clé de licence et la date d'activation, et permettre la saisie d'une date d'expiration. | M | ✔ |
| **EF-INT-07** | L'assistant doit permettre l'enregistrement partiel des saisies et leur reprise ultérieure sans perte. | M | ✔ |
| **EF-INT-08** | L'assistant doit indiquer visuellement l'avancement : étapes complétées, étape en cours, étapes restantes. | M | ✔ |
| **EF-INT-09** | Le système doit interdire la finalisation tant que toutes les étapes ne sont pas complétées. | M | ✔ |
| **EF-INT-10** | Le système doit vérifier que le nombre d'unités saisies correspond exactement à la quantité livrée. | M | ✔ |
| **EF-INT-11** | Le système doit vérifier l'unicité du numéro de série et du code inventaire dans l'ensemble du parc avant création. | M | ✔ |
| **EF-INT-12** | Le système doit demander une confirmation explicite avant la finalisation, en informant l'utilisateur du caractère définitif de l'opération. | M | ✔ |
| **EF-INT-13** | L'intégration doit être atomique : en cas d'erreur sur une unité, aucune création ne doit être conservée. | M | ✔ |
| **EF-INT-14** | Le système doit rendre compte du résultat de l'intégration en indiquant le nombre d'équipements et de licences créés. | M | ✔ |
| **EF-INT-15** | Chaque équipement créé doit être initialisé au statut « en stock » et à l'état « bon », valorisé au prix effectivement commandé, et rattaché au bordereau d'origine. | M | ✔ |
| **EF-INT-16** | Chaque équipement créé doit générer une entrée d'historique d'acquisition référençant le bordereau et le bon de commande. | M | ✔ |
| **EF-INT-17** | Le système doit permettre l'impression des étiquettes d'inventaire des équipements générés. | S | ✔ |
| **EF-INT-18** | Le système doit permettre la saisie en série de caractéristiques communes à toutes les unités d'un même article, afin d'éviter la saisie répétitive. | C | **✘** |
| **EF-INT-19** | Le système doit permettre l'import d'un fichier de numéros de série pour les livraisons de volume important. | C | **✘** |

**Précisions sur EF-INT-18** — La structure de données prévoit un emplacement pour les attributs communs, mais l'interface ne l'exploite pas. Pour une livraison de vingt postes identiques, l'utilisateur doit ressaisir vingt fois les mêmes caractéristiques techniques.

---

### 6.5 Domaine « Stock » — EF-STK

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **EF-STK-01** | Le système doit présenter le niveau de stock des consommables issus du catalogue. | M | ✔ |
| **EF-STK-02** | Le système doit signaler visuellement les consommables dont le stock est inférieur ou égal au seuil d'alerte. | M | ✔ |
| **EF-STK-03** | Le système doit permettre de filtrer les consommables selon leur situation par rapport au seuil. | M | ✔ |
| **EF-STK-04** | Le système doit incrémenter automatiquement le stock d'un consommable lors de la validation d'une réception. | M | ✔ |
| **EF-STK-05** | Le système doit comptabiliser le stock dans un **référentiel unique et non ambigu**. | M | **✘** |
| **EF-STK-06** | Le système doit restituer le nombre de consommables en situation d'alerte sur le tableau de bord. | M | ✔ |
| **EF-STK-07** | Le système doit gérer plusieurs niveaux d'alerte (rupture, critique, alerte, faible) conformément au paramétrage. | C | **✘** |

**Précisions sur EF-STK-05 — exigence critique** — L'implémentation actuelle enregistre une même réception de consommable dans **trois compteurs distincts** : le compteur du catalogue Achat, le compteur des consommables ParcInfo et les lots du module Stock. Par ailleurs, les équipements et les licences sont eux aussi enregistrés dans le module Stock alors qu'ils font l'objet de fiches individuelles. Cette situation rend le stock non fiable et non auditable.

**Décision attendue de la maîtrise d'ouvrage** : désigner le référentiel de stock faisant foi, et cantonner les autres compteurs à un rôle d'affichage dérivé ou les supprimer.

---

### 6.6 Domaine « États et restitutions » — EF-RAP

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **EF-RAP-01** | Le système doit présenter un tableau de bord synthétique : volumétrie du catalogue, des commandes, des livraisons et des alertes de stock. | M | ✔ |
| **EF-RAP-02** | Le tableau de bord doit présenter l'évolution mensuelle de la dépense sur les **douze derniers mois glissants**. | M | ◐ |
| **EF-RAP-03** | Le tableau de bord doit présenter la répartition de la dépense par fournisseur. | M | ✔ |
| **EF-RAP-04** | Le tableau de bord doit présenter les dernières commandes et les dernières livraisons avec accès direct à leur fiche. | S | ✔ |
| **EF-RAP-05** | Le système doit produire un état global des bons de commande, filtrable par fournisseur, état et période. | M | ✔ |
| **EF-RAP-06** | Le système doit produire un état des dépenses par fournisseur. | M | ✔ |
| **EF-RAP-07** | Le système doit produire un état des reliquats de livraison. | M | ✔ |
| **EF-RAP-08** | Le système doit produire un état des articles les plus commandés. | S | ✔ |
| **EF-RAP-09** | Tout état doit être consultable à l'écran et exportable en PDF, en rappelant les filtres appliqués. | M | ✔ |
| **EF-RAP-10** | Le système doit présenter un indicateur de taux de complétion des livraisons. | S | ✔ |
| **EF-RAP-11** | Les états doivent être exportables au format tableur pour retraitement. | S | **✘** |
| **EF-RAP-12** | Le système doit produire un état de la dépense par catégorie d'équipement et par imputation comptable. | C | **✘** |

**Précisions sur EF-RAP-02** — La restitution actuelle porte sur six périodes et retourne, du fait de l'ordre de tri appliqué, **les six premiers mois de l'historique et non les six derniers**. L'indicateur se fige donc progressivement sur des données anciennes. Correction indispensable.

---

### 6.7 Domaine « Gestion documentaire » — EF-DOC

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **EF-DOC-01** | Le système doit permettre de joindre des pièces justificatives à un bon de commande et à un bordereau de livraison. | M | ✔ |
| **EF-DOC-02** | Le système doit accepter les formats PDF, image, traitement de texte et tableur, dans la limite de 10 Mo par fichier. | M | ✔ |
| **EF-DOC-03** | Le système doit permettre de nommer et de commenter chaque pièce jointe. | S | ✔ |
| **EF-DOC-04** | Le système doit permettre le téléchargement et la suppression des pièces jointes. | M | ✔ |
| **EF-DOC-05** | Le système doit enregistrer l'auteur et la date d'ajout de chaque pièce. | M | ✔ |
| **EF-DOC-06** | L'accès aux pièces jointes doit être soumis aux mêmes habilitations que le document auquel elles se rattachent. | M | **✘** |
| **EF-DOC-07** | Le système doit indiquer le nombre de pièces jointes sans nécessiter l'ouverture de l'onglet correspondant. | C | ✔ |

**Précisions sur EF-DOC-06 — exigence de sécurité** — Le service de gestion documentaire ne procède actuellement à **aucun contrôle d'habilitation**. Tout utilisateur authentifié, quel que soit son profil, peut téléverser, télécharger ou supprimer une pièce jointe de n'importe quel bon de commande ou bordereau. Correction indispensable.

---

### 6.8 Domaine « Paramétrage et administration » — EF-ADM

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **EF-ADM-01** | Le système doit permettre de paramétrer les préfixes de numérotation des bons de commande et des bordereaux. | M | ◐ |
| **EF-ADM-02** | Le système doit permettre de paramétrer le format de génération des codes inventaire. | M | ◐ |
| **EF-ADM-03** | Le paramétrage doit être modifiable sans intervention technique ni redéploiement de l'application. | S | **✘** |
| **EF-ADM-04** | Le système doit permettre de paramétrer les seuils d'alerte de stock. | C | **✘** |
| **EF-ADM-05** | Toute permission déclarée dans le référentiel des habilitations doit correspondre à une fonctionnalité effectivement contrôlée. | M | **✘** |

**Précisions sur EF-ADM-01 à EF-ADM-03** — Les paramètres sont stockés en base et donc techniquement modifiables, mais **aucune interface d'administration n'est fournie**. Leur modification suppose aujourd'hui une intervention directe sur la base de données.

**Précisions sur EF-ADM-05** — Sept permissions déclarées ne correspondent à aucune fonctionnalité (mouvements de stock, gestion des licences, rapports), tandis que deux permissions contrôlées dans le code ne sont pas déclarées. L'écran d'administration des droits présente donc des habilitations sans portée réelle et masque des fonctions inaccessibles.

---

## 7. Exigences non fonctionnelles

### 7.1 Sécurité et habilitations — ENF-SEC

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **ENF-SEC-01** | L'accès à toute fonctionnalité du module doit être conditionné à une authentification et à une adresse électronique vérifiée. | M | ✔ |
| **ENF-SEC-02** | Chaque action sensible doit être soumise à une permission dédiée, distincte des permissions de consultation et de modification. | M | **✘** |
| **ENF-SEC-03** | Les commandes de l'interface non autorisées ne doivent pas être présentées à l'utilisateur. | M | ✔ |
| **ENF-SEC-04** | Le contrôle d'habilitation doit être effectué **côté serveur** pour toute action, indépendamment du masquage de l'interface. | M | ◐ |
| **ENF-SEC-05** | Le système doit se prémunir contre la falsification de requête intersites sur toutes les actions de modification. | M | ✔ |
| **ENF-SEC-06** | Les fichiers téléversés doivent être contrôlés en type et en taille, et stockés hors de l'arborescence exécutable. | M | ◐ |
| **ENF-SEC-07** | Aucune donnée du module ne doit être accessible sans habilitation, y compris par accès direct à une adresse. | M | ◐ |

### 7.2 Traçabilité et auditabilité — ENF-TRA

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **ENF-TRA-01** | Toute entité principale doit conserver l'identité de son créateur et de son dernier modificateur, ainsi que les horodatages correspondants. | M | ✔ |
| **ENF-TRA-02** | Aucune donnée métier ne doit être supprimée physiquement : la suppression doit être logique et réversible. | M | ✔ |
| **ENF-TRA-03** | Tout changement d'état d'un bon de commande ou d'un bordereau doit être journalisé avec son auteur, sa date et son motif. | M | **✘** |
| **ENF-TRA-04** | Tout équipement du parc issu d'un achat doit être rattachable à son bordereau, son bon de commande et son fournisseur. | M | ✔ |
| **ENF-TRA-05** | Le journal des événements doit être consultable depuis la fiche du document concerné et non altérable par les utilisateurs. | S | **✘** |

### 7.3 Intégrité et fiabilité — ENF-FIA

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **ENF-FIA-01** | Toute opération portant sur plusieurs enregistrements doit être transactionnelle. | M | ✔ |
| **ENF-FIA-02** | La génération des numéros séquentiels doit résister aux accès concurrents. | M | ◐ |
| **ENF-FIA-03** | Les règles d'unicité doivent être garanties au niveau de la base de données et non uniquement applicative. | M | ✔ |
| **ENF-FIA-04** | Un montant affiché à l'utilisateur doit être calculé de manière identique partout dans l'application. | M | **✘** |
| **ENF-FIA-05** | Le système doit présenter un message d'erreur explicite et actionnable pour toute règle métier non satisfaite. | M | ✔ |
| **ENF-FIA-06** | Un message de succès ne doit jamais être présenté pour une opération n'ayant pas abouti. | M | ◐ |

**Précisions sur ENF-FIA-02** — La génération du code inventaire est correctement verrouillée en base. En revanche, les numéros de bon de commande et de bordereau sont calculés par comptage puis vérification, ce qui présente une fenêtre de concurrence résiduelle sous forte charge.

### 7.4 Performance — ENF-PER

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **ENF-PER-01** | L'affichage d'une liste doit s'effectuer en moins de 2 secondes pour 10 000 enregistrements. | M | ✔ |
| **ENF-PER-02** | La pagination et le tri des listes doivent être exécutés côté serveur. | M | ✔ |
| **ENF-PER-03** | L'enregistrement d'un bon de commande de 50 lignes ne doit pas excéder 3 secondes. | M | ✔ |
| **ENF-PER-04** | L'intégration d'une livraison de 50 unités ne doit pas excéder 10 secondes. | S | ◐ |
| **ENF-PER-05** | La génération d'un document PDF ne doit pas excéder 5 secondes. | S | ✔ |
| **ENF-PER-06** | Les requêtes de restitution doivent s'appuyer sur des index adaptés aux critères de filtrage. | M | ✔ |

### 7.5 Ergonomie et expérience utilisateur — ENF-ERG

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **ENF-ERG-01** | L'interface doit respecter la charte graphique de l'application définie dans le document de référence de conception. | M | ◐ |
| **ENF-ERG-02** | Toute action destructrice ou irréversible doit faire l'objet d'une confirmation explicite mentionnant ses conséquences. | M | ✔ |
| **ENF-ERG-03** | Toute action doit produire un retour visible : message de succès, message d'erreur ou indicateur de progression. | M | ✔ |
| **ENF-ERG-04** | Les libellés, messages et formats doivent être intégralement en langue française. | M | ✔ |
| **ENF-ERG-05** | Les montants doivent être présentés en francs CFA avec séparateur de milliers. | M | ✔ |
| **ENF-ERG-06** | Les dates doivent être présentées au format jour/mois/année. | M | ✔ |
| **ENF-ERG-07** | Une action indisponible en raison de l'état de l'objet doit être présentée désactivée avec explication, plutôt que masquée. | S | ✔ |
| **ENF-ERG-08** | Toute liste vide doit présenter un message explicatif plutôt qu'un tableau vide. | M | ✔ |
| **ENF-ERG-09** | La navigation doit présenter systématiquement un fil d'Ariane. | S | ✔ |
| **ENF-ERG-10** | Les écrans doivent rester exploitables sur une résolution de 1366 × 768 pixels. | M | ✔ |
| **ENF-ERG-11** | Une saisie longue interrompue ne doit pas entraîner de perte de données. | S | ◐ |

**Précisions sur ENF-ERG-11** — L'assistant d'intégration sauvegarde ses étapes, ce qui satisfait l'exigence pour la saisie la plus longue. En revanche, la saisie d'un bon de commande n'est pas protégée : une interruption avant enregistrement entraîne la perte de la totalité des lignes saisies.

### 7.6 Exploitabilité et maintenance — ENF-TEC

| Réf. | Exigence | Prio. | Couv. |
|---|---|:---:|:---:|
| **ENF-TEC-01** | Le module doit respecter l'architecture modulaire de l'application et rester désactivable indépendamment. | M | ◐ |
| **ENF-TEC-02** | Les règles métier doivent être implémentées dans une couche de service distincte des contrôleurs et des vues. | M | ✔ |
| **ENF-TEC-03** | Le code applicatif ne doit contenir aucune valeur métier codée en dur susceptible d'évoluer. | M | **✘** |
| **ENF-TEC-04** | Le module doit être couvert par des tests automatisés portant sur l'ensemble des règles de gestion critiques. | M | ◐ |
| **ENF-TEC-05** | Les requêtes de la base de données ne doivent pas dépendre de fonctions propres à un moteur particulier. | S | **✘** |
| **ENF-TEC-06** | Le code ne doit contenir ni fichier mort, ni logique dupliquée entre plusieurs écrans. | S | **✘** |
| **ENF-TEC-07** | La documentation fonctionnelle doit être maintenue à jour à chaque évolution. | M | ✔ |

**Précisions sur ENF-TEC-01** — Le module dépend désormais du module Stock pour la validation des livraisons. La désactivation de Stock rend l'intégration inopérante. Cette dépendance doit être soit assumée et documentée comme prérequis, soit rendue optionnelle.

**Précisions sur ENF-TEC-04** — Neuf tests fonctionnels couvrent le chemin nominal. Ne sont pas couverts : modification et suppression, annulation, livraisons partielles successives, gestion documentaire, états et restitutions.

**Précisions sur ENF-TEC-05** — Deux requêtes d'agrégation utilisent une fonction propre à PostgreSQL, ce qui rend les écrans concernés non testables automatiquement.

---

## 8. Règles de gestion contractuelles

Les règles suivantes constituent le socle métier opposable. Toute évolution du module doit les préserver.

### 8.1 Règles structurantes

| Réf. | Règle |
|---|---|
| **RGC-01** | Un bon de commande validé est **définitivement non modifiable**. Toute correction impose son annulation et la création d'un nouveau bon. |
| **RGC-02** | Une réception ne peut jamais dépasser, ligne à ligne, la quantité restant à livrer du bon de commande. |
| **RGC-03** | Un bordereau de livraison validé est **irréversible** : ni modification, ni suppression, ni dévalidation. |
| **RGC-04** | Aucun équipement ne peut entrer dans le parc par le module Achat sans numéro de série et sans code inventaire, tous deux uniques. |
| **RGC-05** | L'intégration d'une livraison est **atomique** : soit l'intégralité des enregistrements est créée, soit aucun. |
| **RGC-06** | La valeur d'acquisition d'un équipement est le **prix effectivement commandé**, jamais le prix indicatif du catalogue. |
| **RGC-07** | L'état d'un bon de commande est **calculé** à partir des quantités livrées et ne peut être forcé manuellement. |
| **RGC-08** | Un article engagé dans une commande ne peut être supprimé du catalogue. |
| **RGC-09** | La référence du bordereau physique du fournisseur est unique dans l'application : elle interdit la double saisie d'une même livraison. |
| **RGC-10** | Un bon de commande ayant fait l'objet d'au moins une livraison ne peut plus être annulé. |

### 8.2 Règles de séparation des fonctions

| Réf. | Règle |
|---|---|
| **RGC-11** | La saisie d'un bon de commande et sa validation relèvent de deux habilitations distinctes. |
| **RGC-12** | La réception physique et la validation de l'intégration relèvent du magasin, distinct du service acheteur. |
| **RGC-13** | Le paramétrage du module relève exclusivement de l'administrateur applicatif. |

---

## 9. Contraintes

### 9.1 Contraintes techniques

| Contrainte | Précision |
|---|---|
| Socle applicatif | Laravel, architecture modulaire `nwidart/laravel-modules` |
| Base de données | PostgreSQL en exploitation |
| Interface | Bootstrap 5 / AdminLTE, jQuery, Bootstrap Table, SweetAlert2, Chart.js |
| Génération documentaire | DomPDF |
| Habilitations | Paquet Spatie Permission, mutualisé avec les autres modules |
| Navigateurs cibles | Deux dernières versions majeures des navigateurs courants |
| Déploiement | Serveur interne à l'établissement, pas d'hébergement externe |

### 9.2 Contraintes d'intégration

| Contrainte | Précision |
|---|---|
| **Référentiels** | Marques, catégories et fournisseurs sont détenus par ParcInfo. Le module Achat ne les crée pas. |
| **Champs personnalisés** | L'assistant doit s'adapter automatiquement aux champs définis par catégorie dans ParcInfo, sans développement spécifique. |
| **Prérequis de déploiement** | Les modules ParcInfo et Stock doivent être actifs pour que le module Achat soit opérationnel. |
| **Non-régression** | Aucune évolution du module Achat ne doit altérer les données de ParcInfo existantes. |

### 9.3 Contraintes organisationnelles

| Contrainte | Précision |
|---|---|
| Continuité de service | Les évolutions doivent être déployées hors des périodes de réception de matériel |
| Reprise de données | Les bons de commande et équipements déjà enregistrés doivent rester exploitables |
| Formation | Les évolutions modifiant les écrans doivent s'accompagner d'un support utilisateur actualisé |
| Langue | Documentation et interface intégralement en français |

### 9.4 Contraintes de conformité

| Contrainte | Précision |
|---|---|
| **Traçabilité de l'engagement** | Tout bon de commande validé doit être conservé et restituable, y compris annulé |
| **Conservation des pièces** | Les justificatifs numérisés doivent être conservés sur la durée légale applicable à l'établissement |
| **Non-répudiation** | L'identité du validateur d'un bon de commande doit être conservée de manière non altérable |
| **Piste d'audit** | Le système doit permettre de reconstituer la chaîne complète d'une acquisition lors d'un contrôle |

---

## 10. Livrables attendus

### 10.1 Livrables applicatifs

| Réf. | Livrable | Format |
|---|---|---|
| **LIV-01** | Code source du module conforme aux exigences | Dépôt versionné |
| **LIV-02** | Scripts de migration de la base de données | Migrations versionnées |
| **LIV-03** | Jeux de données initiales : permissions, rôles, paramètres | Seeders |
| **LIV-04** | Suite de tests automatisés couvrant les règles critiques | Tests fonctionnels |

### 10.2 Livrables documentaires

| Réf. | Livrable | État |
|---|---|---|
| **LIV-05** | Cahier des charges fonctionnel *(présent document)* | Fourni |
| **LIV-06** | Spécifications fonctionnelles détaillées | Fourni — `SFD_Achat.md` |
| **LIV-07** | Guide utilisateur par profil : acheteur, validateur, magasinier | **Attendu** |
| **LIV-08** | Procédure d'exploitation : paramétrage, sauvegarde, reprise | **Attendu** |
| **LIV-09** | Cahier de recette avec scénarios de test | **Attendu** |
| **LIV-10** | Matrice de traçabilité exigences ↔ écrans ↔ tests | Partiel — annexe 13.2 |

---

## 11. Recette et critères d'acceptation

### 11.1 Critères d'acceptation généraux

Le module est réputé conforme lorsque :

1. **100 % des exigences de priorité M** sont satisfaites et vérifiées par la recette ;
2. **Aucune anomalie bloquante ou majeure** n'est ouverte ;
3. Les scénarios de recette du chapitre 11.3 sont exécutés avec succès ;
4. La documentation utilisateur est livrée et validée ;
5. Les tests automatisés couvrent l'intégralité des règles de gestion contractuelles du chapitre 8.

### 11.2 Classification des anomalies

| Niveau | Définition | Traitement |
|---|---|---|
| **Bloquante** | Empêche l'utilisation d'une fonction essentielle ou compromet l'intégrité des données | Correction avant mise en service |
| **Majeure** | Produit un résultat erroné ou contourne une règle de gestion contractuelle | Correction avant mise en service |
| **Mineure** | Gêne l'utilisation sans altérer le résultat | Correction planifiée |
| **Cosmétique** | Défaut de présentation | Correction opportuniste |

### 11.3 Scénarios de recette obligatoires

| Réf. | Scénario | Exigences vérifiées | Résultat attendu |
|---|---|---|---|
| **REC-01** | Référencer un équipement, un consommable et une licence | EF-CAT-01 à 04 | Champs conditionnels adaptés, contrôles d'unicité effectifs |
| **REC-02** | Tenter de supprimer un article engagé dans une commande | EF-CAT-08 | Suppression refusée, article désactivé, message conforme |
| **REC-03** | Créer un bon de commande de 3 lignes et vérifier les montants | EF-BC-01 à 05 | Montants HT et TTC exacts au regard des taux de TVA des articles |
| **REC-04** | Valider un bon de commande avec un profil Acheteur seul | ENF-SEC-02, EXI-ORG-01 | **Action refusée** |
| **REC-05** | Valider un bon de commande avec le profil Validateur | EF-BC-08, EF-BC-09 | Bon verrouillé, validateur et date enregistrés |
| **REC-06** | Tenter de modifier un bon de commande validé | RGC-01, EF-BC-07 | Redirection et message d'erreur explicite |
| **REC-07** | Réceptionner partiellement une commande de 10 unités (6 reçues) | EF-BL-05 à 07 | Reste à livrer = 4, saisie plafonnée |
| **REC-08** | Saisir une quantité reçue supérieure au reste à livrer | RGC-02 | Saisie refusée côté interface **et** côté serveur |
| **REC-09** | Exécuter l'assistant sur 6 équipements avec caractéristiques techniques | EF-INT-01 à 05 | 6 fiches créées, codes inventaire générés, caractéristiques enregistrées |
| **REC-10** | Interrompre l'assistant à mi-parcours puis y revenir | EF-INT-07 | Saisies restituées, étapes complétées conservées |
| **REC-11** | Saisir un numéro de série déjà présent dans le parc | RGC-04, EF-INT-11 | Intégration refusée, **aucune fiche créée**, message précis |
| **REC-12** | Provoquer une erreur en fin d'intégration d'une livraison de 5 unités | RGC-05, EF-INT-13 | Aucun équipement créé, aucun compteur modifié, aucun statut altéré |
| **REC-13** | Solder la commande par une seconde livraison | EF-BC-12, EF-BL-01 | Bon de commande en état « livré », reliquat nul |
| **REC-14** | Vérifier la traçabilité d'un équipement intégré | ENF-TRA-04 | Bordereau, bon de commande, fournisseur et prix d'achat restitués |
| **REC-15** | Réceptionner une livraison de consommables uniquement | EXI-PRO-02 | Validation directe sans assistant, stock incrémenté **une seule fois** |
| **REC-16** | Produire les quatre états et les exporter en PDF | EF-RAP-05 à 09 | Données exactes, filtres rappelés dans l'export |
| **REC-17** | Vérifier la période couverte par le graphique de dépense mensuelle | EF-RAP-02 | **Douze derniers mois glissants** |
| **REC-18** | Accéder à une pièce jointe avec un profil non habilité | EF-DOC-06 | **Accès refusé** |
| **REC-19** | Supprimer un bon de commande en brouillon avec le profil habilité | EF-BC-07 | Suppression effective |
| **REC-20** | Ouvrir puis quitter l'assistant sans rien saisir | EF-BL-12 | **Retour au statut brouillon possible**, bordereau à nouveau modifiable |

---

## 12. Lotissement et trajectoire

*Charges indicatives, à valider par la maîtrise d'œuvre.*

### Lot 0 — Mise en conformité bloquante

**Objectif** : rétablir les fonctions inaccessibles et fermer les failles d'habilitation.

| Réf. | Action | Exigences | Charge |
|---|---|---|---|
| L0-1 | Déclarer les permissions de suppression manquantes | EF-BC-07, EF-BL-11 | 0,5 j |
| L0-2 | Rattacher chaque action sensible à sa permission dédiée | ENF-SEC-02, EXI-ORG-01 | 1 j |
| L0-3 | Contrôler les habilitations sur la gestion documentaire | EF-DOC-06 | 1 j |
| L0-4 | Aligner les habilitations des états sur les permissions de rapport | EF-ADM-05 | 0,5 j |
| L0-5 | Supprimer les permissions déclarées sans portée | EF-ADM-05 | 0,5 j |
| | **Total** | | **3,5 j** |

### Lot 1 — Fiabilisation des montants et des restitutions

**Objectif** : garantir l'exactitude des données financières présentées.

| Réf. | Action | Exigences | Charge |
|---|---|---|---|
| L1-1 | Appliquer le taux de TVA propre à chaque article | EF-CAT-12, EF-BC-05 | 2 j |
| L1-2 | Unifier le calcul des montants entre tous les écrans et exports | ENF-FIA-04 | 1,5 j |
| L1-3 | Qualifier explicitement les montants HT et TTC dans toutes les restitutions | EF-BC-05 | 1 j |
| L1-4 | Corriger la période des agrégations mensuelles | EF-RAP-02 | 0,5 j |
| L1-5 | Rendre les agrégations indépendantes du moteur de base de données | ENF-TEC-05 | 1 j |
| L1-6 | Aligner les retours applicatifs sur le résultat réel des opérations | ENF-FIA-06 | 0,5 j |
| | **Total** | | **6,5 j** |

### Lot 2 — Fiabilisation du stock

**Objectif** : disposer d'un référentiel de stock unique et auditable.

| Réf. | Action | Exigences | Charge |
|---|---|---|---|
| L2-0 | **Arbitrage de la maîtrise d'ouvrage** : désigner le référentiel faisant foi | EF-STK-05 | — |
| L2-1 | Supprimer les comptabilisations redondantes | EF-STK-05 | 3 j |
| L2-2 | Restreindre l'alimentation du module Stock aux natures d'article concernées | EF-STK-05 | 1,5 j |
| L2-3 | Reprise et réconciliation des données de stock existantes | EF-STK-05 | 2 j |
| L2-4 | Gérer les niveaux d'alerte paramétrés | EF-STK-07 | 1 j |
| | **Total** | | **7,5 j** *(hors arbitrage)* |

### Lot 3 — Robustesse du processus de réception

**Objectif** : rendre le processus tolérant aux erreurs de manipulation.

| Réf. | Action | Exigences | Charge |
|---|---|---|---|
| L3-1 | Permettre le retour au statut brouillon depuis l'assistant | EF-BL-12 | 1,5 j |
| L3-2 | Interdire le changement de bon de commande d'un bordereau existant | EF-BL-14 | 0,5 j |
| L3-3 | Permettre la clôture d'un reliquat abandonné | EF-BC-18 | 2 j |
| L3-4 | Journaliser réellement les changements d'état | ENF-TRA-03, ENF-TRA-05, EF-BC-17 | 3 j |
| L3-5 | Protéger la saisie d'un bon de commande contre les interruptions | ENF-ERG-11 | 1,5 j |
| | **Total** | | **8,5 j** |

### Lot 4 — Confort d'usage et industrialisation

| Réf. | Action | Exigences | Charge |
|---|---|---|---|
| L4-1 | Saisie en série des caractéristiques communes dans l'assistant | EF-INT-18 | 2,5 j |
| L4-2 | Génération automatique du code article | EF-CAT-13 | 1 j |
| L4-3 | Écran d'administration du paramétrage | EF-ADM-01 à 04 | 2,5 j |
| L4-4 | Export des états au format tableur | EF-RAP-11 | 1,5 j |
| L4-5 | Alerte sur les reliquats anciens | EF-BC-19 | 1,5 j |
| L4-6 | Extension de la couverture de tests automatisés | ENF-TEC-04 | 3 j |
| L4-7 | Suppression du code mort et de la logique dupliquée | ENF-TEC-06 | 1,5 j |
| L4-8 | Harmonisation de la charte graphique | ENF-ERG-01 | 1 j |
| | **Total** | | **14,5 j** |

### Lot 5 — Extensions fonctionnelles *(sous réserve d'arbitrage)*

| Réf. | Action | Priorité |
|---|---|---|
| L5-1 | Demande d'achat et expression de besoin en amont du bon de commande | W |
| L5-2 | Circuit d'approbation multi-niveaux par seuil de montant | W |
| L5-3 | Import de numéros de série pour les livraisons de volume | C |
| L5-4 | Gestion des retours et litiges fournisseurs | W |
| L5-5 | État de la dépense par imputation comptable | C |

### Synthèse de la trajectoire

| Lot | Objet | Charge | Priorité |
|---|---|---|---|
| **Lot 0** | Conformité bloquante | 3,5 j | **Immédiate** |
| **Lot 1** | Fiabilité des montants | 6,5 j | **Haute** |
| **Lot 2** | Fiabilité du stock | 7,5 j | **Haute** *(arbitrage préalable)* |
| **Lot 3** | Robustesse du processus | 8,5 j | Moyenne |
| **Lot 4** | Confort et industrialisation | 14,5 j | Moyenne |
| **Lot 5** | Extensions | à chiffrer | Basse |
| | **Total lots 0 à 4** | **40,5 j** | |

---

## 13. Annexes

### 13.1 Synthèse de la couverture des exigences

| Domaine | Total | ✔ Couvertes | ◐ Partielles | ✘ À réaliser | Taux de couverture |
|---|:---:|:---:|:---:|:---:|:---:|
| Catalogue (EF-CAT) | 13 | 11 | 1 | 1 | 85 % |
| Bons de commande (EF-BC) | 19 | 14 | 3 | 2 | 74 % |
| Bordereaux (EF-BL) | 18 | 15 | 0 | 3 | 83 % |
| Intégration (EF-INT) | 19 | 17 | 0 | 2 | 89 % |
| Stock (EF-STK) | 7 | 5 | 0 | 2 | 71 % |
| États (EF-RAP) | 12 | 9 | 1 | 2 | 75 % |
| Documents (EF-DOC) | 7 | 6 | 0 | 1 | 86 % |
| Administration (EF-ADM) | 5 | 0 | 2 | 3 | 0 % |
| **Total fonctionnel** | **100** | **77** | **7** | **16** | **77 %** |
| Sécurité (ENF-SEC) | 7 | 3 | 3 | 1 | 43 % |
| Traçabilité (ENF-TRA) | 5 | 3 | 0 | 2 | 60 % |
| Intégrité (ENF-FIA) | 6 | 3 | 2 | 1 | 50 % |
| Performance (ENF-PER) | 6 | 5 | 1 | 0 | 83 % |
| Ergonomie (ENF-ERG) | 11 | 9 | 2 | 0 | 82 % |
| Technique (ENF-TEC) | 7 | 2 | 2 | 3 | 29 % |
| **Total non fonctionnel** | **42** | **25** | **10** | **7** | **60 %** |
| **TOTAL GÉNÉRAL** | **142** | **102** | **17** | **23** | **72 %** |

**Lecture** — Le socle fonctionnel du module est solide : la chaîne métier principale, du catalogue à l'intégration au parc, est couverte à près de 90 %. Les écarts se concentrent sur **trois axes transverses** : l'administration du paramétrage, la sécurité des habilitations et la qualité technique. Ces trois axes constituent la substance des lots 0 à 2.

### 13.2 Matrice de traçabilité — Exigences majeures ↔ Écrans

| Exigence | Écrans concernés *(réf. SFD)* | Scénario de recette |
|---|---|---|
| EF-CAT-01 à 04 | E-02, M-01 | REC-01 |
| EF-CAT-08 | E-02 | REC-02 |
| EF-BC-01 à 05 | E-04, E-05, M-03, M-04 | REC-03 |
| EF-BC-07 à 09 | E-06 | REC-05, REC-06, REC-19 |
| EF-BC-12 | E-06, E-12 | REC-13 |
| EF-BL-01 à 08 | E-10, M-05 | REC-07, REC-08 |
| EF-BL-12 | E-11, E-12 | REC-20 |
| EF-INT-01 à 11 | E-12 | REC-09, REC-10, REC-11 |
| EF-INT-13 | E-12 | REC-12 |
| EF-STK-01 à 05 | E-14 | REC-15 |
| EF-RAP-02 | E-01, E-15 | REC-17 |
| EF-RAP-05 à 09 | E-15, E-16 | REC-16 |
| EF-DOC-06 | E-06, E-11 | REC-18 |
| ENF-SEC-02 | Tous | REC-04 |
| ENF-TRA-04 | E-06, E-11 | REC-14 |

### 13.3 Glossaire

| Terme | Définition |
|---|---|
| **Article** | Référence du catalogue achetable, sans existence physique propre |
| **Bon de commande (BC)** | Document d'engagement vers un fournisseur |
| **Bordereau de livraison (BL)** | Document constatant une réception physique |
| **Reliquat** | Quantité commandée non encore livrée |
| **Assistant d'intégration** | Séquence guidée de saisie d'inventaire préalable à la création des fiches du parc |
| **Unité** | Exemplaire physique individuel d'un article livré |
| **Code inventaire** | Identifiant unique d'un équipement dans le patrimoine de l'établissement |
| **Intégration** | Création automatique des enregistrements ParcInfo et Stock à la validation d'une réception |
| **Référentiel faisant foi** | Système désigné comme source unique de vérité pour une donnée |

### 13.4 Documents de référence

| Document | Objet |
|---|---|
| `SFD_Achat.md` | Spécifications fonctionnelles détaillées — description exhaustive des écrans, modales et actions |
| `PATTERNS.md` | Conventions de développement de l'application |
| `DESIGN.md` | Charte graphique et règles d'ergonomie |
| `opencode-specifications-fonctionnelles-module-achat.md` | Spécifications métier antérieures — acteurs, RACI, workflows |
| `opencode-specifications-fonctionnelles-achat.md` | Audit technique antérieur |

### 13.5 Conventions de numérotation

| Préfixe | Objet |
|---|---|
| `OBJ-nn` | Objectif opérationnel |
| `EXI-XXX-nn` | Exigence transverse (organisation, intégration, processus) |
| `EF-XXX-nn` | Exigence fonctionnelle par domaine |
| `ENF-XXX-nn` | Exigence non fonctionnelle par nature |
| `RGC-nn` | Règle de gestion contractuelle |
| `REC-nn` | Scénario de recette |
| `LIV-nn` | Livrable |
| `Ln-n` | Action de lot |

---

*Fin du document — Cahier des Charges Fonctionnel du module Achat, version 1.0.*
