<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Marque extends Model
{
    protected $table = 'parc_info_marques';

    protected $fillable = ['libelle'];

    public function equipements()
    {
        return $this->hasMany(Equipement::class, 'marque_id');
    }
}
