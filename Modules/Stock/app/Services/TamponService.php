<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Exceptions\ReferencementException;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\LigneEntree;
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
    public function saisirNumero(Entree $entree, TamponEquipement $tampon, ?string $numeroSerie): TamponEquipement
    {
        $numeroSerie = trim((string) $numeroSerie);

        if ($numeroSerie === '') {
            $tampon->update(['numero_serie' => null]); // effacement d'une rangée

            return $tampon;
        }

        $this->verifierUnicite($entree, $numeroSerie);

        $tampon->update(['numero_serie' => $numeroSerie]);

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

        return ($ids->search($tampon->id)) + 1;
    }
}
