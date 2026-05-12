<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class TypeRam extends Model
{
    protected $table = 'parc_info_types_rams';

    protected $fillable = ['libelle'];
}
