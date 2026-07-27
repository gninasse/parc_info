<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;

/**
 * Photographie de valorisation (F7) — immuable après création (RG-F7-03).
 */
class StockSnapshot extends Model
{
    protected $table = 'stock_snapshots';

    protected $fillable = [
        'reference',
        'type',
        'date_snapshot',
        'valeur_totale_globale',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_snapshot' => 'datetime',
            'valeur_totale_globale' => 'decimal:2',
        ];
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(StockSnapshotLigne::class, 'snapshot_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
