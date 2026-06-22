<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dictionnaire extends Model
{
    protected $table = 'parc_info_dictionnaires';

    protected $fillable = [
        'code',
        'libelle',
        'description',
    ];

    /**
     * Get values associated with the dictionary.
     */
    public function valeurs(): HasMany
    {
        return $this->hasMany(DictionnaireValeur::class, 'dictionnaire_id');
    }
}
