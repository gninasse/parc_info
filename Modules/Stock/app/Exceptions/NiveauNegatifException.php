<?php

namespace Modules\Stock\Exceptions;

class NiveauNegatifException extends StockException
{
    public static function pour(string $article, string $magasin, float $resultant): self
    {
        return new self(
            "Opération refusée : le niveau de « {$article} » au magasin {$magasin} "
            .'deviendrait négatif ('.rtrim(rtrim(number_format($resultant, 2, ',', ' '), '0'), ',').').'
        );
    }
}
