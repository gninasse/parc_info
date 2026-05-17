<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class TypeLicence extends Model
{
    protected $table = 'parc_info_types_licences';

    protected $fillable = ['code', 'libelle', 'description', 'modeles'];

    protected $casts = [
        'modeles' => 'array',
    ];

    public function logiciels()
    {
        return $this->hasMany(Logiciel::class);
    }
}
