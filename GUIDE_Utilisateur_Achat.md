# Module Achat — Guide de l'utilisateur

> **Pour qui** : acheteur, validateur, contrôleur de gestion, magasinier (les deux derniers chapitres).
> **Version** : 1.0 · 09/08/2026 · CHU-YO — Centre Hospitalier Universitaire Yalgado Ouédraogo.

---

## Le circuit en une phrase

**Catalogue → bon de commande → visa → réception au magasin → parc.**

Chaque flèche est un geste d'une personne différente, et chacune laisse une trace. Le module ne fait rien d'autre que rendre cette chaîne visible et vérifiable.

```
  Acheteur              Validateur           Magasinier           Le parc
     │                      │                    │                   │
  brouillon ──soumettre──► visa ──valider──► livraison ──valider──► fiches
     │                      │                    │                   │
  modifiable            verrouillé          bon d'entrée      unités créées
  librement           (plus de retour       lié à la commande  et valorisées
                       en arrière)
```

Un principe gouverne tout : **rien n'est jamais effacé**. Une erreur se corrige par un geste inverse, tracé, motivé. C'est ce qui permet de répondre, deux ans plus tard, à la question « qui a commandé cela, et pourquoi ? ».

---

## 1. Acheteur — préparer et soumettre un bon

### 1.1 Créer le bon

`Achat → Bons de commande → [+ Nouveau bon de commande]`

L'en-tête demande le **fournisseur** (choisi dans une modale, ou créé sur place s'il est nouveau), la **date** et le **service demandeur**. Les lignes s'ajoutent par le bouton `[+ Ajouter des articles]`, qui ouvre le catalogue : on coche, on valide, les lignes arrivent.

Trois choses se figent au moment où vous ajoutez une ligne :

- le **prix** que vous négociez (pas celui du catalogue) ;
- le **taux de TVA** ;
- la **désignation**.

Elles ne bougeront plus, même si le catalogue change ensuite. Un bon de commande est un engagement : il doit dire la même chose dans un an qu'aujourd'hui.

### 1.2 La décomposition du prix

En saisissant un prix, vous voyez apparaître une comparaison : **le dernier prix réellement payé** pour cet article, la moyenne des trois derniers bons, et le prix indicatif du catalogue.

Si votre prix s'écarte de plus du seuil (20 % par défaut) **vers le haut**, une pilule orange le signale. Elle n'empêche rien : un prix peut monter pour de bonnes raisons (urgence, petite quantité, fournisseur unique). Elle vous évite simplement de découvrir l'écart au moment du visa.

La référence est le prix **payé**, jamais le prix indicatif — celui-ci se modifie, celui-là non.

### 1.3 Soumettre

`[Soumettre au visa]` ferme le brouillon et l'envoie au validateur. Avant de le faire, l'écran vous montre un récapitulatif et, le cas échéant, des **signaux** : fournisseur créé récemment, écart de prix, plusieurs bons rapprochés pour le même fournisseur.

Ces signaux ne bloquent pas. Ils vous donnent l'occasion de vérifier avant qu'un autre ne le fasse.

**Vous vous êtes trompé après avoir soumis ?** `[Reprendre]` ramène le bon en brouillon, tant que le validateur n'a pas statué. C'est votre bon, vous pouvez le reprendre — mais vous seul.

---

## 2. Validateur — viser un bon

`Achat → Bons de commande → filtre « Soumis »`

Ouvrez la fiche, lisez les lignes, puis :

- **`[Valider]`** attribue le numéro définitif (`BC-2026-0041`) et rend le bon immuable. C'est l'engagement de l'établissement ;
- **`[Renvoyer en brouillon]`** exige un **motif**, qui sera lu par l'acheteur et conservé au dossier.

Au moment de valider, une fenêtre récapitule les signaux du bon. Lisez-la : c'est le dernier moment où une question coûte moins cher qu'une correction.

**L'auto-validation** (saisir et viser soi-même) reste possible — un jour de congés, il faut bien que le circuit avance — mais elle est **marquée** sur la fiche et comptée dans les indicateurs. Ce n'est pas une faute, c'est une exception qui doit rester exceptionnelle.

**Après validation, le bon ne se modifie plus.** Une erreur se traite par :

- `[Annuler]` si rien n'a été livré (motif obligatoire) ;
- un bon complémentaire si une ligne manque ;
- `[Clôturer le reliquat]` si le fournisseur ne livrera pas le reste.

---

## 3. Suivre les livraisons

### 3.1 L'onglet Réceptions

Il montre deux choses de nature différente, et la distinction compte :

- **Intégrées** — ces réceptions ont été validées au magasin. Elles font foi : ce sont elles qui décomptent le reste à livrer ;
- **En cours côté magasin** — des bons d'entrée en cours de saisie. Informatif seulement : tant que le magasinier n'a pas validé, **rien n'est décompté**. Le badge le dit.

