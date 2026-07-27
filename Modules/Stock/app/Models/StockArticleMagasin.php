<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock d'un article dans un magasin — projection tenue à jour dans la même
 * transaction que chaque mouvement ; les lots FIFO font foi (RG-F2-05).
 */
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

    protected function casts(): array
    {
        return [
            'quantite_actuelle' => 'integer',
            'valeur_stock_fifo' => 'decimal:2',
            'derniere_entree_at' => 'datetime',
            'derniere_sortie_at' => 'datetime',
        ];
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    /** Statut d'alerte au regard d'un seuil global (F2) : OK | ALERTE | RUPTURE. */
    public function statutAlerte(int $seuil): string
    {
        if ($this->quantite_actuelle === 0) {
            return 'RUPTURE';
        }

        return $this->quantite_actuelle <= $seuil ? 'ALERTE' : 'OK';
    }
}
