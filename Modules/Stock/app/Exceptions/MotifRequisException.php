<?php

namespace Modules\Stock\Exceptions;

class MotifRequisException extends StockException
{
    public static function pourAjustement(): self
    {
        return new self('Un motif est obligatoire pour un ajustement ou un contre-mouvement.');
    }
}
