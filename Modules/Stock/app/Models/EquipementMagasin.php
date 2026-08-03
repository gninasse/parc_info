<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

/**
 * Rattachement courant d'une unité à un magasin (SFD §6.2) — une ligne au
 * plus par équipement ; l'historique des passages est dans les mouvements.
 */
class EquipementMagasin extends Model
{
    use HasFactory, JournaliseActiviteStock;

    protected $table = 'stock_equipements_magasins';

    public $timestamps = false; // schéma SFD : date_rattachement seule

    protected $fillable = [
        'equipement_id',
        'magasin_id',
        'date_rattachement',
    ];

    protected $casts = [
        'date_rattachement' => 'datetime',
    ];

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class);
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function scopeDuMagasin(Builder $query, int $magasinId): Builder
    {
        return $query->where('magasin_id', $magasinId);
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\EquipementMagasinFactory::new();
    }
}
