<?php

namespace Modules\Achat\Services;

use Modules\Achat\Contracts\StockIntegrationInterface;
use Modules\Achat\Models\BordereauLivraison;

/**
 * Seul point de contact du module Achat avec le module Stock.
 *
 * Implémentation neutre transitoire : le module Stock v1 a été supprimé et
 * sera redéveloppé (voir plan de refactoring, phase P3). Tant que Stock v2
 * n'expose pas son service d'entrée, aucune ligne n'est enregistrée en stock ;
 * la validation des bordereaux reste fonctionnelle (RG inchangées côté Achat).
 *
 * TODO(P3) : déléguer à EntreeStockService du module Stock v2 (type_origine BL,
 * coût = prix de la ligne de commande, magasin de réception paramétré).
 */
class StockIntegrationService implements StockIntegrationInterface
{
    public function estDisponible(): bool
    {
        return false;
    }

    public function enregistrerEntreesDepuisBordereau(BordereauLivraison $bordereau, int $userId): int
    {
        return 0;
    }
}
