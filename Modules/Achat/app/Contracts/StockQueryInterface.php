<?php

namespace Modules\Achat\Contracts;

/**
 * Contrat de LECTURE du stock physique.
 *
 * EF-STK-05 — Le module Stock (lots FIFO) est le référentiel unique des
 * quantités et de la valorisation. L'écran E-14 consomme ce contrat ; la
 * projection achat_articles.stock_actuel est supprimée.
 */
interface StockQueryInterface
{
    public function estDisponible(): bool;

    /**
     * Quantités disponibles, tous magasins confondus.
     *
     * @param  list<int>  $articleIds
     * @return array<int,int> [article_id => quantité]
     */
    public function quantitesParArticles(array $articleIds): array;

    /**
     * Valorisation FIFO par article.
     *
     * @param  list<int>  $articleIds
     * @return array<int,float> [article_id => valeur]
     */
    public function valorisationParArticles(array $articleIds): array;
}
