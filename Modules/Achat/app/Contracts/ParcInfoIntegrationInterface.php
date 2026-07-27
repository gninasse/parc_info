<?php

namespace Modules\Achat\Contracts;

/**
 * Contrat d'intégration vers le module ParcInfo.
 *
 * PATTERNS §11 — Aucun service du module Achat n'appelle directement un modèle
 * d'un autre module : tout passe par cette interface, ce qui permet de simuler
 * ParcInfo en test et de circonscrire l'impact d'une évolution de son schéma.
 */
interface ParcInfoIntegrationInterface
{
    /** RG-INT-03 — Le numéro de série est-il déjà porté par un équipement du parc ? */
    public function existeNumeroSerie(string $numeroSerie): bool;

    /** RG-INT-04 — Le code inventaire est-il déjà attribué dans le parc ? */
    public function existeCodeInventaire(string $codeInventaire): bool;

    /**
     * RG-INT-05 — Crée une fiche équipement à partir d'une unité réceptionnée.
     *
     * @param  array{categorie_id:int,code_inventaire:string,numero_serie:string,marque_id:int,modele:string,date_acquisition:mixed,valeur_achat:float,ref_bordereau:string,champs_valeurs:array}  $donnees
     * @return int Identifiant de l'équipement créé
     */
    public function creerEquipement(array $donnees): int;

    /** RG-INT-06 — Historise l'acquisition d'un équipement. */
    public function historiserAcquisition(int $equipementId, int $userId, string $motif, string $referenceDocument): void;

    /** RG-INT-07 — Retourne l'identifiant du logiciel, en le créant au besoin. */
    public function trouverOuCreerLogiciel(string $nom, string $code): int;

    /**
     * RG-INT-07 — Crée une licence logicielle.
     *
     * @return int Identifiant de la licence créée
     */
    public function creerLicence(array $donnees): int;

    /** Champs personnalisés définis pour une catégorie d'équipement. */
    public function champsDeCategorie(?int $categorieId): iterable;

    /**
     * ENF-TRA-04 — Équipements du parc rattachés à des bordereaux de livraison.
     *
     * Retourne des tableaux plats prêts pour l'affichage, jamais de modèles
     * ParcInfo, afin que le schéma de ParcInfo reste confiné à ce contrat.
     *
     * @param  list<string>  $refsBordereaux  Numéros de livraison (ref_bordereau)
     * @return list<array{id:int,code_inventaire:string,categorie_libelle:?string,categorie_icone:?string,marque_libelle:?string,modele:?string,numero_serie:?string,statut:string,etat:string,detail_route:string}>
     */
    public function equipementsDesBordereaux(array $refsBordereaux): array;
}
