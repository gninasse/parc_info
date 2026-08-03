<?php

namespace Modules\Stock\Exceptions;

class ContreMouvementInterditException extends StockException
{
    public static function surEquipement(): self
    {
        return new self(
            'Un mouvement d\'équipement ne se contre-mouvemente pas directement : '
            .'passez par le workflow de retour pour garder ParcInfo cohérent (SFD §7.6).'
        );
    }

    public static function surContreMouvement(): self
    {
        return new self('Un contre-mouvement ne peut pas être lui-même contre-mouvementé : créez un mouvement correctif depuis l\'original.');
    }
}
