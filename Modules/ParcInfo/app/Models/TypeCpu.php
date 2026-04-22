<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class TypeCpu extends Model
{
    protected $table = 'parc_info_types_cpus';

    protected $fillable = ['libelle'];
}
