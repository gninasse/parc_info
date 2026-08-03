<?php

namespace Modules\Stock\Exceptions;

use RuntimeException;

/**
 * Base des exceptions métier du module (S2) : levées par les services,
 * transformées en réponse HTTP par les contrôleurs (422 par défaut).
 */
abstract class StockException extends RuntimeException
{
    public function status(): int
    {
        return 422;
    }
}
