<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class TypeInfrastructure extends Model
{
    protected $table = 'parc_info_types_infrastructures';

    protected $fillable = ['libelle'];
}
