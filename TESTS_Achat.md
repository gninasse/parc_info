# Module Achat — Plan de tests

> **Date** : 09/08/2026 · **Documents liés** : `SFD_Achat.md` (§9.4 — invariants IA-1 → IA-16), `CDC_Achat_v2.md` (§12 — recette), `API_Inter_Modules.md` (§7 — tests de contrat), `SPEC_UX_Achat.md` (§15 — textes gelés), `TESTS_Stock.md` (module amont).
> **Statut** : reflet de l'existant au 09/08/2026 — ce document décrit ce qui **est** exécuté, non ce qui serait souhaitable.

Le module Achat engage l'établissement vis-à-vis de fournisseurs : un montant faux, un reliquat erroné ou une permission oubliée ne se rattrapent pas par un correctif, ils se rattrapent par une négociation. D'où le principe qui gouverne ce plan : **tout invariant qui, s'il cédait, produirait une erreur invisible, a un test qui porte son nom**.

---

## 0. Organisation et conventions

- Emplacement : `Modules/Achat/tests/Feature/` (l'essentiel), `Modules/Achat/tests/Navigateur/` (jsdom), `Modules/Achat/tests/Recette/` (parcours sur base réelle).
- Base : `RefreshDatabase` sur SQLite par défaut. **Contrainte de portabilité** : la suite doit passer sur SQLite *et* PostgreSQL. Ce n'est pas une coquetterie — les `CHECK`, le `GROUP BY` strict, le typage JSON et la gestion des transactions en erreur diffèrent, et un module qui ne passe que sur l'un est un module non testé sur l'autre.
- Nommage : `test_{comportement}_{condition}` en français technique ; un invariant = au moins un test dont le nom contient `iaXX`.
- Un test ne réimplémente jamais ce qu'il vérifie : il appelle le vrai contrôleur (leçon du bordereau, cf. README « pièges »).

### Commandes

```bash
php artisan test Modules/Achat              # SQLite, suite complète
Modules/Achat/tests/postgres.sh             # PostgreSQL réel, schéma isolé
Modules/Achat/tests/migrations.sh           # migrate / rollback / réinstallation
Modules/Achat/tests/Navigateur/executer.sh  # écrans réels dans un DOM
php Modules/Achat/tests/Recette/lot_br.php  # recette du lot BR sur base réelle
python3 scripts/audit_permissions.py        # routes nues, permissions fantômes
python3 scripts/audit_code_mort.py          # JS/vues/services orphelins
```

---

## 1. Invariants (bloquants — SFD §9.4)

| # | Invariant | Où il est tenu |
|---|---|---|
| IA-1 | Cohérence des montants : écran = PDF = export, arrondis compris | `ServicesFondateursTest`, `PdfBonCommandeTest`, `PortabiliteSqlTest` |
| IA-2 | Immutabilité des valeurs figées (prix, TVA à la ligne) | `SaisieBrouillonTest`, `SchemaInvariantsTest` |
| IA-3 | Numérotation sans trou ni collision, y compris en concurrence | `CircuitSoumissionTest`, `CycleDeVieBonCommandeTest` |
| IA-4 | Plafond de réception revérifié **sous verrou** (deux bons concurrents) | `RaccordementReceptionTest`, `LivraisonSurCommandeTest` (Stock) |
| IA-5 | Idempotence : validation, notification de réception, finalisation | `RaccordementReceptionTest`, `ServicesFondateursTest` |
| IA-6 | Statuts recalculés justes, contre-mouvement `LIVRE` → `PARTIEL` compris | `LivraisonSurCommandeTest` (Stock) |
| IA-7 | Atomicité des licences : un échec à la 20e clé n'écrit rien | `ReceptionLicencesTest` |
| IA-8 | Tampon persistant puis purgé (reprise de session) | `ReceptionLicencesTest` |
| IA-9 | Garde « logiciel rattaché » à l'ouverture du wizard | `ReceptionLicencesTest` |
| IA-10 | Séparation des rôles : matrice 403 exhaustive, `.data`/PDF/exports/API compris | `InvariantsComplementairesTest`, `CeintureSecuriteTest` |
| IA-11 | Régularisation : bornes d'intérim, visa, extinction, unicité de rattachement | `RegularisationTest` |
| IA-12 | Référence de prix = dernier prix **payé**, non manipulable par le Catalogue | `InvariantsComplementairesTest` |
| IA-13 | Pierre tombale : fichier effacé, ligne conservée, motivée et signée | `DocumentsBonCommandeTest`, `SchemaInvariantsTest` |
| IA-14 | Chronologie = journal : aucun événement reconstruit depuis les colonnes | `FicheBonCommandeTest` |
| IA-15 | Dossier d'un bon engagé figé : 409 sur tout ajout de pièce | `InvariantsComplementairesTest` |
| IA-16 | Un document de réception n'est jamais accessible sans la permission du **module consulté** | `InvariantDocumentsReceptionTest`, `DocumentsReceptionTest` |

Un script vérifie que ces seize étiquettes existent réellement dans les suites : voir `scripts/audit_permissions.py` pour son pendant sur les permissions.

---

## 2. La ceinture de sécurité (D-19)

Les tests fonctionnels prouvent qu'une route **donnée** refuse un profil **donné**. Ils ne disent rien de la route qu'on oubliera de protéger dans six mois — et c'est toujours celle-là qui pose problème, parce que personne n'écrit un test pour un contrôle qu'il n'a pas conscience d'avoir omis.

`CeintureSecuriteTest` raisonne donc sur la **table de routage entière**, et couvre automatiquement toute route ajoutée ensuite :

| Garantie | Ce qui serait attrapé |
|---|---|
| Aucune route sans permission | Un `Route::get()` ajouté à la va-vite pour « un petit lien pratique » |
| Aucune route hors `auth` | Un groupe de routes déclaré au mauvais endroit du fichier |
| Toute permission exigée existe en base | Une faute de frappe dans `permission:achat.bons_comande.index` — 403 pour tout le monde, administrateur compris, sans aucune erreur visible |
| Les 20 permissions du SFD §5, ni plus ni moins | Un ajout non spécifié, ou une perte lors d'un remaniement |
| Pas d'action métier en GET | `/{id}/valider` accessible par un préchargement de navigateur ou un lien partagé |
| Un profil sans droit est refusé partout | La régression de masse |

Ces tests ont été **vérifiés par sabotage** : une route volontairement dépouillée de sa permission fait bien tomber la suite, avec son URI en clair dans le message.

### Audit des dépôts de fichiers

`AuditUploadsTest` couvre les quatre fautes classiques, **sur les deux modules** :

1. le MIME retenu est celui détecté par le serveur, jamais celui déclaré ;
2. un nom hostile (`../../etc/passwd.pdf`) ne construit pas le chemin — le nom d'origine est conservé pour l'affichage seulement ;
3. les disques ne sont pas servis par le serveur web ;
4. chaque lecture repasse par la permission ; déposer et consulter restent deux droits distincts.

---

## 3. Contrats inter-modules

| Test | Objet |
|---|---|
| `ApiContratTest` | Snapshots des 6 endpoints (structure et types) — casser un snapshot oblige à amender `API_Inter_Modules.md` |
| `RaccordementReceptionTest` | Le service d'intégration : plafonds, concurrence, idempotence, contre-passation, statuts |
| `DocumentsReceptionTest` | Le proxy documentaire et la dégradation partielle |
| `PortabiliteSqlTest` | Les requêtes agrégées se comportent identiquement sur les deux SGBD |

---

## 4. Restitutions et écrans

`ListeBonsCommandeTest`, `FicheBonCommandeTest`, `TableauDeBordTest`, `RapportsTest`, `ReliquatsTest`, `AdministrationTest` : chaque KPI est confronté à la liste qu'il prétend résumer, chaque export à son contenu, chaque paramètre à son effet **en bout de chaîne** (un paramètre modifié doit changer le comportement, pas seulement la ligne en base).

### Notifications (D-22)

Une notification est un **confort** greffé sur une action métier : tout l'enjeu est qu'elle ne puisse jamais nuire à cette action, ni partir à qui n'est pas concerné.

| Suite | Ce qu'elle protège |
|---|---|
| `NotificationsAchatTest` | Les destinataires se déduisent des **permissions** — retirer le visa à quelqu'un suffit à arrêter ses notifications, aucune liste n'est à tenir à jour. L'auteur ne s'auto-notifie pas. L'absence de préférence vaut **actif** (une notification qu'il faut activer n'est jamais activée). Le résumé hebdomadaire est **groupé par destinataire** : un courriel, pas un par reliquat. |
| `NotificationsClocheTest` | On ne voit **que** ses propres notifications (un identifiant deviné renvoie 404, il ne marque pas lue celle d'un collègue) et **que** celles du module Achat — la table `notifications` est partagée avec le reste de l'application, d'où un filtre sur la colonne `type` plutôt que sur le JSON `data`, comparable à l'identique sous SQLite et PostgreSQL. |

Le test le plus important de l'ensemble est `test_un_echec_de_notification_ne_defait_pas_la_soumission` : il **supprime la table des préférences** puis soumet un bon, et exige que la soumission aboutisse. Sans ce garde-fou, un serveur de messagerie injoignable bloquerait le circuit d'engagement de l'établissement. Les déclencheurs sont pour cette raison placés **après** les transactions, jamais dedans.

### Vérification navigateur

Les tests PHPUnit s'arrêtent à la réponse HTTP. Deux harnais exécutent le **vrai** JavaScript des vues dans un DOM (jsdom) et contrôlent ce que l'utilisateur voit :

- `Navigateur/rendu.cjs` et `Navigateur/fiche.cjs` (Achat) : colonnes, pilules, montants qualifiés TTC, barres de livraison, boutons grisés avec leur diagnostic, modale d'impression ;
- `Modules/Stock/tests/Navigateur/ecarts-bl.cjs` : la saisie des écarts BL, avec le vrai jQuery — ouverture sous le bon motif, pré-remplissage, charge réellement envoyée.

---

## 5. Recette

| Niveau | Ce qu'il prouve |
|---|---|
| Suites PHPUnit | Le comportement sur une base **fabriquée**, avec des permissions fabriquées |
| `Recette/lot_br.php` | Le parcours sur la base **réelle**, avec les comptes et les rôles qui y existent — un rôle mal seedé ou un paramètre absent ne passe qu'ici |
| Cahier CDC §12 | Les 22 scénarios initiaux + les 6 du lot BR (REC-23 → REC-28), chacun adossé à un test automatisé |

---

## 6. Ce qui n'est pas couvert, et pourquoi

- **Accessibilité au clavier** (SPEC_UX §18) : parcours de tabulation, `Entrée` = ligne suivante, focus des dialogues. Vérifié à la main, non automatisé — un test jsdom du focus donnerait une fausse assurance, le comportement réel dépendant du navigateur et du lecteur d'écran.
- **Charge et volumétrie** : aucun test de performance. Les requêtes des écrans de liste sont paginées et indexées, mais le comportement à 100 000 bons n'est pas mesuré.
- **Inventaires et journal des mouvements** (module Stock) : sept permissions sont seedées pour des écrans spécifiés (`SFD_Stock` §3.8/§3.9) mais **non livrés**. Elles n'ouvrent rien aujourd'hui ; `scripts/audit_permissions.py` les liste explicitement comme « en attente de leur écran », pour qu'elles ne se confondent pas avec des droits fantômes.

---

*Ce document décrit l'état au 09/08/2026 : 605 tests dans `Modules/Achat`, suites vertes sur SQLite et PostgreSQL.*
