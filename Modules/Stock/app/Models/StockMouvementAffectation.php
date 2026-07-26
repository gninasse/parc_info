<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
