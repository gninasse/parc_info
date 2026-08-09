# Cadrage — Rapprochement bon de commande ↔ factures (3-way match)

> **Statut : étude, aucun code.** Livrable du prompt D-26. Objectif : préparer l'interface avec le système comptable sans l'implémenter, et **nommer les décisions à trancher (Y1 → Y10)**.
>
> **Documents liés** : `SFD_Achat.md` (§6 modèle, RGC-02) · `CDC_Achat_v2.md` (§3.3 : « calcul d'amortissement, facturation, mandatement, règlement » = **hors périmètre**, système comptable responsable) · `RACCORDEMENT_Achat_Stock.md` (IA-2 valeurs figées, IA-16 rattachement) · `API_Inter_Modules.md`.
> **Date** : 09/08/2026.

---

## 1. Ce que le module sait déjà, et ce qui lui manque

Le rapprochement à trois termes confronte **commandé**, **reçu** et **facturé**. Le module Achat détient aujourd'hui **deux des trois** de façon fiable, ce qui est le fait déterminant de ce cadrage.

| Terme | Détenu ? | Où, et avec quelle garantie |
|---|---|---|
| **Commandé** | **Oui** | `achat_lignes_commande` : quantité, prix unitaire HT, taux de TVA, désignation, **figés à la ligne** au moment de la validation (A4 / IA-2). Un article dont le prix change ensuite ne réécrit pas un bon validé — c'est précisément ce qui rend le rapprochement opposable. |
| **Reçu** | **Oui** | `quantite_livree` par ligne, alimentée par les intégrations de réception sous verrou et transaction (RGC-02), avec contre-passation possible. Les écarts BL sont déclarés séparément et **neutres pour les compteurs** : ils documentent le désaccord au quai sans le confondre avec la quantité acceptée. |
| **Facturé** | **Non** | **Aucune table, aucun champ, aucun import.** Vérification faite sur la base : aucune structure de facturation n'existe. C'est l'intégralité du chantier. |

Deux acquis récents servent directement ce lot :

- **l'imputation comptable** (D-24) : le compte est **figé à la ligne**, si bien que réaffecter un article ne réécrit pas un exercice clos. Un rapprochement doit hériter de cette propriété ;
- **le dossier documentaire** (lot BR) : BL fournisseur typé, bordereau de réception signable, proxy documentaire. L'acheteur qui conteste une facture peut déjà produire les pièces — le rapprochement lui dira **quoi** contester.

---

## 2. La question préalable, qui commande tout le reste

`CDC_Achat_v2.md` §3.3 place explicitement **facturation, mandatement et règlement hors périmètre**, en désignant le système comptable comme responsable, Achat se bornant à « fournir les données sources ».

Ce lot ne consiste donc **pas** à facturer dans Achat. Il consiste à **confronter** ce qu'Achat sait (commandé, reçu) à ce que la comptabilité sait (facturé), et à **nommer les écarts**. Toute dérive vers la saisie de factures, le suivi des règlements ou les échéanciers sortirait du périmètre contractuel et empiéterait sur un système existant.

**Conséquence pratique** : la valeur du lot dépend entièrement de la capacité à **obtenir les données de facturation**. Cette question est technique en apparence, organisationnelle en réalité (**Y1**), et **elle doit être tranchée en premier** : sans flux de factures, tout le reste est sans objet.

---

## 3. Point d'entrée : import, API, ou saisie ?

| Option | Ce qu'elle suppose | Appréciation |
|---|---|---|
| **A. Import de fichier** (CSV/XLSX déposé périodiquement) | Que la comptabilité puisse **exporter** ses factures | **Recommandé pour démarrer.** Ne demande aucun développement au système comptable, aucune ouverture réseau, et se met en place en quelques jours. Défaut assumé : la fraîcheur dépend de la périodicité du dépôt. |
| **B. API consommée** (Achat interroge la comptabilité) | Que le système comptable **expose** un service, avec authentification et disponibilité | Meilleure fraîcheur, mais dépend d'un tiers dont le calendrier n'est pas maîtrisé. Ne pas en faire un prérequis. |
| **C. Saisie manuelle dans Achat** | Que quelqu'un ressaisisse les factures | **À écarter.** Double saisie, donc double source de vérité et divergence garantie ; et cela ferait entrer la facture dans Achat, contre le §3.3 du CDC. |

**Recommandation** : concevoir le modèle pour que **la source soit interchangeable** (une table `achat_factures_importees` alimentée indifféremment par un import ou par une API), livrer l'import en premier, garder l'API comme évolution. Ce qui compte est que **l'origine de chaque ligne soit tracée** : sans elle, un écart ne peut être imputé ni au fichier, ni au rapprochement.

---

## 4. La clé de rapprochement, et pourquoi elle est fragile

Le rapprochement se fait « par numéro de BC ». Cette évidence mérite d'être instruite, car c'est là que ce genre de dispositif échoue.

Le numéro est attribué **à la validation** (`BC-2026-0042`), il est unique et il figure sur le PDF envoyé au fournisseur. C'est donc la bonne clé. Mais :

1. **le fournisseur ne le reporte pas toujours** sur sa facture, ou le déforme (espaces, tirets, préfixe omis, casse) ;
2. **une facture peut couvrir plusieurs bons**, et **un bon peut être facturé en plusieurs fois** lorsqu'il a été livré partiellement ;
3. **une facture peut ne correspondre à aucun bon** : achat hors circuit, ou bon de régularisation.

Il faut donc prévoir dès la conception : une **normalisation** de la référence avant comparaison (celle de la recherche « tous formats de numéros » de A-02 existe déjà et doit être réutilisée plutôt que réécrite), une relation **N↔N** entre factures et bons, et surtout **une file des factures non rapprochées**. Cette file est le vrai livrable du lot : les factures qui tombent juste ne demandent aucun travail, ce sont les autres qui coûtent de l'argent.

---

## 5. Les écarts : lesquels nommer, et lesquels tolérer

Un rapprochement utile ne dit pas « il y a un écart », il dit **lequel** et **de quel ordre**.

| Écart | Question qu'il pose | Traitement proposé |
|---|---|---|
| **Prix** (facturé ≠ commandé) | Le fournisseur a-t-il appliqué le prix négocié ? | Le plus important. Le prix commandé est **figé à la ligne**, la comparaison est donc opposable. Seuil de tolérance à définir (**Y4**). |
| **Quantité** (facturé > reçu) | Paie-t-on ce qui n'est pas arrivé ? | **Aucune tolérance.** Facturer plus que le reçu doit toujours ressortir, quel que soit le montant. |
| **Quantité** (facturé < reçu) | Reste-t-il une facture à venir ? | Signalé, non bloquant : c'est le cas normal d'une livraison partielle. |
| **TVA** | Le taux appliqué est-il celui de la commande ? | Comparaison du taux figé à la ligne. |
| **Facture sans bon** | Achat hors circuit ? | File dédiée. C'est un **signal de gouvernance**, pas une anomalie technique. |
| **Bon reçu jamais facturé** | Une dette est-elle latente ? | Symétrique du reliquat, et probablement l'indicateur le plus utile pour la clôture d'exercice. |

**Point de vigilance** : les seuils de tolérance ne doivent **jamais faire disparaître un écart**, seulement le classer. Un écart sous le seuil reste consultable ; il ne remonte pas en alerte. La différence est décisive : masquer un écart de 2 % sur cent factures revient à ne pas voir une dérive systématique.

---

## 6. Restitution

En réutilisant l'existant plutôt qu'en inventant :

- **une carte de rapport** dans A-07, aux côtés des dépenses par imputation (D-24), exportable comme les autres ;
- **un signal supplémentaire** dans le rapport Signaux (10e), sous la permission dédiée existante `achat.rapports.signaux` : taux de factures non rapprochées, et écarts de prix par fournisseur — cette dernière information rejoint la logique du signal « écarts BL par fournisseur » déjà livré, et les deux ensemble donnent une lecture de la qualité d'un fournisseur ;
- **un onglet sur la fiche du bon** (A-04), à côté de Réceptions et Documents : ce qui a été facturé sur ce bon, et l'écart. C'est là que l'acheteur en litige se rendra ;
- **aucun écran de saisie de facture** : ce serait sortir du périmètre (§2).

---

## 7. Décisions à trancher (Y1 → Y10)

| # | Décision | Enjeu | Recommandation |
|---|---|---|---|
| **Y1** | **Le système comptable peut-il fournir ses factures, sous quel format et à quelle fréquence ?** | **Bloquant.** Sans flux, le lot n'a aucun objet. | À instruire **avant toute conception**. C'est une question à poser à la comptabilité, pas une question technique. |
| **Y2** | **Import ou API ?** | Calendrier et dépendance à un tiers. | Import d'abord, modèle conçu pour accueillir une API ensuite (§3). |
| **Y3** | **Cardinalité facture ↔ bon.** | Un choix 1↔1 casse au premier groupage. | **N↔N**, comme pour les demandes (Z2 du cadrage D-25). La réalité comptable groupe et fractionne. |
| **Y4** | **Seuil de tolérance sur le prix** : montant absolu, pourcentage, ou les deux ? | Trop bas, la file est ingérable ; trop haut, elle ne sert à rien. | **Paramétrable** (écran A-08, comme les autres seuils), avec une valeur initiale prudente. Et **jamais de masquage** : sous le seuil, l'écart est classé, pas supprimé (§5). |
| **Y5** | **Que fait-on d'un écart constaté ?** Simple signalement, ou objet de traitement avec statut et motif ? | Un signalement sans suite se transforme en liste que personne ne regarde. | **Objet traçable** : un écart se solde par une décision motivée (accepté / contesté / avoir attendu), sur le modèle du renvoi motivé et de la pierre tombale documentaire. |
| **Y6** | **Le rapprochement modifie-t-il quoi que ce soit au bon ?** | Risque majeur : qu'une facture altère `quantite_livree` ou un statut. | **Non, jamais.** Le rapprochement est **en lecture seule** sur la chaîne d'engagement. Une facture est un dire du fournisseur ; seule la réception physique fait foi pour le reçu. Cette règle doit être un invariant testé, au même titre que la neutralité des écarts BL. |
| **Y7** | **Qui voit les écarts ?** | Information financière sensible. | Permission dédiée, distincte de la consultation des bons — cohérent avec `achat.rapports.signaux`. |
| **Y8** | **Rapproche-t-on les bons de régularisation ?** | Ils constatent l'existant, souvent sans facture attendue. | **Exclus par défaut**, avec bascule explicite dans les rapports (comme le fait déjà D-24 pour l'imputation). |
| **Y9** | **Sur quel exercice impute-t-on un rapprochement ?** | Une facture de janvier sur un bon de décembre. | Suivre le principe déjà retenu en D-24 : **la valeur est figée au moment où elle est constatée**, et un exercice clos ne se réécrit pas. |
| **Y10** | **Que fait-on des factures sans bon identifiable ?** | Elles s'accumulent. | File dédiée avec **rattachement manuel possible**, sur le modèle du rattachement des régularisations. Sans ce geste, la file ne se vide jamais. |

