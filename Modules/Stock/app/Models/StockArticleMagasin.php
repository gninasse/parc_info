<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Achat\Models\Article;

class StockArticleMagasin extends Model
{
    protected $table = 'stock_articles_magasin';

    protected $fillable = [
        'article_id',
        'magasin_id',
        'quantite_actuelle',
        'valeur_stock_fifo',
        'derniere_entree_at',
        'derniere_sortie_at',
    ];

    protected $casts = [
        'quantite_actuelle' => 'integer',
        'valeur_stock_fifo' => 'decimal:2',
        'derniere_entree_at' => 'datetime',
        'derniere_sortie_at' => 'datetime',
    ];

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
