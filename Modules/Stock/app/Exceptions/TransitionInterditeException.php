<?php

namespace Modules\Stock\Exceptions;

/**
 * S3 — transition de la machine à états refusée. 409 : l'état du document
 * ne permet pas l'opération (PUT/DELETE sur VALIDE, etc.).
 */
class TransitionInterditeException extends StockException
{
    public function status(): int
    {
        return 409;
    }

    public static function pour(string $document, string $depuis, string $vers): self
    {
        return new self("Transition interdite pour {$document} : {$depuis} → {$vers}.");
    }
}
