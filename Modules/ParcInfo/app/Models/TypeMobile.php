<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class TypeMobile extends Model
{
    protected $table = 'parc_info_types_mobiles';

    protected $fillable = ['libelle'];
}
