<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorieEquipement extends Model
{
    protected $table = 'parc_info_categories_equipements';

    protected $fillable = [
        'code',
        'libelle',
        'icone',
    ];

    /**
     * Get fields configured for this category.
     */
    public function champs(): HasMany
    {
        return $this->hasMany(ChampConfig::class, 'categorie_id')->orderBy('ordre_affichage');
    }

    /**
     * Get equipments belonging to this category.
     */
    public function equipements(): HasMany
    {
        return $this->hasMany(Equipement::class, 'categorie_id');
    }
}
