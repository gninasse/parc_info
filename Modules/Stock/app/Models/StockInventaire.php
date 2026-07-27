<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\User;
use Modules\Stock\Traits\HasAuditFields;

/**
 * Campagne d'inventaire (F6) — EN_COURS → CLOTURE | ANNULE.
 */
class StockInventaire extends Model
{
    use HasAuditFields, SoftDeletes;

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

    protected function casts(): array
    {
        return [
            'date_inventaire' => 'date',
            'date_cloture' => 'datetime',
            'nombre_articles' => 'integer',
            'nombre_ecarts' => 'integer',
        ];
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(StockInventaireLigne::class, 'inventaire_id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function estEnCours(): bool
    {
        return $this->statut === 'EN_COURS';
    }

    public function getStatutLabelAttribute(): string
    {
        return config("stock.statuts_inventaire.{$this->statut}.label", $this->statut);
    }

    public function getStatutColorAttribute(): string
    {
        return config("stock.statuts_inventaire.{$this->statut}.color", 'secondary');
    }
}