---

## 8. Ordre de traitement proposé

1. **Y1** — obtenir l'engagement de la comptabilité sur un flux. Tant que la réponse n'est pas connue, **ne rien concevoir** ;
2. modèle et import, avec traçabilité de l'origine de chaque ligne ;
3. moteur de rapprochement + file des non-rapprochées (le cœur de la valeur) ;
4. restitutions (onglet fiche, carte de rapport, signal) ;
5. traitement des écarts (Y5), une fois que le volume réel est connu.

Livrer 3 avant 4 est délibéré : une restitution sans moteur affiche des zéros, tandis qu'un moteur sans restitution alimente déjà un travail par export.

---

## 9. Le risque principal

Ce lot **dépend d'un tiers** que le projet ne maîtrise pas. C'est sa différence avec tout ce qui a été livré jusqu'ici : le raccordement Achat ⇄ Stock reliait deux modules du même dépôt, avec la même équipe et les mêmes conventions. Ici, la qualité du résultat dépendra de la régularité et de la propreté d'un flux produit ailleurs.

Deux précautions en découlent : **ne pas annoncer** de rapprochement automatique tant que Y1 n'est pas acquis, et **concevoir pour un flux imparfait** dès le départ (références déformées, doublons, factures partielles). Un moteur qui suppose des données propres ne rapprochera rien le jour de sa mise en service.

---

*Cadrage D-26 — CHU-YO, 09/08/2026. Étude préalable, aucune implémentation. Les décisions Y1 à Y10 sont à arbitrer avant l'ouverture du lot ; Y1 conditionne l'existence même du chantier.*
