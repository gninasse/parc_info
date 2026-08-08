<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Exceptions\ReferencementException;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\TamponEquipement;

/**
 * Cycle de vie du tampon de référencement des entrées (D13/D16) :
 * naissance des rangées au passage en RÉFÉRENCEMENT, saisie unitaire
 * contrôlée (unicité tampon global + ParcInfo à CHAQUE écriture), import
 * en masse (même code de contrôle), purge au retour brouillon.
 */
class TamponService
{
    /**
     * BROUILLON → RÉFÉRENCEMENT : une rangée de tampon par unité attendue
     * des lignes « modèle × N ». Les quantités se verrouillent (I16).
     */
    public function passerEnReferencement(Entree $entree): int
    {
        // La machine à états d'abord (409), la règle métier ensuite (422)
        if ($entree->statut !== Entree::STATUT_BROUILLON) {
            throw \Modules\Stock\Exceptions\TransitionInterditeException::pour(
                $entree->numero_affiche, $entree->statut, Entree::STATUT_REFERENCEMENT
            );
        }

        $lignesModeles = $this->lignesModeles($entree);

        if ($lignesModeles->isEmpty()) {
            throw ReferencementException::aucuneLigneModele();
        }

        return DB::transaction(function () use ($entree, $lignesModeles) {
            $entree->passerEnReferencement();

            $creees = 0;

            foreach ($lignesModeles as $ligne) {
                for ($i = 0; $i < (int) $ligne->quantite; $i++) {
                    TamponEquipement::create(['ligne_entree_id' => $ligne->id]);
                    $creees++;
                }
            }

            activity('stock')
                ->performedOn($entree)
                ->withProperties(['rangees_tampon' => $creees])
                ->log('passage_referencement');

            return $creees;
        });
    }

    /**
     * Retour au brouillon (I16/D16) : purge du tampon, déverrouillage,
     * action journalisée. Les articles et quantités du bon sont conservés.
     */
    public function retourBrouillon(Entree $entree): int
    {
        return DB::transaction(function () use ($entree) {
            $entree->retourBrouillon();

            $tampons = TamponEquipement::query()
                ->whereIn('ligne_entree_id', $entree->lignes()->select('id'));

            $saisis = (clone $tampons)->whereNotNull('numero_serie')->count();
            $purgees = $tampons->delete();

            activity('stock')
                ->performedOn($entree)
                ->withProperties(['references_perdues' => $saisis, 'rangees_purgees' => $purgees])
                ->log('retour_brouillon');

            return $saisis;
        });
    }

    /**
     * Saisie unitaire d'un n° de série (autosave S5 — wizard C).
     * Unicité contrôlée à CHAQUE écriture : tampon global (ce bon → « Déjà
     * saisi ligne N », autre bon → refus) puis parc_info_equipements
     * (→ « utilisez le rattachement »).
     */
    public function saisirNumero(Entree $entree, TamponEquipement $tampon, ?string $numeroSerie, ?string $etat = null): TamponEquipement
    {
        $numeroSerie = trim((string) $numeroSerie);

        if ($numeroSerie === '') {
            // Effacement (reset) d'une rangée : le numéro repart, l'état choisi reste
            $tampon->update(['numero_serie' => null] + ($etat !== null ? ['etat' => $etat] : []));

            return $tampon;
        }

        // Même numéro re-soumis (changement d'état seul) : pas de contrôle d'unicité
        if ($numeroSerie !== $tampon->numero_serie) {
            $this->verifierUnicite($entree, $numeroSerie);
        }

        $tampon->update(array_filter([
            'numero_serie' => $numeroSerie,
            'etat' => $etat,
        ], fn ($valeur) => $valeur !== null));

        return $tampon;
    }

    /**
     * @throws StockException 422 avec le message exact UX §3.3
     */
    public function verifierUnicite(Entree $entree, string $numeroSerie, array $ignorerTamponIds = []): void
    {
        $doublon = TamponEquipement::query()
            ->where('numero_serie', $numeroSerie)
            ->when($ignorerTamponIds !== [], fn ($q) => $q->whereNotIn('id', $ignorerTamponIds))
            ->with('ligneEntree')
            ->first();

        if ($doublon !== null) {
            throw $doublon->ligneEntree !== null && (int) $doublon->ligneEntree->entree_id === (int) $entree->id
                ? ReferencementException::dejaSaisiLigne($this->rangDansEntree($entree, $doublon))
                : ReferencementException::dejaSaisiAutreBon();
        }

        $existant = Equipement::query()->where('numero_serie', $numeroSerie)->first();

        if ($existant !== null) {
            throw ReferencementException::existeDeja($existant->code_inventaire);
        }
    }