### 3.2 Les pièces de la livraison

Sur chaque réception intégrée vous trouvez :

- **`[🖨 Bordereau de réception]`** — le document signé par le magasinier et le livreur. C'est votre pièce en cas de contestation ;
- **les pièces jointes** — le bordereau du fournisseur numérisé, les photos de la livraison ;
- **`[⚠ Écart BL]`**, en rouge, si le magasin a constaté une différence entre ce que le bordereau annonçait et ce qui a été compté.

Vous n'avez pas besoin d'accès au module Stock pour les consulter : ce sont les pièces de **vos** commandes.

**Un point à ne pas confondre** : un écart déclaré ne change aucun compteur. Si le bordereau annonçait 10 et que 8 ont été comptés, votre commande est décomptée de **8**, et il reste 12 à livrer. L'écart documente la réclamation à faire au fournisseur, rien de plus.

### 3.3 Les reliquats

`Achat → Reliquats` liste tout ce qui est commandé et non livré, avec l'âge de l'attente en pilules de couleur. Le pied de tableau donne le montant **engagé non livré** : c'est ce que l'établissement doit encore recevoir.

Quand un fournisseur ne livrera manifestement plus, `[Clôturer le reliquat]` solde la ligne avec un motif. Les réceptions déjà faites restent, la commande passe en « clôturée ».

---

## 4. Les licences et les prestations

Une licence n'entre pas en stock : elle n'a pas de carton. Elle se réceptionne par un **assistant** dédié.

`Fiche du bon → onglet Licences → [Réceptionner…]`

Saisissez les clés une par une (la touche `Entrée` passe à la suivante — le geste est prévu pour une saisie longue). **Votre travail est enregistré au fur et à mesure** : vous pouvez fermer la fenêtre, revenir demain, les clés déjà saisies sont là.

À la fin, `[Finaliser]` crée les licences dans le parc, rattachées au logiciel, valorisées au prix du bon. Si une seule clé pose problème, **rien n'est créé** : vous corrigez, vous refinalisez. Il n'y a jamais de demi-réception.

Pour une **prestation** (installation, formation, maintenance), il n'y a pas de clé : `[Constater le service fait]` avec la date et l'attestation.

---

## 5. Corriger une erreur

| La situation | Le geste |
|---|---|
| Le brouillon est faux | Modifiez-le librement, rien n'est engagé |
| Vous avez soumis trop vite | `[Reprendre]` (vous seul, avant le visa) |
| Le validateur a renvoyé le bon | Lisez le motif sur la fiche, corrigez, resoumettez |
| Le bon validé comporte une erreur, rien n'est livré | `[Annuler]` avec motif |
| Il manque une ligne sur un bon validé | Créez un bon complémentaire |
| Le fournisseur a trop livré | Le magasin refuse l'excédent, ou vous créez un bon complémentaire |
| Le fournisseur ne livrera plus le reste | `[Clôturer le reliquat]` avec motif |
| Une pièce jointe est erronée | Supprimez-la avec un motif : la ligne reste au dossier, barrée, avec l'explication |

Vous remarquerez que **chaque correction demande un motif**. Ce n'est pas de la bureaucratie : c'est ce qui rend le dossier défendable devant un contrôle ou un fournisseur.

---

## 6. Les bons de régularisation

