<?php

namespace Modules\Stock\Services;

use Modules\Stock\Contracts\ParcInfoIntegrationInterface;
use Modules\Stock\Exceptions\RegleMetierException;

/**
 * Seul point de contact du module Stock avec les modèles ParcInfo.
 *
 * Implémenté en phase P4 (sorties de stock) : les affectations sont créées
 * dans la transaction de la sortie, jamais par accès direct aux modèles
 * ParcInfo depuis les autres services.
 */
class ParcInfoIntegrationService implements ParcInfoIntegrationInterface
{
    public function affecterEquipement(array $donnees): int
    {
        throw new RegleMetierException('Les sorties vers ParcInfo seront disponibles avec la fonctionnalité Sorties (P4).');
    }

    public function tracerConsommation(array $donnees): int
    {
        throw new RegleMetierException('Les sorties vers ParcInfo seront disponibles avec la fonctionnalité Sorties (P4).');
    }

    public function affecterLicence(array $donnees): int
    {
        throw new RegleMetierException('Les sorties vers ParcInfo seront disponibles avec la fonctionnalité Sorties (P4).');
    }
}
