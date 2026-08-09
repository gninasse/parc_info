# Raccordement Achat ↔ Stock — Spécification du prérequis PRQ-05

> **Date** : 05/08/2026 · **Statut** : proposition à valider (amende le SFD Stock v3.0 et précise le CDC Achat v2.0)
> **Base** : écrans réels du module Stock (« Nouveau bon d'entrée », stepper 3 étapes, modale « Sélectionner un article », tampon n° de série + état) · décisions A1/A2/A10 · UX-12/UX-13.
> **Principe directeur** : le magasinier garde **son** écran et **ses** gestes. Le raccordement ajoute un mode, pas un écran.

---

## 1. Constats sur l'implémentation Stock actuelle (captures du 05/08/2026)

| Constat | Conséquence pour le raccordement |
|---|---|
| Le triptyque est un **stepper sur une page** : ① Quantités → ② N° de série → ③ Validation | Le mode « sur commande » s'insère à l'étape ① uniquement ; les étapes ② et ③ sont **inchangées** |
| Le tampon de référencement porte **n° de série + état** par unité | ⚠️ Écart avec D10 (« état Neuf » figé) — le SFD Stock v3.0 §6.2 est à mettre à jour : `stock_tampon_equipements.etat` existe. Le raccordement n'y touche pas |
| `Référence externe` est un texte libre « N° de BL ou de commande » | C'est l'ancre D11 : elle devient le point d'insertion du lien structurel |
| La sélection d'article passe par une **modale** (filtre nature, recherche, prix indicatif, lien D7) | Convention d'interface du projet : la sélection de commande sera une **modale du même gabarit** ; le module Achat réutilisera cette modale article pour la saisie des BC |
| Pied de page : compteurs `X article(s) · Y équipement(s) · Z rattachement(s) · total FCFA` | Le mode commande ajoute au pied : `sur BC-2026-0041 · reste après ce bon : …` |

---

## 2. Étape ① « Quantités » — le mode « Livraison sur commande »

### 2.1 Déclenchement

Sous `Nature = Livraison`, le champ **Référence externe** est complété par un bouton adjacent : **« Lier à une commande… »** qui ouvre la modale de sélection (§2.2). Alternativement, le magasinier **scanne le QR code** imprimé sur le PDF du BC (UX-13) directement dans le champ : la liaison s'établit comme par la modale.

Le bon reste utilisable **sans** commande (retours, régularisations, achats hors module) : le mode libre actuel est conservé tel quel.

### 2.2 Modale « Sélectionner une commande »

Même gabarit que « Sélectionner un article » :

- Barre de filtres : fournisseur (Select) · recherche « N° de BC ou article… » ;
- Liste (radio) : **N° BC** · Fournisseur · Date de validation · Lignes restantes · Montant TTC · pilule de statut (`Validé` / `Partiel`) — seuls les BC en statut `validé` ou `partiel` apparaissent (source : `GET /achat/api/bons-commande/a-livrer`, EF-API-04) ;
- Pied de modale : ℹ️ *« Le bon d'entrée sera pré-rempli du reste à livrer ; les quantités reçues restent modifiables à la baisse. »* · `Annuler` / `Choisir`.

### 2.3 Effets de la liaison sur l'en-tête et les lignes

| Zone | Sans commande (actuel) | Avec commande liée |
|---|---|---|
| Bandeau | — | Encart bleu : `Livraison sur BC-2026-0041 — Sonabel-Info · [Voir le BC] · [Délier]` (Délier = confirmation, lignes conservées mais re-libellées libres) |
| Fournisseur | Libre (Catalogue) | **Imposé par le BC**, champ verrouillé 🔒 (infobulle « Défini par la commande ») |
| Référence externe | Texte libre | Conserve le n° de **BL papier** du livreur (le n° de commande est porté par la liaison, plus par ce champ) |
| Lignes du bon | Ajout manuel via modale article | **Pré-remplies du reste à livrer** de chaque ligne du BC (article, nature, quantité = reste, coût unitaire = **prix figé de la ligne**, non le prix indicatif — A10). Le magasinier ajuste les quantités **à la baisse** (livraison partielle) ou supprime une ligne non livrée |
| Plafond | — | Quantité saisie ≤ reste à livrer, contrôle champ par champ (message : « Reste à livrer : 4 ») |
| Ajouter un article | Libre | Autorisé uniquement parmi les lignes du BC non encore présentes ; un article hors BC ⇒ message « Cet article n'est pas sur la commande — créez un bon d'entrée séparé » (pas de mélange commande / hors commande sur un même bon) |
| Coût unitaire | Saisi (pré-rempli prix indicatif, alerte ±20 %) | Pré-rempli du **prix figé** de la ligne BC, modifiable (écart réel constaté), alerte ±20 % calculée **contre le prix figé** |
| Pied de page | Compteurs actuels | + `sur BC-2026-0041 · reste global après ce bon : 4 unités` |

### 2.4 Étapes ② et ③ : inchangées

Le wizard n° de série + état (tampon) et l'écran de validation ne changent pas d'un pixel. Seule la transaction de validation s'enrichit (§3).

---

## 3. Validation : la transaction étendue et la notification

Ordre transactionnel (extension du §7.1 du SFD Stock) :

1. Re-contrôles Stock actuels (tampon complet, unicité, articles actifs) ;
2. **Re-contrôle Achat sous verrou** : pour chaque ligne liée, quantité ≤ reste à livrer *au moment de la validation* (protège de deux bons d'entrée concurrents sur le même BC) — échec ⇒ 422 ligne à ligne, rien n'est écrit ;
3. Effets Stock actuels : mouvements, niveaux, **sérialisation** (fiches ParcInfo : n° série + état du tampon, coût = coût unitaire de la ligne, donc prix figé par défaut) ;
4. **Notification Achat** (même transaction, appel de service interne — pas d'événement asynchrone, conformément à ENF-TEC-04) : incrément des `quantite_livree`, recalcul du statut BC (`partiel`/`livré`), entrée de chronologie « Réception ENT-2026-0034 (6/10) intégrée » ;
5. Numéro définitif, PDF, purge du tampon.

**Idempotence** : la notification porte l'`entree_id` ; un rejeu ne produit aucun double incrément (EF-REC-04). **Contre-mouvement** : s'il porte sur un mouvement d'une entrée liée, décrément symétrique + événement de chronologie (EF-API-05).

---

## 4. Ce que la fiche BC affiche en retour (côté Achat — UX-12)

Onglet **Réceptions** :

- **Intégrées** (fait foi) : ENT-2026-0034 · date · magasin · lignes/quantités · observation typée · lien ;
- **En cours côté magasin** (informatif, badge « non intégré ») : « Brouillon #58 — 10 unités en référencement (7/10 saisies) » — lecture API Stock des bons liés non validés.

### 4.1 Le dossier documentaire de la livraison *(BR-03/BR-04)*

L'onglet ne se contente plus de compter : il **restitue les pièces**, pour que l'acheteur qui conteste une facture n'ait plus à téléphoner au magasin.

Sur chaque carte **Intégrée** :

- **« Bordereau de réception »** — le PDF de BR-02, ouvert dans la modale d'impression (jamais un onglet). Il est REGÉNÉRÉ à la demande, donc toujours à jour des contre-passations et de leur filigrane ;
- **les pièces jointes typées** — « Bordereau du fournisseur », « Photo de la livraison »… avec leur taille. Une pièce supprimée reste affichée barrée (pierre tombale) : une pièce retirée d'un dossier engagé doit rester visible ;
- **la pilule rouge « Écart BL »** et son détail au dépliage : annoncé au BL, compté reçu, écart signé, motif — assortie du rappel que rien de tout cela ne modifie un compteur.

Sur les cartes **En cours côté magasin** : l'indicateur **« BL joint / BL non joint »**, purement informatif. Une photo de livraison ne vaut pas BL — c'est précisément ce que le typage de BR-01 permet de distinguer.

Dans la **chronologie** : le dépôt du BL au magasin (« Bordereau du fournisseur joint au magasin sur ENT-2026-0034 ») et l'écart déclaré sur la réception intégrée.

Deux règles encadrent tout cela :

- **aucune URL Stock n'est rendue** — tous les liens passent par les routes proxy d'Achat, qui vérifient les permissions d'Achat puis relaient le fichier. Un profil Achat sans aucun droit Stock lit les bordereaux de SES commandes ; le rattachement est revérifié à chaque appel (IA-16) ;
- **dégradation partielle** — Stock absent, la fiche vit et l'onglet perd ses pièces. Jamais de page en erreur sur un bon parce que le magasin est en maintenance.

---

## 5. Contrats d'API (extrait pour `API_Inter_Modules.md`)

| Endpoint | Sens | Contenu |
|---|---|---|
| `GET /achat/api/bons-commande/a-livrer?fournisseur_id=&q=` | Stock → Achat | BC `validé`/`partiel`, lignes restantes, montants (modale §2.2) |
| `GET /achat/api/bons-commande/{id}/lignes-a-livrer` | Stock → Achat | Par ligne : article, nature, reste, **prix figé**, TVA figée (pré-remplissage §2.3) |
| `POST /achat/api/receptions` *(service interne transactionnel)* | Stock → Achat | `entree_id`, lignes/quantités → incréments + statut + chronologie ; idempotent |
| `POST /achat/api/receptions/contre` | Stock → Achat | `mouvement_id` d'origine → décréments |
| `GET /stock/api/entrees/liees?bon_commande_id=` | Achat → Stock | Bons liés (tous statuts) pour l'onglet Réceptions |
| `GET /achat/bons-commande/{id}/receptions/{entree}/bordereau` *(BR-03, proxy)* | interface Achat | Le PDF de BR-02, sous permissions **d'Achat** |
| `GET /achat/bons-commande/{id}/receptions/{entree}/documents/{doc}` *(BR-03, proxy)* | interface Achat | Une pièce de Stock relayée en flux ; 404 hors rattachement, 410 pierre tombale |

Permissions : `achat.api.view` aux rôles Stock ; `stock.api.view` aux rôles Achat. Les deux routes proxy exigent `achat.bons_commande.index` **et** `achat.documents.view`, et **aucune** permission Stock (voir IA-16, `API_Inter_Modules.md` §6).

> Les lectures marquées « API » côté Stock (`entrees/liees`, écarts BL) sont implémentées en **lecture directe de la base** par Achat, sans aller-retour HTTP : la fiche d'un bon ne doit pas dépendre du chargement d'un module voisin pour afficher ses données. La forme des données reste le contrat.

---

## 6. Répercussions documentaires

| Document | Amendement |
|---|---|
| SFD Stock v3.0 | §3.4 (mode commande, modale, QR, Délier) · §6.2 (`stock_entrees.bon_commande_id` FK nullable `restrict` ; **consigner l'`etat` du tampon**, écart D10 constaté) · §7.1 (étapes 2 et 4 de la transaction) · `TESTS_Stock.md` (I18 : plafond concurrent ; I19 : idempotence notification) |
| CDC Achat v2.0 | PRQ-05 : renvoyer vers ce document · UX-04 (brainstorming) : la sélection d'article des BC réutilise la **modale** « Sélectionner un article » (enrichie : prix indicatif, fournisseur préféré, TVA) — le Select2 inline est abandonné au profit de la convention modale du projet |
| Gabarit PDF BC (LIV-04) | QR code du numéro (UX-13) |

## 7. Recette du raccordement (complète REC-06→09)

1. Lier par modale, lier par scan QR, délier ; 2. Pré-remplissage et plafonds (baisse OK, hausse 422) ; 3. Deux bons concurrents sur le même reste : le second échoue à la validation, message ligne à ligne ; 4. Livraison partielle → BC `partiel`, solde → `livré` ; 5. Rejeu de notification → aucun double ; 6. Contre-mouvement → décrément + chronologie ; 7. Article hors BC refusé ; 8. Fiche BC : sections Intégrées / En cours exactes ; 9. Fiches ParcInfo : coût = prix figé, état = état du tampon.

**Ajouts du lot BR** : 10. Dépôt du BL au comptoir depuis un mobile (photo), typé « Bordereau du fournisseur » — puis paramètre `bl_obligatoire_si_commande` activé : la validation d'une entrée liée sans BL est refusée en 422 avec un message qui nomme la pièce ; 11. Bordereau de réception signé par le livreur avec des écarts déclarés : le bloc « Écarts constatés » figure sur le PDF, et les compteurs d'Achat ignorent les quantités annoncées ; 12. Consultation croisée : un compte **Achat pur, sans aucun rôle Stock**, ouvre la fiche de sa commande, imprime le bordereau et télécharge le BL ; le même compte reçoit 404 sur une pièce d'une autre commande, et 403 sur les routes Stock.

---

*À valider par la MOA puis à intégrer : amendement SFD Stock, `API_Inter_Modules.md`, maquettes (encart de liaison, modale commande).*