Certaines acquisitions ont eu lieu **avant** la mise en service du module (période d'intérim). Elles se saisissent a posteriori, marquées **« Régularisation »**.

Ces bons :

- portent un badge visible sur toutes les listes ;
- sont **exclus par défaut** des états et des statistiques (ils fausseraient les tendances) ;
- se rattachent aux équipements déjà présents dans le parc, ce qui **éteint la dette** au fur et à mesure.

L'écran de régularisation affiche la dette réelle restante : le nombre d'équipements du parc qui n'ont encore aucun bon de commande derrière eux.

---

## 7. Magasinier — recevoir une livraison sur commande

*(Ce chapitre concerne le module Stock, mais c'est là que la chaîne se joue.)*

### 7.1 Lier le bon d'entrée à la commande

`Stock → Entrées → [+ Nouvelle entrée]`, puis **`[Lier à une commande…]`**.

La liste ne propose que des bons livrables. Une fois lié :

- le **fournisseur est imposé** par la commande (le champ se verrouille) ;
- les lignes se **pré-remplissent** avec ce qui reste à livrer, au prix figé de la commande ;
- vous ne pouvez pas saisir plus que le reste : le champ vous le dit, avec le reste exact.

**Livraison partielle ?** Baissez simplement les quantités. Le solde restera à livrer.

### 7.2 Le bordereau du fournisseur

Joignez-le tout de suite, au comptoir : le bouton accepte une **photo prise avec le téléphone**. Choisissez le type « Bordereau du fournisseur » — c'est ce qui le distingue d'une photo de colis, et c'est cette pièce qu'on cherchera plus tard.

Selon le paramétrage de l'établissement, cette pièce peut être **obligatoire** pour valider une entrée liée à une commande.

### 7.3 Déclarer un écart

Si le bordereau annonce 10 cartons et que vous en comptez 8 : choisissez le motif **« Écart BL — réclamation »**, puis remplissez le petit tableau qui apparaît (annoncé, compté, motif).

C'est **facultatif** — mais c'est ce qui transforme un « il manquait des cartons » en pièce opposable au fournisseur. L'écart s'imprime sur le bordereau de réception que **le livreur contresigne**.

Rassurez-vous sur un point : **déclarer un écart ne change aucune quantité**. Ce qui entre en stock, c'est ce que vous avez compté, ni plus ni moins.

### 7.4 Valider et imprimer

`[Valider]` fait tout en une fois : les quantités entrent en stock, les fiches d'équipement sont créées, et la commande est décomptée côté Achat.

Imprimez ensuite le **bordereau de réception** et faites-le signer au livreur. Sans cette signature, une réclamation ultérieure ne pèse rien.

Le bordereau ne porte **aucun prix** : c'est un document de quai, le livreur n'a pas à connaître les prix négociés par l'établissement.

---

## 8. Être averti sans être noyé

Vous n'avez plus à guetter : le module vous prévient de ce qui vous concerne.

| Vous recevez | Quand | Qui la reçoit |
|---|---|---|
| **Un bon attend mon visa** | Un bon est soumis | Les personnes habilitées à viser |
| **Mon bon a été renvoyé** | Un validateur le renvoie en brouillon | L'auteur du bon, **avec le motif** |
| **Une livraison a été reçue** | Le magasin valide une entrée sur votre bon | L'auteur du bon |
| **Mes bons en attente** | Chaque lundi matin | Les auteurs de bons non livrés au-delà du délai |

Deux principes valent la peine d'être connus :

- **Vous ne recevez que ce qui vous concerne.** Les destinataires sont déduits de vos **permissions** : si vous ne pouvez pas viser, aucun bon à viser ne vous parviendra, même en laissant l'interrupteur ouvert. Et vous n'êtes jamais averti d'un geste que vous venez de faire vous-même.
- **Le résumé du lundi est unique.** Dix bons en retard donnent **un** message, pas dix.

**La cloche** (en haut à droite) porte une pastille rouge tant qu'il reste des messages non lus. Ouvrir un message le marque lu ; « Tout marquer comme lu » vide la pastille d'un coup.

**Vous réglez ce que vous recevez** : `Achat → cloche → Régler mes notifications`, ou directement `/achat/preferences-notification`. Chaque type se règle sur deux canaux, l'application et le courriel, et chaque interrupteur s'enregistre seul. Ces réglages sont **les vôtres** : personne d'autre n'est affecté, et vous n'avez besoin de personne pour les changer. Coupez le courriel et gardez la cloche si votre messagerie déborde : l'information reste consultable au lieu d'être perdue dans un filtre.

---

## 9. Questions fréquentes

**Pourquoi mon bouton est-il grisé ?**
Survolez-le : une infobulle dit toujours pourquoi. Il n'y a jamais de bouton inactif sans explication.

**J'ai un message « 403 »**
Votre profil n'a pas cette permission. Le message nomme celle qui manque : transmettez-la à votre administrateur.

**Le numéro du bon n'apparaît pas**
Il est attribué **à la validation**, pas à la création. Un brouillon s'affiche « Brouillon #58 » : il n'engage rien, il n'a donc pas de numéro.

**Les montants sont-ils HT ou TTC ?**
Chaque total le précise. Les états et exports portent la mention dans leur titre — jamais un montant sans sa qualification.

**Le module Stock est indisponible**
La fiche de vos bons reste consultable et vos compteurs restent exacts. Seule la section « en cours côté magasin » s'efface, en le disant.

**Puis-je supprimer un bon validé ?**
Non. Vous pouvez l'annuler (avec motif) s'il n'a rien reçu. Un document qui a pu circuler ne disparaît pas.

---

## 10. À qui s'adresser

| Sujet | Interlocuteur |
|---|---|
| Un article manque au catalogue | Gestionnaire du catalogue |
| Une permission manquante, un rôle à ajuster | Administrateur (module Core) |
| Un paramètre à changer (seuils, délais, taille des pièces) | `Achat → Administration` (profil habilité) |
| Une réception qui n'apparaît pas | Le magasin : la validation du bon d'entrée est ce qui déclenche tout |
| Trop de courriels, ou pas assez | Personne : `Achat → cloche → Régler mes notifications`, c'est à vous |

---

*Guide utilisateur du module Achat — CHU-YO, 09/08/2026. Documents de référence : `SFD_Achat.md`, `SPEC_UX_Achat.md`, `CDC_Achat_v2.md`.*
