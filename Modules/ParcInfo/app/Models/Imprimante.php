<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Imprimante extends Model
{
    protected $table = 'parc_info_imprimantes';

    public $timestamps = false;

    protected $primaryKey = 'equipement_id';

    public $incrementing = false;

    protected $fillable = [
        'equipement_id',
        'type_imprimante_id',
        'est_couleur',
        'est_multifonction',
        'fonctions',
        'adresse_ip',
        'snmp_community',
    ];

    protected $casts = [
        'est_couleur'       => 'boolean',
        'est_multifonction' => 'boolean',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class, 'equipement_id');
    }

    public function typeImprimante()
    {
        return $this->belongsTo(TypeImprimante::class, 'type_imprimante_id');
    }
}
