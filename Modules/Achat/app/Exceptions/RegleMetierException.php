<?php

namespace Modules\Achat\Exceptions;

use Exception;

/**
 * Violation d'une règle de gestion métier.
 *
 * PATTERNS §5 — Distinguée d'une erreur technique : le message est destiné à
 * l'utilisateur et la réponse HTTP est 422, jamais 500. Les contrôleurs
 * n'exposent le message brut d'une exception que pour ce type.
 */
class RegleMetierException extends Exception
{
    protected int $statut = 422;

    public function statut(): int
    {
        return $this->statut;
    }
}