    /**
     * Parsing d'un collage / fichier CSV (MD-IMPORT) : un numéro par ligne,
     * trim, dédoublonnage en conservant l'ordre.
     */
    public function parserContenu(string $contenu): array
    {
        return collect(preg_split('/\R/', $contenu) ?: [])
            ->map(fn (string $ligne) => trim(trim($ligne), ';,'))
            ->filter(fn (string $ligne) => $ligne !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Rapport d'import (analyse sans écriture) — même code de contrôle que
     * la saisie unitaire (verifierUnicite) :
     * {acceptes, doublons_tampon, deja_connus, en_trop}.
     */
    public function analyserImport(Entree $entree, array $numeros): array
    {
        $rapport = [
            'acceptes' => [],
            'doublons_tampon' => [],
            'deja_connus' => [],
            'en_trop' => [],
        ];

        $capacite = TamponEquipement::query()
            ->whereIn('ligne_entree_id', $entree->lignes()->select('id'))
            ->whereNull('numero_serie')
            ->count();

        foreach ($numeros as $numero) {
            try {
                $this->verifierUnicite($entree, $numero);
            } catch (ReferencementException $e) {
                $cle = $e->categorie === ReferencementException::CATEGORIE_DEJA_CONNU ? 'deja_connus' : 'doublons_tampon';
                $rapport[$cle][] = ['numero' => $numero, 'detail' => $e->getMessage()];

                continue;
            }

            if (count($rapport['acceptes']) < $capacite) {
                $rapport['acceptes'][] = $numero;
            } else {
                $rapport['en_trop'][] = ['numero' => $numero, 'detail' => 'Plus de rangée vide disponible'];
            }
        }

        return $rapport;
    }

    /**
     * « Appliquer les N acceptés » : ré-analyse (l'état a pu changer depuis
     * le rapport) puis remplit les rangées vides, dans l'ordre du wizard.
     */
    public function appliquerImport(Entree $entree, array $numeros): array
    {
        return DB::transaction(function () use ($entree, $numeros) {
            $rapport = $this->analyserImport($entree, $numeros);

            $rangeesVides = TamponEquipement::query()
                ->whereIn('ligne_entree_id', $entree->lignes()->select('id'))
                ->whereNull('numero_serie')
                ->orderBy('ligne_entree_id')
                ->orderBy('id')
                ->get();

            foreach ($rapport['acceptes'] as $index => $numero) {
                $rangeesVides[$index]->update(['numero_serie' => $numero]);
            }

            activity('stock')
                ->performedOn($entree)
                ->withProperties(['appliques' => count($rapport['acceptes'])])
                ->log('import_references');

            return $rapport;
        });
    }

    /** Progression du wizard : [saisis, total]. */
    public function progression(Entree $entree): array
    {
        $tampons = TamponEquipement::query()
            ->whereIn('ligne_entree_id', $entree->lignes()->select('id'));

        return [(clone $tampons)->whereNotNull('numero_serie')->count(), $tampons->count()];
    }

    public function lignesModeles(Entree $entree)
    {
        return $entree->lignes()
            ->whereNotNull('article_id')
            ->whereHas('article', fn ($q) => $q->where('nature', Article::NATURE_EQUIPEMENT))
            ->with(['article:id,code,nom,nature,modele', 'tampons' => fn ($q) => $q->orderBy('id')])
            ->orderBy('id')
            ->get();
    }

    /** Rang global (1-based) d'une rangée de tampon dans son bon — « ligne 4 » du message UX. */
    private function rangDansEntree(Entree $entree, TamponEquipement $tampon): int
    {
        $ids = TamponEquipement::query()
            ->whereIn('ligne_entree_id', $entree->lignes()->select('id'))
            ->orderBy('ligne_entree_id')
            ->orderBy('id')
            ->pluck('id');

        return $ids->search($tampon->id) + 1;
    }
}
