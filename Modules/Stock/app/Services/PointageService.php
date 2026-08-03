<?php

namespace Modules\Stock\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Exceptions\PointageException;
use Modules\Stock\Exceptions\TransitionInterditeException;
use Modules\Stock\Models\Contracts\DocumentAPointage;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\TamponEquipement;

/**
 * Pointage d'unités (D14/D17 — I17), document-agnostique : sorties ET
 * transferts passent par ici via le contrat DocumentAPointage — le service
 * ne connaît que le magasin source et la colonne de tampon.
 *
 * Contrairement au référencement des entrées (rangées pré-créées à remplir),
 * le pointage AJOUTE une rangée de tampon par unité choisie, dans la limite
 * de la quantité de la ligne. Le scan express (brouillon) crée une ligne
 * « modèle × 1 » pré-pointée.
 */
class PointageService
{
    /**
     * BROUILLON → POINTAGE. Les tampons de pré-pointage (scan express)
     * survivent à la transition.
     */
    public function passerEnPointage(DocumentAPointage&Model $document): void
    {
        if ($document->statut !== 'BROUILLON') {
            throw TransitionInterditeException::pour($document->numero_affiche, $document->statut, 'POINTAGE');
        }

        if ($this->lignesModeles($document)->isEmpty()) {
            throw PointageException::aucuneLigneModele();
        }

        DB::transaction(function () use ($document) {
            $document->passerEnPointage();

            activity('stock')
                ->performedOn($document)
                ->log('passage_pointage');
        });
    }

    /**
     * Pointe une unité sur une ligne « modèle × N » (I17) : unité du magasin
     * source, « en stock », du bon modèle, pas déjà pointée (dans ce bon ou
     * un autre bon non validé — l'unicité DB du tampon fait le filet).
     */
    public function pointer(DocumentAPointage&Model $document, Model $ligne, int $equipementId): TamponEquipement
    {
        $equipement = Equipement::query()->find($equipementId);

        if ($equipement === null) {
            throw PointageException::introuvableDansCeMagasin();
        }

        $this->verifierUnitePointable($document, $equipement);

        if ($ligne->article === null || $ligne->article->nature !== Article::NATURE_EQUIPEMENT) {
            throw PointageException::modeleDifferent($equipement->code_inventaire);
        }

        if ($equipement->categorie_id !== null
            && $ligne->article->categorie_equipement_id !== null
            && (int) $equipement->categorie_id !== (int) $ligne->article->categorie_equipement_id) {
            throw PointageException::modeleDifferent($equipement->code_inventaire);
        }

        if ($ligne->tampons()->count() >= (int) $ligne->quantite) {
            throw PointageException::ligneComplete((int) $ligne->quantite);
        }

        return TamponEquipement::create([
            $document->colonneTamponLigne() => $ligne->id,
            'equipement_id' => $equipement->id,
        ]);
    }

    public function depointer(DocumentAPointage&Model $document, int $tamponId): void
    {
        $this->tamponsDuDocument($document)->where('id', $tamponId)->delete();
    }

    /**
     * Scan express (D17, brouillons) : un scan = ligne « modèle × 1 » créée
     * et pré-pointée. L'article est résolu depuis l'unité (catégorie +
     * modèle) ; s'il n'existe pas au catalogue → erreur explicite, la ligne
     * s'ajoute manuellement. Messages exacts UX §0.5.
     */
    public function scanExpress(DocumentAPointage&Model $document, string $numeroSerie): array
    {
        $equipement = Equipement::query()->where('numero_serie', trim($numeroSerie))->first();

        if ($equipement === null) {
            throw PointageException::introuvableDansCeMagasin();
        }

        $this->verifierUnitePointable($document, $equipement);

        $article = Article::query()
            ->where('nature', Article::NATURE_EQUIPEMENT)
            ->where('categorie_equipement_id', $equipement->categorie_id)
            ->where(function ($q) use ($equipement) {
                $q->where('modele', $equipement->modele)->orWhereNull('modele');
            })
            ->orderByRaw('CASE WHEN modele IS NULL THEN 1 ELSE 0 END')
            ->first();

        if ($article === null) {
            throw PointageException::aucunArticleCorrespondant($equipement->modele ?? '—');
        }

        return DB::transaction(function () use ($document, $article, $equipement) {
            $ligne = $document->lignes()->create([
                'article_id' => $article->id,
                'quantite' => 1,
            ]);

            $tampon = TamponEquipement::create([
                $document->colonneTamponLigne() => $ligne->id,
                'equipement_id' => $equipement->id,
            ]);

            return [
                'ligne' => $ligne->load('article:id,code,nom,nature'),
                'tampon' => $tampon,
                'equipement' => $equipement->only(['id', 'code_inventaire', 'numero_serie', 'modele']),
            ];
        });
    }

