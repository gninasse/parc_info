<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Stock\Traits\GeneratesDocumentNumbers;
use Modules\Stock\Traits\HasAuditFields;

class StockInventaire extends Model
{
    use GeneratesDocumentNumbers, HasAuditFields, SoftDeletes;

    protected $table = 'stock_inventaires';

    protected $fillable = [
        'numero_inventaire',
        'magasin_id',
        'date_inventaire',
        'statut',
        'nombre_articles',
        'nombre_ecarts',
        'valide_par',
        'date_cloture',
    ];

    protected $casts = [
        'date_inventaire' => 'date',
        'date_cloture' => 'datetime',
        'nombre_articles' => 'integer',
        'nombre_ecarts' => 'integer',
    ];

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\User::class, 'valide_par');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(StockInventaireLigne::class, 'inventaire_id');
    }
}
