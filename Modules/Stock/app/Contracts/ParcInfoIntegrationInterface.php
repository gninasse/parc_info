<?php

namespace Modules\Stock\Contracts;

/**
 * Contrat d'intégration vers le module ParcInfo (affectations à la sortie).
 *
 * PATTERNS §11 — Toute écriture dans ParcInfo passe par ce contrat, dans la
 * transaction de la sortie (RG-F4-02/03) : si l'affectation échoue, la sortie
 * est intégralement annulée. Aucune méthode ne touche aux compteurs de
 * quantité de ParcInfo : le module Stock est le référentiel unique (EF-STK-05).
 */
interface ParcInfoIntegrationInterface
{
    /**
     * RG-F4 — Affecte un équipement du parc à une cible (affectation PERMANENTE).
     *
     * @param  array{equipement_id:int,type_cible:string,cible_id:int,motif:?string,user_id:int}  $donnees
     * @return int Identifiant de l'affectation créée
     */
    public function affecterEquipement(array $donnees): int;

    /**
     * RG-F4 — Trace la consommation d'un consommable (mouvement + affectation),
     * sans modifier de compteur de stock ParcInfo.
     *
     * @param  array{code_article:string,designation:string,quantite:int,type_cible:string,cible_id:int,motif:?string,user_id:int,reference_document:?string}  $donnees
     * @return int Identifiant du mouvement consommable créé
     */
    public function tracerConsommation(array $donnees): int;

    /**
     * RG-F4 — Affecte une licence à une cible.
     *
     * @param  array{licence_id:int,type_cible:string,cible_id:int,motif:?string,user_id:int}  $donnees
     * @return int Identifiant de l'affectation créée
     */
    public function affecterLicence(array $donnees): int;
}
