<?php

namespace Modules\Stock\Exceptions;

/**
 * Refus liés au référencement (D13) : messages exacts UX §3.3, 422.
 */
class ReferencementException extends StockException
{
    public static function aucuneLigneModele(): self
    {
        return new self('Aucune ligne « modèle × N » : ce bon se valide directement, sans référencement.');
    }

    public static function dejaSaisiLigne(int $rang): self
    {
        return new self("Déjà saisi ligne {$rang}");
    }

    public static function dejaSaisiAutreBon(): self
    {
        return new self('Déjà saisi dans un autre bon non validé');
    }

    public static function existeDeja(string $codeInventaire): self
    {
        return new self("Existe déjà ({$codeInventaire}) — utilisez le rattachement");
    }
}
