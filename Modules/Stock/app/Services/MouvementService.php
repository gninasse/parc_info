<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Exceptions\ArticleInactifException;
use Modules\Stock\Exceptions\ArticleNonStockableException;
use Modules\Stock\Exceptions\ContreMouvementInterditException;
use Modules\Stock\Exceptions\MagasinInactifException;
use Modules\Stock\Exceptions\MotifRequisException;
use Modules\Stock\Exceptions\NiveauNegatifException;
use Modules\Stock\Exceptions\QuantiteInvalideException;
use Modules\Stock\Exceptions\StockInsuffisantException;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Niveau;

/**
 * UNIQUE point d'écriture des mouvements et des niveaux (S2).
 *
 * Chaque méthode publique ouvre une transaction (DB::transaction se joint à
 * la transaction appelante par savepoint : la validation d'un document reste
 * atomique), verrouille les lignes de stock_niveaux concernées
 * (lockForUpdate — D9), applique les gardes transverses du SFD §7.0 et
 * lève des exceptions métier typées, transformées en 422 par les contrôleurs.
 *
 * Les contrôleurs de documents n'écrivent JAMAIS un mouvement directement.
 *
 * $attributs communs : magasin_id, article_id XOR equipement_id, quantite,
 * cout_unitaire?, motif?, created_by?, et la FK de document
 * (entree_id | sortie_id | transfert_id | inventaire_id).
 */
class MouvementService
{
    /** Mouvement ENTREE (+1) : réception ou retour — l'article doit être actif. */
    public function entree(array $attributs): Mouvement
    {
        return DB::transaction(fn () => $this->ecrire(
            $attributs,
            Mouvement::TYPE_ENTREE,
            Mouvement::SENS_ENTREE,
            exigerArticleActif: true
        ));
    }

    /** Mouvement SORTIE (−1) : le niveau résultant doit rester ≥ 0. */
    public function sortie(array $attributs): Mouvement
    {
        return DB::transaction(fn () => $this->ecrire(
            $attributs,
            Mouvement::TYPE_SORTIE,
            Mouvement::SENS_SORTIE
        ));
    }

    /**
     * Paire atomique TRANSFERT_SORTIE + TRANSFERT_ENTREE (§7.3) — jamais
     * l'une sans l'autre. $attributs : magasin_source_id, magasin_cible_id,
     * transfert_id, article_id XOR equipement_id, quantite…
     *
     * Un article désactivé reste transférable : le transfert n'est pas une
     * acquisition, bloquer stranderait le stock existant (garde « article
     * actif » limitée au type ENTREE, SFD §7.0).
     *
     * @return array{sortie: Mouvement, entree: Mouvement}
     */
    public function transfert(array $attributs): array
    {
        return DB::transaction(function () use ($attributs) {
            $commun = collect($attributs)->except(['magasin_source_id', 'magasin_cible_id'])->all();

            $mouvementSortie = $this->ecrire(
                array_merge($commun, ['magasin_id' => $attributs['magasin_source_id']]),
                Mouvement::TYPE_TRANSFERT_SORTIE,
                Mouvement::SENS_SORTIE
            );

            $mouvementEntree = $this->ecrire(
                array_merge($commun, ['magasin_id' => $attributs['magasin_cible_id']]),
                Mouvement::TYPE_TRANSFERT_ENTREE,
                Mouvement::SENS_ENTREE
            );

            return ['sortie' => $mouvementSortie, 'entree' => $mouvementEntree];
        });
    }

    /**
     * AJUSTEMENT (±1 selon $attributs['sens']) : inventaire (inventaire_id)
     * ou correctif — motif obligatoire (§7.0).
     */
    public function ajustement(array $attributs): Mouvement
    {
        if (blank($attributs['motif'] ?? null)) {
            throw MotifRequisException::pourAjustement();
        }

        $sens = (int) ($attributs['sens'] ?? Mouvement::SENS_ENTREE);

        return DB::transaction(fn () => $this->ecrire(
            $attributs,
            Mouvement::TYPE_AJUSTEMENT,
            $sens
        ));
    }

