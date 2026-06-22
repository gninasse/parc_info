<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DictionnaireValeur extends Model
{
    protected $table = 'parc_info_dictionnaire_valeurs';

    protected $fillable = [
        'dictionnaire_id',
        'valeur',
        'description',
    ];

    /**
     * Get the parent dictionary.
     */
    public function dictionnaire(): BelongsTo
    {
        return $this->belongsTo(Dictionnaire::class, 'dictionnaire_id');
    }
}
