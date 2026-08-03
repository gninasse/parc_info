<?php

namespace Modules\Stock\Exceptions;

class ArticleInactifException extends StockException
{
    public static function pour(string $article): self
    {
        return new self("L'article « {$article} » est désactivé au catalogue : plus aucune entrée possible (il reste visible en historique).");
    }
}
