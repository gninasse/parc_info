# Module Achat — Analyse de fin de chantier

> **Date** : 09/08/2026 — **Branche** : `refactor/stock-rebuild`
> **Périmètre** : `Modules/Achat/`, le JS associé dans `public/js/modules/achat/`, et les points de contact avec `Modules/Stock` (raccordement PRQ-05, lot BR).
> **Nature** : bilan de construction. Il décrit ce qui **est**, y compris ce qui a été écarté et pourquoi — un document qui ne dirait que les réussites ne servirait à personne pour la suite.

---

## 1. Vue d'ensemble

Le module **Achat** porte la **chaîne d'engagement fournisseur** du CHU-YO : du bon de commande à la réception physique, en passant par le visa et jusqu'au reliquat soldé. Il a été reconstruit intégralement — les modules Achat et Stock v1 avaient été supprimés le 27/07/2026, laissant l'établissement sans traçabilité de ses acquisitions.

Sa position dans la chaîne des modules :

```
Catalogue ──(articles, prix indicatifs, fournisseurs)──► ACHAT
                                                           │
                                        (bons à livrer)    │  (réceptions)
                                                           ▼
                                                         STOCK ──► ParcInfo
```

**Il est la source de vérité du reste à livrer.** Cette phrase gouverne toute l'architecture : le Stock lui *notifie* les réceptions, il ne les lui *impose* pas ; et aucun compteur d'Achat n'est déduit d'une lecture du Stock.

### Chiffres

| | |
|---|---|
| Modèles | 9 |
| Contrôleurs | 11 |
| Services | 23 |
| Migrations | 10 |
| Vues Blade | 21 |
| Modules JavaScript | 13 |
| Code applicatif | ~14 650 lignes PHP |
| Tests | 498 méthodes, ~11 460 lignes — **un ratio de 0,78 ligne de test par ligne de code** |

Ce ratio n'est pas une coquetterie. Le module manipule des montants engagés vis-à-vis de tiers : une erreur ne se rattrape pas par un correctif, elle se rattrape par une négociation commerciale.

---

## 2. Les décisions structurantes

### 2.1 Les valeurs sont figées à la ligne, pas référencées

Un bon de commande recopie la désignation, le prix et le taux de TVA au moment où la ligne est créée. Le catalogue peut ensuite changer : le bon dit la même chose dans deux ans qu'aujourd'hui.

C'est contre-intuitif pour un développeur (« pourquoi dupliquer une donnée qu'on peut joindre ? »), et c'est pourtant le cœur du sujet : un engagement contractuel n'est pas une vue sur un référentiel mouvant.

### 2.2 Le numéro s'attribue à la validation, sous verrou

Un brouillon n'a pas de numéro (il s'affiche « Brouillon #58 »). Le numéro définitif est attribué au visa, dans une transaction verrouillée, ce qui garantit une séquence **sans trou ni collision** même si deux validations partent en même temps.

Un brouillon supprimé ne consomme donc aucun numéro — et un trou dans une séquence de bons de commande est exactement ce qu'un contrôleur remarque.

### 2.3 L'intégration des réceptions est un service interne, pas une API HTTP

`AchatReceptionService::integrer()` est appelé **dans la transaction de validation du bon d'entrée Stock**. Son échec fait échouer la validation Stock : ni mouvement de stock, ni incrément de livraison.

C'est le choix le plus important du raccordement. Une API HTTP aurait créé une fenêtre où le stock physique aurait bougé sans que la commande le sache — l'écart aurait été découvert des semaines plus tard, sans moyen de savoir laquelle des deux vérités croire.

Trois protections dans ce service : le bon doit être livrable ; le plafond est revérifié **sous verrou** au moment de l'intégration (et non à la saisie) ; l'idempotence par `entree_id` permet de rejouer une notification sans double incrément.

### 2.4 La chronologie EST le journal

