<?php

namespace Modules\Stock\Contracts;

/**
 * Contrat d'intégration vers le module GRH.
 *
 * PATTERNS §11 — Employés utilisés comme responsables de magasin et comme
 * cibles d'affectation (type EMPLOYE) lors des sorties.
 */
interface GrhIntegrationInterface
{
    /**
     * Employés actifs, pour les listes déroulantes.
     *
     * @return list<array{id:int,matricule:string,nom_complet:string}>
     */
    public function employesActifs(): array;

    public function employeExiste(int $employeId): bool;

    /** Libellé court d'un employé (matricule + nom), ou null. */
    public function libelleEmploye(int $employeId): ?string;
}
