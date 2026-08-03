<?php

namespace Modules\Stock\Exceptions;

/**
 * Refus de validation d'une sortie (I15/I17) — 422, statut inchangé.
 * Les erreurs de disponible sont listées LIGNE PAR LIGNE (UX §0.6).
 */
class ValidationSortieException extends StockException
{
    /** @var array<int, string> messages par index de ligne (lignes.N.quantite) */
    public array $erreursLignes = [];

    public static function sansLigne(): self
    {
        return new self('Ce bon ne contient aucune ligne : rien à valider.');
    }

    public static function disponibleInsuffisant(array $erreursLignes): self
    {
        $exception = new self(
            'Disponible insuffisant : '.implode(' ; ', $erreursLignes).'.'
        );
        $exception->erreursLignes = $erreursLignes;

        return $exception;
    }

    public static function remisAManquant(): self
    {
        return new self('« Remis à » est obligatoire quand le bon contient des équipements.');
    }
}
