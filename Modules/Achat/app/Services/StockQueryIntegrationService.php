<?php

namespace Modules\Achat\Services;

use Modules\Achat\Contracts\StockQueryInterface;

/**
 * Lecture du stock physique via la façade du module Stock (EF-STK-05).
 *
 * Seul point de lecture du module Achat vers Stock ; retourne des quantités
 * nulles quand le module Stock est absent ou désactivé.
 */
class StockQueryIntegrationService implements StockQueryInterface
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

        return $this->facade()->quantitesParArticles($articleIds);
    }

    public function valorisationParArticles(array $articleIds): array
    {
        if (! $this->estDisponible()) {
            return [];
        }

        return $this->facade()->valorisationParArticles($articleIds);
    }

    protected function facade(): \Modules\Stock\Contracts\StockQueryInterface
    {
        return app(\Modules\Stock\Contracts\StockQueryInterface::class);
    }
}
