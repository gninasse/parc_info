# Cadrage — Portail de demande d'achat (v2)

> **Statut : étude, aucun code.** Livrable du prompt D-25. Ce document instruit le circuit de demande **sans l'implémenter** : il pose le périmètre, nomme les acteurs, et surtout **liste les décisions à trancher (Z1 → Z12)** sur le modèle des A1-A16 du `SFD_Achat.md` §1.6. Aucune décision n'est prise ici : elles sont préparées, chiffrées quand c'est possible, et assorties d'une recommandation argumentée.
>
> **Documents liés** : `SFD_Achat.md` (A13, §1.5 acteurs, §6.2 modèle) · `CDC_Achat_v2.md` (§3.3 périmètre exclu, ENF-SEC-04) · `CDC_Achat.md` (L5-1, L5-2) · `SPEC_UX_Achat.md` (§15 textes gelés) · `GUIDE_Utilisateur_Achat.md`.
> **Date** : 09/08/2026. **Demandeur** : Dr Zongo.

---

## 1. Le problème, tel qu'il se pose aujourd'hui

Le module Achat couvre la **chaîne d'engagement** : brouillon → soumission → visa → validation numérotée → réception → reliquat soldé. Il commence donc **au moment où l'acheteur saisit**. Ce qui se passe avant, l'expression du besoin par un service, reste **hors du système** : un service demande par téléphone, par courriel ou sur un imprimé papier, et l'acheteur ressaisit.

Trois conséquences se constatent sans instrumentation particulière :

1. **Le besoin n'est pas traçable.** Un service qui affirme avoir demandé du matériel en mars n'a rien à produire, et l'acheteur non plus. Le désaccord ne se tranche pas.
2. **L'arbitrage est invisible.** Quand plusieurs services demandent plus que le budget, quelqu'un choisit ; ce choix n'est écrit nulle part, il ne peut donc être ni expliqué ni contesté.
3. **Le demandeur ne sait pas où en est sa demande** et relance par téléphone, ce qui consomme le temps de l'acheteur — c'est exactement le mal que D-22 vient de traiter *à l'intérieur* du circuit, et qui subsiste *en amont*.

### Ce qui a déjà été préparé (A13), et ce qu'il faut en dire

