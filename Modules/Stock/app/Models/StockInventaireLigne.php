<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Achat\Models\Article;

class StockInventaireLigne extends Model
{
    protected $table = 'stock_inventaire_lignes';

    protected $fillable = [
        'inventaire_id',
        'article_id',
        'quantite_theorique',
        'quantite_reelle',
        'ecart',
        'cout_unitaire_reference',
        'mouvement_id',
    ];

    protected $casts = [
        'quantite_theorique' => 'integer',
        'quantite_reelle' => 'integer',
        'ecart' => 'integer',
        'cout_unitaire_reference' => 'decimal:4',
    ];

    public function inventaire(): BelongsTo
    {
        return $this->belongsTo(StockInventaire::class, 'inventaire_id');
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
