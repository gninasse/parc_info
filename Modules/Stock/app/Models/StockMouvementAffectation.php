<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trace de l'affectation ParcInfo créée lors d'une sortie de stock (F4).
 */
class StockMouvementAffectation extends Model
{
    protected $table = 'stock_mouvements_affectations';

    protected $fillable = [
        'mouvement_id',
        'type_affectation_parcinfo',
        'affectation_id',
        'type_cible',
        'cible_id',
    ];

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(StockMouvement::class, 'mouvement_id');
    }
}
