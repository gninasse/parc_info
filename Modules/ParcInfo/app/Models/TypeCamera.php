<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class TypeCamera extends Model
{
    protected $table = 'parc_info_types_cameras';
    protected $fillable = ['libelle'];
}
