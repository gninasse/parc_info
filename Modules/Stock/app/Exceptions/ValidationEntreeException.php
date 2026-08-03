<?php

namespace Modules\Stock\Exceptions;

/**
 * Refus de validation d'une entrée (I11/I14) — 422, statut inchangé.
 */
class ValidationEntreeException extends StockException
{
    public static function sansLigne(): self
    {
        return new self('Ce bon ne contient aucune ligne : rien à valider.');
    }

    public static function tamponIncomplet(int $manquantes): self
    {
        return new self("{$manquantes} référence(s) manquante(s) : complétez la saisie des numéros de série avant de valider.");
    }

    public static function doublonsInternes(array $series): self
    {
        return new self('Numéro(s) de série en doublon dans ce bon : '.implode(', ', $series).'.');
    }

    public static function serieConnue(string $serie, string $codeInventaire): self
    {
        return new self("Le numéro de série {$serie} existe déjà dans le parc ({$codeInventaire}) — utilisez le rattachement.");
    }

    public static function uniteIndisponible(string $codeInventaire, string $raison): self
    {
        return new self("L'unité {$codeInventaire} n'est plus rattachable : {$raison}.");
    }

    public static function motifRetourRequis(): self
    {
        return new self('Un motif (observation) est obligatoire pour un retour d\'équipement.');
    }
}
