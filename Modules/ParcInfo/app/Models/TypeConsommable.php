<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class TypeConsommable extends Model
{
    protected $table = 'parc_info_types_consommables';

    protected $fillable = [
        'code',
        'nom',
        'categorie',
        'sous_categorie',
        'unite_stock',
        'seul_reapprovisionnement',
        'duree_conservation_jours',
        'description',
    ];

    protected $casts = [
        'seul_reapprovisionnement' => 'integer',
        'duree_conservation_jours' => 'integer',
    ];

    public function consommables()
    {
        return $this->hasMany(Consommable::class);
    }
}
