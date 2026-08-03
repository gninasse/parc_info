<?php

namespace Modules\Stock\Exceptions;

/**
 * Refus liés au référencement (D13) : messages exacts UX §3.3, 422.
 */
class ReferencementException extends StockException
{
    public const CATEGORIE_DOUBLON_TAMPON = 'doublon_tampon';

    public const CATEGORIE_DEJA_CONNU = 'deja_connu';

    /** Catégorie du rapport d'import MD-IMPORT (null hors doublons). */
    public ?string $categorie = null;

    public static function aucuneLigneModele(): self
    {
        return new self('Aucune ligne « modèle × N » : ce bon se valide directement, sans référencement.');
    }

    public static function dejaSaisiLigne(int $rang): self
    {
        $exception = new self("Déjà saisi ligne {$rang}");
        $exception->categorie = self::CATEGORIE_DOUBLON_TAMPON;

        return $exception;
    }

    public static function dejaSaisiAutreBon(): self
    {
        $exception = new self('Déjà saisi dans un autre bon non validé');
        $exception->categorie = self::CATEGORIE_DOUBLON_TAMPON;

        return $exception;
    }

    public static function existeDeja(string $codeInventaire): self
    {
        $exception = new self("Existe déjà ({$codeInventaire}) — utilisez le rattachement");
        $exception->categorie = self::CATEGORIE_DEJA_CONNU;

        return $exception;
    }
}
