<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class TypeImprimante extends Model
{
    protected $table = 'parc_info_types_imprimantes';

    protected $fillable = ['libelle'];
}
