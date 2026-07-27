<?php

namespace Modules\Stock\Contracts;

/**
 * Contrat d'intégration vers le module Organisation.
 *
 * PATTERNS §11 — Directions, services, unités et postes de travail utilisés
 * comme cibles d'affectation lors des sorties de stock (F4).
 */
interface OrganisationIntegrationInterface
{
    /**
     * Cibles disponibles pour un type donné (SERVICE, DIRECTION, UNITE, POSTE).
     *
     * @return list<array{id:int,libelle:string}>
     */
    public function cibles(string $typeCible): array;

    /** La cible existe-t-elle pour ce type ? */
    public function cibleExiste(string $typeCible, int $cibleId): bool;

    /** Libellé d'une cible, ou null. */
    public function libelleCible(string $typeCible, int $cibleId): ?string;
}
