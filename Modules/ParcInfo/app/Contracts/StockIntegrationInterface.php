<?php

namespace Modules\ParcInfo\Contracts;

/**
 * Contrat de LECTURE du stock physique des consommables.
 *
 * EF-STK-05 — Le module Stock est le référentiel unique des quantités : la
 * fiche parc_info_consommables reste un catalogue, ses compteurs autonomes
 * sont supprimés. Le lien vers le référentiel article se fait par
 * parc_info_consommables.article_id (achat_articles).
 */
interface StockIntegrationInterface
{
    public function estDisponible(): bool;

    /**
     * Quantités disponibles, tous magasins confondus.
     *
     * @param  list<int>  $articleIds  Identifiants achat_articles
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
