<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $casts = [
        'date_snapshot' => 'datetime',
        'valeur_totale_globale' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\User::class, 'created_by');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(StockSnapshotLigne::class, 'snapshot_id');
    }
}
