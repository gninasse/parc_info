<?php

namespace Modules\Achat\Exceptions;

use RuntimeException;

/**
 * Base des exceptions métier du module : levées par les services et les
 * modèles, transformées en réponse HTTP par les contrôleurs (422 par défaut).
 */
abstract class AchatException extends RuntimeException
{
    public function status(): int
    {
        return 422;
    }
}
