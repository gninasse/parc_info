<?php

namespace Modules\Stock\Contracts;

/**
 * Contrat d'intégration vers le module Achat (lecture seule).
 *
 * PATTERNS §11 — Le référentiel des articles est achat_articles ; le module
 * Stock n'en duplique rien et n'y accède que par ce contrat. Les données
 * retournées sont des tableaux plats : aucun modèle Achat ne sort du contrat.
 */
interface AchatIntegrationInterface
{
    /**
     * Articles pouvant alimenter le stock physique (config achat.types_avec_stock).
     *
     * @param  array{recherche?:string,actif?:bool}  $filtres
     * @return list<array{id:int,code_article:string,designation:string,type_article:string,unite_mesure:?string,seuil_alerte:?int,marque_libelle:?string}>
     */
    public function articlesStockables(array $filtres = []): array;

    /**
     * Fiche allégée d'un article, ou null s'il n'existe pas.
     *
     * @return ?array{id:int,code_article:string,designation:string,type_article:string,unite_mesure:?string,seuil_alerte:?int,marque_libelle:?string}
     */
    public function article(int $articleId): ?array;

    /**
     * Fiches allégées de plusieurs articles, indexées par identifiant.
     *
     * @param  list<int>  $articleIds
     * @return array<int,array{id:int,code_article:string,designation:string,type_article:string,unite_mesure:?string,seuil_alerte:?int,marque_libelle:?string}>
     */
    public function articlesParIds(array $articleIds): array;

    /** L'article existe-t-il et son type alimente-t-il le stock physique ? */
    public function estStockable(int $articleId): bool;

    /** Seuil d'alerte global de l'article (achat_articles.seuil_alerte). */
    public function seuilAlerte(int $articleId): int;
}
