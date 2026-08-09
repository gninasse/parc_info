# Module Stock — Contrat des API inter-modules

> **Date** : 02/08/2026 · **Documents liés** : `SFD_Stock.md` **v2.0** §2.6 et §8, `SFD_Catalogue.md` §2.5 · **Statut** : **version 2.0** — révision « catalogue unifié » : les endpoints `disponibilite/consommable/{id}` et `disponibilite/piece/{id}` de la v1.0 sont **fusionnés** en `disponibilite/article/{id}` (l'article Catalogue porte sa nature) ; le registre des consommateurs s'enrichit du module Catalogue (fiche article) et du futur module Achat
> **Objet** : contrat d'interface des endpoints que Stock expose aux autres modules. Ce document comble une lacune constatée dans le projet : les API internes d'Organisation et de Grh sont consommées (par ParcInfo notamment) sans aucun contrat écrit, ce qui rend leurs évolutions risquées. Toute modification d'un format décrit ici est un **changement de contrat** : elle se documente ici *avant* d'être codée, et les consommateurs listés sont prévenus.

## 1. Règles générales

| Règle | Détail |
|---|---|
| Base | `/stock/api/…`, routes web (session), middleware `auth` + permission `stock.api.view` |
| Authentification | Session applicative (comme les API internes existantes) ; **pas** de Sanctum en v1 |
| Format | JSON UTF-8 ; dates `YYYY-MM-DD`, horodatages ISO 8601 ; décimaux en **chaînes** (`"12.50"`) pour éviter les pertes de précision côté JS |
| Bornage | Toute liste est limitée (`limit` défaut 50, max 200) — jamais de « renvoyer tout » (leçon des `getApiData` d'Organisation) |
| Recherche | Paramètre `q`, opérateur `LIKE` portable, insensibilité à la casse gérée applicativement (jamais `ilike`) |
| Erreurs | `403` sans permission ; `404` `{"message": "…"}` pour un id inconnu ; jamais de message d'exception brut (`$e->getMessage()` proscrit — leçon Grh) |
| Stabilité | Champs **ajoutés** librement (les consommateurs doivent ignorer l'inconnu) ; champs renommés/supprimés = changement de contrat |

## 2. Endpoints

### 2.1 `GET /stock/api/magasins`

Liste plate des magasins actifs. **Consommateurs** : Organisation (fiche site, relation `Site::magasins()` côté UI), sélecteurs inter-modules.

Paramètres : `q` (recherche code/libellé), `site_id` (filtre), `limit`.

```json
{
  "data": [
    {
      "id": 1,
      "code": "MAG-CHUYO-01",
      "libelle": "Magasin central Yalgado",
      "site_id": 1,
      "site_libelle": "Site principal CHU-YO",
      "est_actif": true
    }
  ],
  "total": 1
}
```

### 2.2 `GET /stock/api/disponibilite/article/{id}` *(v2.0 — fusionne consommable+pièce)*

Disponibilité d'un article Catalogue (natures stockables quantitatives), tous magasins. **Consommateurs** : Catalogue (bloc « Disponibilité en stock » de la fiche article), ParcInfo, futur Achat.

```json
{
  "article": {"id": 42, "code": "CONS-00014", "nom": "Toner HP LaserJet 26A", "nature": "consommable"},
  "unite": "unité",
  "total": "7.00",
  "statut_global": "SOUS_SEUIL",
  "niveaux": [
    {"magasin_id": 1, "magasin_code": "MAG-CHUYO-01", "quantite": "5.00",
     "seuil": "10.00", "seuil_origine": "article", "statut": "SOUS_SEUIL"},
    {"magasin_id": 2, "magasin_code": "MAG-CHUYO-02", "quantite": "2.00",
     "seuil": null, "seuil_origine": "aucun", "statut": "OK"}
  ]
}
```

Sémantique : `statut` ∈ `OK`|`SOUS_SEUIL`|`RUPTURE` ; `seuil_origine` ∈ `local`|`article`|`aucun` (**cascade v2.0 simplifiée** — SFD Stock §7.6) ; `statut_global` = pire statut des magasins seuillés. Article jamais réceptionné → `total: "0.00"`, `niveaux: []` (200, pas 404). Article de nature `equipement` → 422 « article sérialisé : utiliser l'endpoint équipements » ; nature `licence` → 422 « article non stocké ».

### 2.3 *(supprimé en v2.0 — fusionné dans 2.2)*

### 2.4 `GET /stock/api/equipements/{id}/magasin`

Magasin de rattachement d'un équipement. **Consommateur** : ParcInfo — fiche équipement (affichage « En stock — Magasin X » au lieu du seul badge de statut).

```json
{
  "equipement_id": 318,
  "magasin": {
    "id": 1,
    "code": "MAG-CHUYO-01",
    "libelle": "Magasin central Yalgado",
    "date_rattachement": "2026-08-15"
  }
}
```

`"magasin": null` si l'équipement n'est rattaché à aucun magasin. **Cas d'incohérence** (SFD §7.3 — rattaché mais statut ≠ « en stock ») : le rattachement est renvoyé tel quel, accompagné de `"coherent": false` ; le consommateur peut l'afficher, la résolution reste du ressort de l'inventaire Stock.

## 3. Codes de statut récapitulatifs

| Code | Cas |
|---|---|
| 200 | Réponse nominale (y compris disponibilité nulle) |
| 302 → login | Non authentifié (routes web) |
| 403 | Authentifié sans `stock.api.view` |
| 404 | `{id}` inconnu au catalogue concerné |
| 422 | Paramètre invalide (`limit` hors bornes, `site_id` non numérique) |

## 4. Registre des consommateurs

| Consommateur | Endpoints | Usage | Contact chantier |
|---|---|---|---|
| ParcInfo — fiche consommable | 2.2 | Bloc disponibilité | Chantier d'intégration (SFD §9.3, temps 2) |
| ParcInfo — fiche équipement | 2.4 | Localisation magasin | idem |
| Organisation — fiche site | 2.1 | Affichage du magasin du site | idem |
| Sélecteurs partagés | 2.1 | Listes de magasins | — |

Tout nouveau consommateur s'ajoute à ce registre : c'est lui qui définit qui prévenir en cas d'évolution.

## 5. Ce que Stock consomme (rappel, pour symétrie)

Stock dépend des endpoints amont suivants (contrats **non écrits** à ce jour — leur formalisation par leurs modules respectifs est recommandée) : `/organisation/{directions,services,unites,locaux,postes-travail}/api`, `/grh/employes/api`, recherche d'équipements ParcInfo pour les sélecteurs. Les précautions associées (pas de pagination amont, `ilike`, absence de permission sur certains) sont documentées en SFD §2.3–2.4 ; Stock ne doit jamais présumer que ces données sont filtrées par droits.

## 6. Tests contractuels

Chaque endpoint est couvert par un test snapshot du format de réponse (`TESTS_Stock.md` §4). La rupture d'un snapshot en CI est le signal qu'un changement de contrat est en cours : mettre à jour **ce document d'abord**, prévenir les consommateurs du registre (§4), puis le snapshot.

---
*Fin du document.*
