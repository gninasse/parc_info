<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class TypeReseau extends Model
{
    protected $table = 'parc_info_types_reseaux';

    protected $fillable = ['libelle'];
}