La décision **A13** a doté le bon de commande d'un `service_demandeur_id` (FK Organisation, `set null`, avec libellé dénormalisé) et d'une `reference_demande` (texte libre, la référence de l'imprimé papier). Les deux sont imprimés sur le bon et filtrables dans la liste.

**Constat de terrain à verser au dossier** : sur les 56 bons de la base de développement, **ces deux champs sont renseignés 0 fois**. Il faut se garder d'en tirer une conclusion hâtive — la base de développement n'est pas la production, et les bons y ont été saisis pour tester la chaîne, pas pour refléter l'activité. Mais cela suffit à établir un point : **A13 ne fournira pas d'historique exploitable** au démarrage du portail. Le circuit de demande ne pourra pas s'amorcer sur des données existantes, et toute reprise devra être considérée comme un chantier à part entière (cf. **Z11**).

---

## 2. Périmètre proposé

### 2.1 Inclus

| # | Objet |
|---|---|
| P1 | **Saisie d'une demande** par un agent d'un service : articles souhaités (catalogue), quantités, justification, degré d'urgence |
| P2 | **Circuit de la demande** : brouillon → soumise → arbitrée (retenue / ajournée / refusée, **toujours motivée**) |
| P3 | **Lien demande → bon de commande** : un BC né d'une ou plusieurs demandes, et la demande sait quel BC la sert |
| P4 | **Suivi par le demandeur** : où en est ma demande, et quand la livraison est-elle arrivée |
| P5 | **Restitution** : demandes en attente d'arbitrage, délai moyen d'arbitrage, taux de satisfaction par service |

### 2.2 Exclu, et pourquoi

| Exclusion | Raison |
|---|---|
| Budget par service, enveloppes, consommation budgétaire | C'est un module en soi. L'arbitrage v2 se fait **à dire d'expert**, pas contre une enveloppe calculée. Confondre les deux ferait glisser le chantier vers une comptabilité budgétaire. |
| Circuit d'approbation multi-niveaux par seuil de montant (L5-2) | Distinct de la demande. À traiter séparément, sous peine de mêler deux questions dont l'une bloquerait l'autre. |
| Workflow de validation hiérarchique du demandeur (chef de service → direction) | Voir **Z4** : à trancher, et probablement à repousser. |
| Retours et litiges fournisseurs (L5-4) | Sans rapport. |

---

## 3. Acteurs

| Acteur | Ce qu'il fait | Ce qu'il ne doit PAS voir |
|---|---|---|
| **Demandeur** (agent d'un service) | Saisit, soumet, suit **ses** demandes et celles de son service | **Les prix, les montants, les dépenses globales, les autres services.** Voir §5. |
| **Arbitre** (responsable des achats, ou Dr Zongo) | Voit toutes les demandes, arbitre, motive, engage vers un BC | — |
| **Acheteur** | Transforme une demande retenue en bon de commande | Inchangé par rapport à v1 |
| **Consultation / contrôle de gestion** | Lit les demandes et les indicateurs d'arbitrage | — |

Le portail introduit donc **un acteur qui n'existe pas aujourd'hui dans Achat** : le demandeur. C'est le fait structurant du lot, et la source de la principale difficulté (§5).

---

## 4. Impacts sur l'existant

### 4.1 Sur le modèle

Deux tables nouvelles (`achat_demandes`, `achat_lignes_demande`) et **un lien** vers le bon de commande. Le sens de ce lien est à trancher (**Z2**), car il détermine si une demande peut être servie par plusieurs bons et si un bon peut servir plusieurs demandes.

A13 devient alors **redondant** en tant que saisie manuelle : le service demandeur se déduira du lien. Il ne faut pourtant **pas supprimer** `service_demandeur_id` : les bons directs (sans demande préalable, cf. **Z3**) continueront d'en avoir besoin, et les bons déjà validés ne se réécrivent pas. La colonne reste, sa saisie devient facultative **et** automatique quand une demande est à l'origine du bon.

### 4.2 Sur les statuts et le visa (question posée par le prompt)

**Le circuit de la demande et le circuit du bon sont deux circuits distincts et ne doivent pas être fusionnés.** Une demande arbitrée n'est pas un bon soumis : entre les deux, l'acheteur choisit un fournisseur, négocie un prix, groupe plusieurs demandes. Fusionner les deux ferait porter au statut du bon une information qui n'est pas la sienne.

Conséquence directe sur la question du prompt : **D3 (pas de double visa en v1) n'a pas à être rouvert par ce lot**. L'arbitrage de la demande n'est pas un visa du bon ; il porte sur l'opportunité du besoin, pas sur l'engagement financier. Les deux questions sont indépendantes et doivent le rester — les mêler donnerait un circuit à quatre étapes de validation dont personne ne saurait dire laquelle engage l'établissement. Voir **Z5**.

### 4.3 Sur les notifications (D-22)

Le dispositif livré est **directement réutilisable** : quatre types deviennent six ou sept. Le mécanisme (destinataires déduits des permissions, préférences personnelles, déclenchement hors transaction) n'est pas à refaire. Nouveaux types pressentis : « votre demande a été arbitrée » (avec le motif, comme le renvoi), « une demande attend un arbitrage », et le rattachement de la notification de réception au **demandeur** en plus de l'auteur du bon (**Z8**).

### 4.4 Sur la volumétrie

39 services et 33 unités existent au référentiel Organisation. Si un dixième d'entre eux demande deux fois par mois, l'ordre de grandeur est **~100 demandes par an**, à comparer aux bons de commande. Ce n'est pas un problème technique ; c'est en revanche **une charge d'arbitrage** dont il faut vérifier qu'elle a un titulaire (**Z6**).

---

## 5. Le point dur : le demandeur ne doit pas voir les prix

C'est la difficulté principale du lot, et elle est de nature **architecturale**, pas cosmétique.

`CDC_Achat_v2.md` acte pour ENF-SEC-04 qu'il n'y a **aucune restriction par périmètre de données en v1** : tout utilisateur habilité voit tous les bons. Ce choix était assumé et documenté, avec la mention qu'il serait réévalué **si le périmètre organisationnel s'élargissait**. Le portail de demande est précisément cet élargissement : il fait entrer dans l'application des agents de service qui n'ont rien à connaître des montants engagés par l'établissement.

Trois conséquences à ne pas sous-estimer :

1. **Un filtrage d'affichage ne suffit pas.** Masquer une colonne dans une vue laisse l'information accessible par les endpoints `.data`, les exports et les PDF. La règle du projet (permissions serveur partout) impose que la restriction soit **au niveau des données servies**, pas du gabarit.
2. **Le périmètre « son service » n'existe pas encore.** Vérification faite : `users.service` est une **chaîne de caractères libre**, sans clé étrangère vers `organisation_services`, et elle est **vide pour les deux comptes existants**. Il n'y a donc aujourd'hui **aucun moyen fiable de savoir de quel service relève un utilisateur**. C'est un prérequis à part entière, dans le module **Core/Organisation**, et non un détail du portail (**Z1**).
3. **Le catalogue porte des prix.** Le sélecteur d'articles affiche le prix indicatif, et le module Catalogue expose un journal des prix. Un demandeur qui choisit un article verrait donc un prix par ce chemin, même si l'écran de demande n'en affiche aucun (**Z7**).

**Recommandation** : traiter ce point **avant** toute écriture d'écran. Un portail livré puis rétro-sécurisé laisserait, le temps de la correction, des montants d'engagement visibles par des dizaines d'agents.

---

## 6. Décisions à trancher (Z1 → Z12)

| # | Décision | Enjeu | Recommandation |
|---|---|---|---|
| **Z1** | **Comment rattache-t-on un utilisateur à un service ?** FK `users.service_id → organisation_services`, reprise des valeurs texte existantes, et qui l'administre. | **Bloquant.** Sans lui, « voir les demandes de mon service » n'est pas calculable, donc ni le portail ni sa sécurité ne tiennent. | À traiter comme un **prérequis Core/Organisation**, chiffré et livré avant le portail. Ne pas le glisser dans le lot Achat : ce n'est pas un champ, c'est un référentiel de rattachement. |
| **Z2** | **Cardinalité demande ↔ bon de commande.** 1↔1, 1 demande → N bons, ou N demandes → 1 bon ? | Structure la table de liaison et toute la restitution. | **N↔N** via table de liaison. C'est la réalité : on groupe plusieurs demandes chez un fournisseur, et une demande peut être servie en deux fois. Choisir 1↔1 par simplicité obligerait à tout reprendre au premier groupage. |
| **Z3** | **Un bon peut-il naître sans demande ?** | Si non, tout achat urgent est bloqué par le circuit. | **Oui**, sans discussion. Le bon direct reste la règle pour l'urgence et le renouvellement ; la demande est une **porte d'entrée supplémentaire**, pas un péage. |
| **Z4** | **Le chef de service valide-t-il la demande avant qu'elle parte à l'arbitrage ?** | Ajoute une étape et un acteur ; multiplie les comptes à créer. | **Non en v2.** La demande est nominative et journalisée, la traçabilité fait le contrôle (même raisonnement que D3). À rouvrir si l'usage montre des demandes non concertées. |
| **Z5** | **L'arbitrage remplace-t-il ou complète-t-il le visa du bon ?** | Risque de circuit à double validation illisible. | **Il le complète, et reste distinct** (§4.2). D3 n'est pas rouvert par ce lot. |
| **Z6** | **Qui arbitre, nommément, et sous quel délai ?** | Une file sans titulaire ni délai devient un cimetière de demandes, pire que le téléphone. | À nommer **avant** de coder, avec un délai cible affiché au demandeur. Sinon, ne pas livrer le portail. |
| **Z7** | **Le demandeur voit-il les prix indicatifs du catalogue ?** | Contradiction possible avec §5. | **Non.** Prévoir un mode « sélection sans prix » du sélecteur d'articles, imposé côté serveur. Le demandeur exprime un **besoin**, pas un budget. |
| **Z8** | **Le demandeur est-il notifié de la réception ?** | C'est la principale attente exprimée (« où en est ma demande »). | **Oui**, en réutilisant D-22 : c'est peu coûteux et cela supprime les relances téléphoniques. |
| **Z9** | **Une demande refusée peut-elle être resoumise ?** | Une demande morte sans recours pousse à contourner le circuit. | **Oui**, par duplication, en conservant la trace du refus et de son motif. Jamais par réouverture du même objet : un refus motivé ne se réécrit pas. |
| **Z10** | **Le demandeur choisit-il dans le catalogue, ou décrit-il en texte libre ?** | Le texte libre est confortable pour lui mais impose une requalification à chaque demande. | **Catalogue d'abord**, avec **un champ « autre besoin » en texte libre** conservé : refuser tout texte libre ferait sortir du système les besoins que le catalogue ne couvre pas encore, ce qui est exactement ce qu'on cherche à éviter. |
| **Z11** | **Reprend-on l'historique papier ?** | Charge de saisie, qualité incertaine. | **Non.** Le portail démarre à blanc, à une date annoncée. A13 ne fournit aucun historique exploitable (§1), et ressaisir du papier ancien coûterait plus que ce qu'il apporterait. |
| **Z12** | **Le portail est-il un écran du module Achat ou un module distinct ?** | Un demandeur n'a rien à faire dans la navigation Achat (bons, reliquats, rapports). | **Écrans dédiés dans Achat**, avec une **entrée de navigation propre** et un layout allégé. Créer un module séparé dupliquerait le modèle ; laisser le demandeur dans la navigation Achat l'exposerait à des écrans qui ne le concernent pas. |

---

## 7. Écrans candidats (esquisse, non normative)

| Réf. | Écran | Pour qui |
|---|---|---|
| **A-09** | Mes demandes (liste, statuts, suivi) | Demandeur |
| **A-10** | Saisie d'une demande (articles **sans prix**, quantité, justification, urgence) | Demandeur |
| **A-11** | File d'arbitrage (toutes les demandes soumises, tri par urgence et ancienneté, arbitrage motivé) | Arbitre |
| **A-12** | Engager en commande (sélection de demandes retenues → brouillon de BC pré-rempli) | Acheteur |

A-12 est le point de jonction avec l'existant : le mécanisme est **déjà écrit** (D-21 crée un brouillon de BC depuis des articles en alerte de seuil, avec refus explicite si le fournisseur ne peut être déduit). Il est réutilisable presque tel quel.

---

## 8. Re-répartition des exigences W du CDC

| Réf. | Exigence | Devient |
|---|---|---|
| **L5-1** | Demande d'achat et expression de besoin en amont du BC | **Objet du présent cadrage.** Passe de W à **S**, sous réserve de Z1 (prérequis bloquant) et Z6 (titulaire de l'arbitrage). |
| **L5-2** | Circuit d'approbation multi-niveaux par seuil de montant | **Reste W**, et reste **distinct** (Z5). |
| **L5-4** | Retours et litiges fournisseurs | Reste W, hors sujet. |

`CDC_Achat_v2.md` §3.3 exclut « demande d'achat amont, circuit d'approbation multi-niveaux » d'une même ligne. **Proposition d'amendement** : scinder cette ligne en deux, la demande amont passant en lot ultérieur *cadré*, le circuit multi-niveaux restant exclu. Les deux ont été traités comme un bloc, alors qu'ils ne posent ni la même question ni le même risque.

---

## 9. Ce qu'il faut vérifier avant d'engager le lot

1. **Z1 est-il financé ?** Sans le rattachement utilisateur → service, le lot ne peut pas démarrer. C'est la première question à poser, avant toute estimation.
2. **Z6 a-t-il un titulaire ?** Un portail qui alimente une file que personne ne traite dégrade la situation actuelle au lieu de l'améliorer : le demandeur aura en plus le sentiment d'avoir été enregistré puis ignoré.
3. **Le cloisonnement des montants (§5) est-il accepté comme un préalable**, et non comme une finition ?

Tant que ces trois points ne sont pas tranchés, **aucune ligne de code ne doit être écrite** : ce sont eux, et non la difficulté technique, qui décideront du succès du portail.

---

*Cadrage D-25 — CHU-YO, 09/08/2026. Étude préalable, aucune implémentation. Les décisions Z1 à Z12 sont à arbitrer avant l'ouverture du lot ; elles rejoindront alors le `SFD_Achat.md` §1.6 au même titre que les A1-A16.*
