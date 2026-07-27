<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lot FIFO — référentiel de la valorisation (RG-F2-05, RG-F2-06).
 */
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

    protected function casts(): array
    {
        return [
            'quantite_initiale' => 'integer',
            'quantite_restante' => 'integer',
            'cout_unitaire' => 'decimal:4',
            'date_entree' => 'date',
        ];
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(StockMouvement::class, 'mouvement_id');
    }

    public function scopeDisponible(Builder $query): Builder
    {
        return $query->where('quantite_restante', '>', 0);
    }

    /** RG-F2-06 — Ordre de consommation FIFO. */
    public function scopeOrdreFifo(Builder $query): Builder
    {
        return $query->orderBy('date_entree')->orderBy('id');
    }

    public function estIntact(): bool
    {
        return $this->quantite_restante === $this->quantite_initiale;
    }
}
