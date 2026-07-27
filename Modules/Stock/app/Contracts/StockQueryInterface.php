<?php

namespace Modules\Stock\Contracts;

/**
 * Façade de lecture du stock, exposée aux autres modules.
 *
 * EF-STK-05 — Le module Stock est le référentiel unique des quantités et de
 * la valorisation. Les modules Achat (écran E-14) et ParcInfo (fiche
 * consommable) consomment cette façade via leur propre contrat d'intégration,
 * jamais les modèles Stock directement.
 */
interface StockQueryInterface
{
    /** Quantité disponible d'un article, tous magasins ou magasin donné. */
    public function quantiteDisponible(int $articleId, ?int $magasinId = null): int;

    /**
     * Quantités totales par article.
     *
     * @param  list<int>  $articleIds
     * @return array<int,int> [article_id => quantité totale]
     */
    public function quantitesParArticles(array $articleIds): array;

    /**
     * Valorisation FIFO par article (RG-F2-05).
     *
     * @param  list<int>  $articleIds
     * @return array<int,float> [article_id => valeur FIFO totale]
     */
    public function valorisationParArticles(array $articleIds): array;

    /**
     * Détail du stock d'un article par magasin.
     *
     * @return list<array{magasin_id:int,magasin_code:string,magasin_libelle:string,quantite:int,valeur_fifo:float,derniere_entree_at:?string,derniere_sortie_at:?string}>
     */
    public function detailParMagasin(int $articleId): array;

    /** Statut d'alerte global d'un article : OK | ALERTE | RUPTURE. */
    public function statutAlerte(int $articleId): string;
}
