<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Editeur extends Model
{
    protected $table = 'parc_info_editeurs';

    protected $fillable = [
        'code',
        'nom',
        'logo_url',
        'site_web',
        'email_support',
        'telephone_support',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    public function logiciels()
    {
        return $this->hasMany(Logiciel::class);
    }
}