Aucun événement de la chronologie n'est reconstruit depuis les colonnes du bon : chaque ligne affichée est une entrée d'`activity_log`. Si le journal et la fiche divergeaient, on ne saurait plus lequel croire — et c'est le journal qui a valeur de preuve.

### 2.5 Rien ne s'efface

Une pièce justificative retirée d'un bon engagé laisse une **pierre tombale** : fichier effacé, ligne conservée, motivée et signée, téléchargement en 410. Un bon validé s'annule (avec motif) mais ne se supprime pas. Une réception s'annule par contre-passation, jamais par retrait.

### 2.6 Les signaux signalent, ils n'accusent pas

Les neuf indicateurs de vigilance (écarts de prix, fournisseurs récents, bons rapprochés, auto-validations, écarts de livraison…) ne bloquent **rien**. Un écart de prix peut être justifié, une auto-validation peut être la seule option un jour de congés.

Ils donnent à un responsable de quoi **poser une question**, ce qui est tout autre chose qu'un contrôle automatique — lequel serait contourné dans le mois.

---

## 3. Le lot BR — les bordereaux de réception

Extension du chantier initial, livrée en cinq étapes. Le problème traité : l'acheteur qui conteste une facture avait besoin du BL signé, mais devait pour cela obtenir des droits sur le module Stock. Dans les faits, il téléphonait au magasin.

| Prompt | Apport |
|---|---|
| BR-01 | Pièces jointes **typées** (le BL du livreur ne se confond plus avec une photo de colis), pierre tombale, garde `bl_obligatoire_si_commande` |
| BR-02 | Le **bordereau de réception** PDF, signable par le magasinier et le livreur, sans montants |
| BR-03 | Le **proxy documentaire** : Achat sert les pièces de ses propres commandes |
| BR-04 | Les **écarts BL** structurés et le 9e signal |
| BR-05 | L'invariant IA-16, la matrice croisée, les documents |

### Le point sensible : IA-16

BR-03 ouvre une porte entre deux modules — Achat sert des fichiers qui appartiennent à Stock. C'est utile, et c'est le genre de commodité qui devient une faille quand personne ne la surveille.

Trois verrous, dont le **troisième** est celui qui compte : la pièce doit appartenir à une entrée liée à CE bon de commande. Sans lui, un identifiant deviné donnerait accès à tout le magasin, permission en poche. Réponse `404` et non `403` : pour l'acheteur, une pièce absente de son dossier n'existe pas.

### Le point délicat : la neutralité des écarts

