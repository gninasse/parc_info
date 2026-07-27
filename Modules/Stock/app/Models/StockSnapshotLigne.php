<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockSnapshotLigne extends Model
{
    protected $table = 'stock_snapshot_lignes';

    protected $fillable = [
        'snapshot_id',
        'magasin_id',
        'article_id',
        'quantite',
        'valeur_fifo',
        'cout_unitaire_moyen',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'valeur_fifo' => 'decimal:2',
            'cout_unitaire_moyen' => 'decimal:4',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(StockSnapshot::class, 'snapshot_id');
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }
}
