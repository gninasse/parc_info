<?php

namespace Modules\Achat\Contracts;

use Modules\Achat\Models\BordereauLivraison;

/**
 * Contrat d'intégration vers le module Stock.
 *
 * EF-STK-05 — Le module Stock est le référentiel de quantité et de
 * valorisation faisant foi. Seuls les articles dont le type alimente le stock
 * physique (config achat.types_avec_stock) y sont enregistrés : les
 * équipements et les licences, qui donnent lieu à des fiches individuelles
 * dans ParcInfo, en sont exclus.
 */
interface StockIntegrationInterface
{
    /**
     * Enregistre les entrées de stock d'un bordereau validé.
     *
     * @return int Nombre de lignes effectivement enregistrées
     */
    public function enregistrerEntreesDepuisBordereau(BordereauLivraison $bordereau, int $userId): int;

    /** Le module Stock est-il disponible et activé pour l'intégration ? */
    public function estDisponible(): bool;
}
