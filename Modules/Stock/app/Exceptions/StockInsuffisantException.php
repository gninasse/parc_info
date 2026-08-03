<?php

namespace Modules\Stock\Exceptions;

class StockInsuffisantException extends StockException
{
    public static function pour(string $article, string $magasin, float $demande, float $disponible): self
    {
        return new self(
            "Stock insuffisant pour « {$article} » au magasin {$magasin} : "
            .'demandé '.rtrim(rtrim(number_format($demande, 2, ',', ' '), '0'), ',')
            .', disponible '.rtrim(rtrim(number_format($disponible, 2, ',', ' '), '0'), ',').'.'
        );
    }
}
