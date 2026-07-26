<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Achat\Models\Article;

class StockLot extends Model
{
    protected $table = 'stock_lots';

    protected $fillable = [
        'article_id',
        'magasin_id',
        'quantite_initiale',
        'quantite_restante',
        'cout_unitaire',
        'date_entree',
        'mouvement_id',
    ];

    protected $casts = [
        'quantite_initiale' => 'integer',
        'quantite_restante' => 'integer',
        'cout_unitaire' => 'decimal:4',
        'date_entree' => 'date',
    ];

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(StockMouvement::class, 'mouvement_id');
    }
}
