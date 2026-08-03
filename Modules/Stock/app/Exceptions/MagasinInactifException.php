<?php

namespace Modules\Stock\Exceptions;

class MagasinInactifException extends StockException
{
    public static function pour(string $magasin): self
    {
        return new self("Le magasin {$magasin} est désactivé : aucun mouvement possible.");
    }
}
