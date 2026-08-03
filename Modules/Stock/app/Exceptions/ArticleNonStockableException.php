<?php

namespace Modules\Stock\Exceptions;

class ArticleNonStockableException extends StockException
{
    public static function pour(string $article): self
    {
        return new self("L'article « {$article} » n'est pas stockable : aucun mouvement de stock possible.");
    }
}