    /** Retour au brouillon : tampon purgé, lignes et quantités conservées (D14). */
    public function retourBrouillon(DocumentAPointage&Model $document): int
    {
        return DB::transaction(function () use ($document) {
            $document->retourBrouillon();

            $tampons = $this->tamponsDuDocument($document);
            $pointees = $tampons->count();
            $tampons->delete();

            activity('stock')
                ->performedOn($document)
                ->withProperties(['unites_depointees' => $pointees])
                ->log('retour_brouillon');

            return $pointees;
        });
    }

    /** Progression : [pointées, attendues] sur les lignes modèle × N. */
    public function progression(DocumentAPointage&Model $document): array
    {
        $lignes = $this->lignesModeles($document);

        return [
            (int) $lignes->sum(fn ($ligne) => $ligne->tampons->count()),
            (int) $lignes->sum('quantite'),
        ];
    }

    /**
     * Précondition de validation (I17) : N unités pointées par ligne
     * modèle × N, unités toujours pointables (re-contrôle sous transaction).
     */
    public function verifierPointageComplet(DocumentAPointage&Model $document): void
    {
        $manquantes = 0;

        foreach ($this->lignesModeles($document) as $ligne) {
            $manquantes += max(0, (int) $ligne->quantite - $ligne->tampons->count());
        }

        if ($manquantes > 0) {
            throw PointageException::pointageIncomplet($manquantes);
        }

        foreach ($this->lignesModeles($document) as $ligne) {
            foreach ($ligne->tampons as $tampon) {
                $this->verifierUnitePointable($document, $tampon->equipement, ignorerTamponId: $tampon->id);
            }
        }
    }

    public function lignesModeles(DocumentAPointage&Model $document)
    {
        return $document->lignes()
            ->whereHas('article', fn ($q) => $q->where('nature', Article::NATURE_EQUIPEMENT))
            ->with(['article:id,code,nom,nature,modele,categorie_equipement_id', 'tampons.equipement:id,code_inventaire,numero_serie,modele,statut,categorie_id'])
            ->orderBy('id')
            ->get();
    }

    public function tamponsDuDocument(DocumentAPointage&Model $document)
    {
        return TamponEquipement::query()
            ->whereIn($document->colonneTamponLigne(), $document->lignes()->select('id'));
    }

    /** Gardes communes I17 : au magasin source, « en stock », pas déjà pointée. */
    private function verifierUnitePointable(DocumentAPointage&Model $document, Equipement $equipement, ?int $ignorerTamponId = null): void
    {
        $rattachement = EquipementMagasin::query()->where('equipement_id', $equipement->id)->first();

        if ($rattachement === null || (int) $rattachement->magasin_id !== $document->magasinSourceId()) {
            throw PointageException::introuvableDansCeMagasin();
        }

        if (! str_starts_with((string) $equipement->statut, 'en_stock')) {
            throw PointageException::nonEnStock();
        }

        // Déjà pointée dans CE bon ou dans un autre bon non validé (tampon global)
        $dejaPointee = TamponEquipement::query()
            ->where('equipement_id', $equipement->id)
            ->when($ignorerTamponId !== null, fn ($q) => $q->where('id', '!=', $ignorerTamponId))
            ->exists();

        if ($dejaPointee) {
            throw PointageException::dejaPointe();
        }
    }
}
