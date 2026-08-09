---
name: Parc Info (CHU-YO Keystone)
colors:
  primary: "#0d6efd"
  primary-hover: "#0b5ed7"
  marine: "#0d2060"
  success: "#28a745"
  danger: "#dc3545"
  info: "#0dcaf0"
  secondary: "#6c757d"
  background: "#f8f9fa"
  surface: "#ffffff"
  text-main: "#212529"
  text-muted: "#6c757d"
  border: "#dee2e6"
typography:
  headline-lg:
    fontFamily: "'Source Sans 3', sans-serif"
    fontSize: 28px
    fontWeight: "600"
  headline-md:
    fontFamily: "'Source Sans 3', sans-serif"
    fontSize: 24px
    fontWeight: "600"
  title:
    fontFamily: "'Source Sans 3', sans-serif"
    fontSize: 20px
    fontWeight: "600"
  body:
    fontFamily: "'Source Sans 3', sans-serif"
    fontSize: 16px
    fontWeight: "400"
  label:
    fontFamily: "'Source Sans 3', sans-serif"
    fontSize: 14px
    fontWeight: "500"
rounded:
  none: 0
  sm: 0.2rem
  DEFAULT: 0.25rem
  lg: 0.3rem
  circle: 50%
spacing:
  xs: 0.25rem
  sm: 0.5rem
  md: 1rem
  lg: 1.5rem
  xl: 3rem
elevation:
  card: none
  modal: 0 0.5rem 1rem rgba(0, 0, 0, 0.15)
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "#ffffff"
    rounded: "{rounded.DEFAULT}"
    padding: "{spacing.sm} {spacing.md}"
  card:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.DEFAULT}"
    padding: "{spacing.md}"
    border: "1px solid {colors.border}"
  modal:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.lg}"
---

## Brand & Style

The design system for **Parc Info (CHU-YO Keystone)** is built for administrative efficiency, clarity, and rapid data entry. The style is strictly **Flat & Minimalist** with a highly structured, data-first approach to UI. 

Extravagant stylings, heavy drop shadows, and oversized border radii are intentionally avoided in favor of a crisp, professional, and dense interface. Due to specific identity requirements from CHU-YO, the use of medical crosses is strictly prohibited, relying instead on clean iconography (FontAwesome) and typography.

## Colors

The color palette is anchored by a deep corporate blue and a vibrant interactive blue, maintaining a serious yet modern tone.

- **Bleu Marine (`#0d2060`)**: Used for branding, main navigation backgrounds, and primary identity elements. It grounds the application.
- **Bleu Primary (`#0d6efd`)**: The standard interactive color used for main actions, links, and primary buttons.
- **Semantic Colors**: Standard Bootstrap colors (`Success`, `Danger`, `Info`, `Secondary`) are strictly mapped to their semantic meanings (e.g., Green for activation/saving, Red for deletion/deactivation).

## Typography

The application relies entirely on **Source Sans 3** to provide exceptional legibility for data-dense tables and complex forms. 

- **Hierarchy**: Sections within modals and cards are clearly delineated using semi-bold (`fw-semibold`) headings with distinct bottom borders rather than relying heavily on size differences.
- **Form Readability**: Input text and labels follow standard Bootstrap 5 sizing, keeping interfaces compact. 

## Layout & Spacing

The layout is fundamentally modular and based on Bootstrap 5 utility classes.

- **Spacing Utilities**: Standardized spacing is used across the app. `mb-3` is used for form group separation, while `pb-2` is used for section headers.
- **Index Layouts**: Data lists follow a strict top-to-bottom flow: a dedicated filter card at the very top, followed by a dedicated toolbar (action buttons), followed by the data table (`Bootstrap Table`) contained within a card.
- **Action Toolbars**: Buttons within the main table toolbar are strictly icon-only (`<i class="fas fa-..."></i>`) with tooltips (`data-bs-toggle="tooltip"`). Text labels on action buttons are avoided to keep the toolbar compact and standardized.

## Components & Interaction Patterns

- **Cards**: Cards are strictly flat (`elevation: none`) with standard 1px borders. This prevents "over-stylisation".
- **Modals**: Used extensively for creation forms. They use `modal-lg` by default, focusing on a clean header (often with an icon using a `bg-opacity-10` background) and heavily structured body sections.
- **Show / Profile Views**: Uses an "In-Place Edit" pattern. The page loads with all form fields disabled. An "Edit" button toggles the view, enabling fields and revealing "Save/Cancel" buttons.
- **State Management**: Button states in lists are highly dynamic. Buttons (Edit, Activate, Delete) are disabled by default and enabled conditionally via JavaScript based on the number and state of the selected rows in the table.
- **Confirmation**: Destructive or state-changing actions always trigger a standard `SweetAlert2` confirmation modal styled with the appropriate semantic color.

