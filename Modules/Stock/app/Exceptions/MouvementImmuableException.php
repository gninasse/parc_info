<?php

namespace Modules\Stock\Exceptions;

use LogicException;

/**
 * S1 — le journal stock_mouvements est en insertion seule. Cette exception
 * signale une erreur de programmation (tentative d'UPDATE/DELETE), pas un
 * cas métier : elle ne doit jamais être rattrapée pour continuer.
 */
class MouvementImmuableException extends LogicException
{
    public static function creer(): self
    {
        return new self('Le journal stock_mouvements est en insertion seule : les corrections se font par contre-mouvement (S1).');
    }
}
