<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class TypeOs extends Model
{
    protected $table = 'parc_info_types_os';

    protected $fillable = ['libelle'];
}