---

# Conventions issues des modules Stock et Achat (2026)

*Ces règles sont nées de décisions prises en cours de chantier. Elles sont
consignées ici pour être reprises telles quelles par les modules suivants —
non par goût de l'uniformité, mais parce que chacune répond à un problème
rencontré.*

## La sémantique du JAUNE

Le jaune ne signifie **ni « attention », ni « erreur »**. Il signifie
**« verrouillé, en cours d'officialisation »**.

Un bon de commande soumis au visa est jaune : son auteur ne peut plus le
modifier, le validateur ne s'est pas encore prononcé. C'est un état
d'attente, pas un défaut. Employer le jaune pour signaler un problème dans
un autre écran brouillerait ce sens, et l'utilisateur cesserait de faire la
différence.

| Couleur | Sens | Exemple |
|---|---|---|
| Gris | Brouillon, sans effet | « Brouillon #58 » |
| **Jaune** | **Verrouillé, en officialisation** | Bon soumis au visa |
| Bleu | Engagé, en cours | Bon validé, livraison partielle |
| Vert | Abouti | Bon livré, réception intégrée |
| Rouge | Refus, annulation, écart | Renvoi motivé, contre-passation, écart BL |
| Noir/foncé | Fin de vie assumée | Reliquat clôturé |

## Le code des alertes

- **Rouge** = une action est attendue de l'utilisateur (un refus à traiter,
  un écart à réclamer) ;
- **Orange** = vigilance, aucune action imposée (un signal, un prix qui
  s'écarte de la référence) ;
- **Orange hachuré** = dette héritée, à résorber progressivement (la
  régularisation de l'intérim).

La distinction rouge/orange n'est pas décorative : une application dont tout
est rouge n'a plus de rouge.

## Jamais d'information par la couleur seule

Chaque pilule de statut porte **un libellé**, et chaque badge sémantique un
pictogramme (✔ / ⚠ / ⛔). Un daltonien, une impression en noir et blanc, ou
un écran mal réglé ne doivent pas faire perdre l'information.

## Le pied de tableau à compteurs

Toute liste porteuse d'enjeux financiers affiche un pied de tableau qui
**totalise ce qui est affiché**, en qualifiant le montant (HT / TTC). Un
total sans qualification est une question qu'on laisse à l'utilisateur, et
il y répondra à sa manière.

## Les 403 nominatives

Un refus de permission nomme **la permission manquante**, jamais un simple
« accès refusé ». L'utilisateur peut ainsi transmettre une demande précise à
son administrateur, plutôt qu'un « ça ne marche pas ».

> « Cette action requiert la permission « achat.bons_commande.valider ». »

## Les boutons grisés portent leur diagnostic

Un bouton inactif sans explication est une impasse. Chaque bouton grisé
porte une infobulle (et un `aria-describedby`) qui dit **pourquoi** :

> Bouton « Modifier » grisé — « Un bon validé ne se modifie plus
> (BC-2026-0041). »

## Les impressions s'ouvrent en modale

Tout PDF (bon de commande, bordereau de réception, états) s'affiche dans une
**modale à iframe**, avec impression directe, téléchargement et ouverture en
onglet. Jamais un onglet imposé : l'utilisateur perdrait son contexte de
travail, et sur les listes, sa sélection.

## Les sélections passent par une modale

Choisir un article, un fournisseur, une commande ou des unités se fait dans
une **modale de sélection** (recherche, colonnes utiles, sélection simple ou
multiple), jamais dans une liste déroulante. Un catalogue de 400 articles ne
se parcourt pas dans un `select`.

## Une toolbar, pas une colonne d'actions

Les listes n'ont **pas de colonne d'actions** en bout de ligne. On
sélectionne une ligne (radio ou case), et la **toolbar** au-dessus du
tableau s'active. Cela évite les tableaux qui débordent horizontalement, et
rend les actions découvrables au même endroit sur tous les écrans.

## Les états vides enseignent

Un écran vide ne dit pas « aucun résultat ». Il dit ce qu'il faut faire, et
d'où viendra la donnée :

> « Rien n'a encore été livré sur cette commande. Les réceptions se
> saisissent au magasin (module Stock) et apparaîtront ici. »

C'est souvent le seul endroit où un utilisateur apprend la frontière entre
deux modules.

## Une attente affichée disparaît le jour de la livraison

Quand une fonctionnalité dépend d'un prérequis non livré, on l'affiche
**désactivée avec sa raison** plutôt que de la masquer — l'utilisateur sait
qu'elle est prévue. Mais la mention doit disparaître **automatiquement** le
jour où le prérequis arrive (condition sur le schéma, pas sur une constante
à changer à la main) : une carte « bientôt disponible » qui survit à sa
livraison apprend à ne plus lire l'écran.
