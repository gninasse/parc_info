<?php

namespace Modules\ParcInfo\Services;

use Modules\ParcInfo\Contracts\StockIntegrationInterface;

/**
 * Seul point de lecture du module ParcInfo vers le module Stock (EF-STK-05).
 */
class StockIntegrationService implements StockIntegrationInterface
{
    public function estDisponible(): bool
    {
        return interface_exists(\Modules\Stock\Contracts\StockQueryInterface::class)
            && \Nwidart\Modules\Facades\Module::isEnabled('Stock');
    }

    public function quantitesParArticles(array $articleIds): array
    {
        if (! $this->estDisponible()) {
            return [];
        }

        return app(\Modules\Stock\Contracts\StockQueryInterface::class)
            ->quantitesParArticles($articleIds);
    }

    public function valorisationParArticles(array $articleIds): array
    {
        if (! $this->estDisponible()) {
            return [];
        }

        return app(\Modules\Stock\Contracts\StockQueryInterface::class)
            ->valorisationParArticles($articleIds);
    }
}
