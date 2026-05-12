<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class TypeDisque extends Model
{
    protected $table = 'parc_info_types_disques';

    protected $fillable = ['libelle'];
}