    /**
     * Contre-mouvement (§7.6) : mouvement inverse motivé référençant
     * l'original, autoporté (aucune FK de document). Niveau résultant ≥ 0
     * exigé (I7). Les mouvements d'équipements passent par le workflow de
     * retour (ParcInfo doit rester cohérent) ; un contre-mouvement ne se
     * contre-mouvemente pas.
     */
    public function contreMouvement(Mouvement $origine, string $motif, ?int $createdBy = null): Mouvement
    {
        if (blank($motif)) {
            throw MotifRequisException::pourAjustement();
        }

        if ($origine->equipement_id !== null) {
            throw ContreMouvementInterditException::surEquipement();
        }

        if ($origine->estContreMouvement()) {
            throw ContreMouvementInterditException::surContreMouvement();
        }

        return DB::transaction(fn () => $this->ecrire(
            [
                'magasin_id' => $origine->magasin_id,
                'article_id' => $origine->article_id,
                'quantite' => (float) $origine->quantite,
                'cout_unitaire' => $origine->cout_unitaire,
                'mouvement_origine_id' => $origine->id,
                'motif' => $motif,
                'created_by' => $createdBy,
            ],
            Mouvement::TYPE_AJUSTEMENT,
            -1 * (int) $origine->sens,
            contreMouvement: true
        ));
    }

    /**
     * Écriture centrale : gardes transverses, verrou du niveau, mouvement
     * + niveau dans la même transaction.
     */
    private function ecrire(
        array $attributs,
        string $type,
        int $sens,
        bool $exigerArticleActif = false,
        bool $contreMouvement = false
    ): Mouvement {
        $quantite = (float) ($attributs['quantite'] ?? 0);
        $articleId = $attributs['article_id'] ?? null;
        $equipementId = $attributs['equipement_id'] ?? null;

        // ── Gardes transverses (§7.0) ──────────────────────────────────────
        if ($quantite <= 0) {
            throw QuantiteInvalideException::pour($quantite);
        }

        if ($equipementId !== null && (float) $quantite !== 1.0) {
            throw QuantiteInvalideException::uniteNonUnitaire($quantite);
        }

        $magasin = Magasin::query()->findOrFail($attributs['magasin_id']);

        if (! $magasin->est_actif) {
            throw MagasinInactifException::pour($magasin->code);
        }

        $article = null;

        if ($articleId !== null) {
            $article = Article::query()->findOrFail($articleId);

            if (! $article->est_stockable) {
                throw ArticleNonStockableException::pour($article->nom);
            }

            if ($exigerArticleActif && ! $article->est_actif) {
                throw ArticleInactifException::pour($article->nom);
            }
        } else {
            Equipement::query()->findOrFail($equipementId);
        }

        // ── Niveau sous verrou (articles quantitatifs — D9) ────────────────
        if ($article !== null) {
            $niveau = $this->niveauVerrouille($magasin->id, $article->id);
            $resultant = (float) $niveau->quantite + $sens * $quantite;

            if ($resultant < 0) {
                throw $contreMouvement || $type === Mouvement::TYPE_AJUSTEMENT
                    ? NiveauNegatifException::pour($article->nom, $magasin->code, $resultant)
                    : StockInsuffisantException::pour($article->nom, $magasin->code, $quantite, (float) $niveau->quantite);
            }

            $niveau->update(['quantite' => $resultant]);
        }

        // ── Journal (insertion seule — S1) ─────────────────────────────────
        return Mouvement::create([
            'entree_id' => $attributs['entree_id'] ?? null,
            'sortie_id' => $attributs['sortie_id'] ?? null,
            'transfert_id' => $attributs['transfert_id'] ?? null,
            'inventaire_id' => $attributs['inventaire_id'] ?? null,
            'mouvement_origine_id' => $attributs['mouvement_origine_id'] ?? null,
            'magasin_id' => $magasin->id,
            'type' => $type,
            'sens' => $sens,
            'article_id' => $articleId,
            'equipement_id' => $equipementId,
            'quantite' => $quantite,
            'cout_unitaire' => $attributs['cout_unitaire'] ?? null,
            'affectation_equipement_id' => $attributs['affectation_equipement_id'] ?? null,
            'motif' => $attributs['motif'] ?? null,
            'created_by' => $attributs['created_by'] ?? auth()->id(),
            'created_at' => now(),
        ]);
    }

    /**
     * Ligne de niveau verrouillée (lockForUpdate), créée à 0 au premier
     * mouvement. La création concurrente est absorbée par l'unicité
     * (magasin_id, article_id) : en cas de doublon on reprend le verrou.
     */
    private function niveauVerrouille(int $magasinId, int $articleId): Niveau
    {
        $niveau = Niveau::query()
            ->where('magasin_id', $magasinId)
            ->where('article_id', $articleId)
            ->lockForUpdate()
            ->first();

        if ($niveau !== null) {
            return $niveau;
        }

        try {
            Niveau::query()->create([
                'magasin_id' => $magasinId,
                'article_id' => $articleId,
                'quantite' => 0,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // un concurrent vient de créer la ligne : on la verrouille ci-dessous
        }

        return Niveau::query()
            ->where('magasin_id', $magasinId)
            ->where('article_id', $articleId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
