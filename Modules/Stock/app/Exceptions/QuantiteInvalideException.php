<?php

namespace Modules\Stock\Exceptions;

class QuantiteInvalideException extends StockException
{
    public static function pour(float $quantite): self
    {
        return new self('La quantité d\'un mouvement doit être strictement positive (reçu : '.$quantite.').');
    }

    public static function uniteNonUnitaire(float $quantite): self
    {
        return new self('Un mouvement d\'équipement porte toujours sur une unité (quantité reçue : '.$quantite.').');
    }
}