Un écart déclaré (« le BL annonçait 10, j'en ai compté 8 ») **ne modifie aucun compteur**. Les reliquats ne connaissent que le compté.

Cette neutralité n'est pas une facilité d'implémentation, c'est la condition pour que l'écart soit déclaré : un magasinier qui craint de fausser les compteurs ne déclare rien, et l'information est perdue.

---

## 4. Ce qui a été trouvé en construisant

Ces défauts sont consignés au README du module. Ils valent d'être relus avant toute reprise :

| # | Le piège | Ce qu'il enseigne |
|---|---|---|
| 1 | SQLite perd les `CHECK` dès qu'une migration recrée une table | Une base peut perdre son filet de sécurité **sans la moindre erreur** |
| 2 | Blade échoue sur un accès de tableau dans une directive | Extraire en `@php` avant la directive |
| 3 | `taux_tva / 100` vaut 0 en SQL entier | Une colonne entière de TVA à zéro, sans erreur |
| 4 | `SUM` ajouté à une projection casse sur PostgreSQL, pas sur SQLite | Réussir sur un SGBD ne prouve rien pour l'autre |
| 5 | `addMonth()` depuis le 31 saute un mois | `addMonthNoOverflow()` |
| 6 | Une requête en erreur **avorte la transaction** PostgreSQL | Le `try/catch` attrape l'exception, mais la page est déjà perdue |
| 7 | Une trace `activity()` sans sujet ne porte pas le module | Elle disparaît des chronologies |
| 8 | Un test qui recopie l'appel d'une vue finit par mentir | Appeler le vrai contrôleur, jamais réimplémenter ce qu'on vérifie |
| 9 | jsdom diffère `ready` et n'implémente pas `requestSubmit` | Un harnais peut mesurer un écran non encore branché |

Le motif commun de la moitié d'entre eux : **une erreur qui ne produit aucun message**. C'est ce type de défaut que les audits outillés (D-19) cherchent désormais en une commande.

---

## 5. Ce que la vérification a coûté, et rapporté

| Niveau | Ce qu'il attrape |
|---|---|
| 498 tests serveur, bi-SGBD | Le comportement, les invariants, les permissions |
| 4 harnais navigateur (jsdom) | Ce qu'un test HTTP ne voit pas : ancre morte, montant non qualifié, bouton grisé sans diagnostic |
| 3 audits outillés | La route qu'on **oubliera** de protéger, le code mort, le texte qui a dérivé de la spec |
| Recette sur base réelle | Le rôle mal seedé, le paramètre absent — invisibles sur une base fabriquée |
| Inspection des PDF | Le bordereau qui s'annonçait « Laravel » au lieu de l'établissement |

Les deux derniers niveaux ont trouvé des défauts que les 498 tests laissaient passer. C'est l'argument le plus solide en faveur d'une recette exécutable sur données réelles, et il vaut pour les modules suivants.

---

## 6. Écarts avec la spécification

Treize écarts sont consignés au tableau du README. Les trois plus structurants :

1. **`API_Inter_Modules` §3.2** spécifie un endpoint HTTP `GET /stock/api/entrees/liees`. Il est implémenté en **lecture directe** de la base : la fiche d'un bon ne doit pas dépendre du chargement d'un module voisin, ni d'un aller-retour HTTP, pour afficher ses propres données. La forme des données reste le contrat.
2. **Le SFD annonçait 8 signaux**, il y en a 9 depuis BR-04.
3. **Le SFD Stock ne décrivait pas `stock_documents`** : la table existait sans spécification. Elle est désormais documentée.

Aucun de ces écarts n'a été masqué : la règle suivie tout au long du chantier a été de consigner plutôt que de faire coïncider silencieusement.

---

## 7. Limites assumées

- **Inventaires et journal des mouvements** (module Stock) : spécifiés au SFD §3.8/§3.9, non livrés. Sept permissions sont seedées et n'ouvrent rien ; l'audit les liste explicitement comme « en attente de leur écran ».
- **Accessibilité** : les attributs ARIA, l'étiquetage et la double information couleur+libellé sont vérifiés automatiquement. L'ordre de tabulation, le focus des dialogues et le geste douchette du wizard sont contrôlés **à la main** — les simuler sous jsdom donnerait une fausse assurance.
- **Performance** : aucune mesure de charge. Les listes sont paginées et indexées, mais le comportement à 100 000 bons n'est pas connu.
- **Notifications** (mail/SMS au visa, au renvoi, à la réception) : hors périmètre v1, au backlog.
- **Rapprochement facture ↔ bon** : hors périmètre. C'est la suite naturelle du module, et le lot BR l'a préparée en rendant les pièces de réception consultables depuis Achat.

---

## 8. Pour qui reprendra ce module

Trois lectures, dans cet ordre :

1. `Modules/Achat/README.md` — l'installation, les paramètres, et surtout le tableau des **pièges** ;
2. `TESTS_Achat.md` — ce qui est vérifié, comment, et ce qui ne l'est pas ;
3. `SFD_Achat.md` §9.4 — les seize invariants. Chacun a un test qui porte son numéro : si vous cassez l'un d'eux, un test nommé `iaXX` vous le dira.

Et une commande, avant tout commit :

```bash
php artisan test Modules/Achat && python3 scripts/audit_permissions.py
```

---

*Analyse de fin de chantier — module Achat, CHU-YO, 09/08/2026.*
