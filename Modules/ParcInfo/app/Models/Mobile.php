<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Mobile extends Model
{
    protected $table = 'parc_info_mobiles';

    public $timestamps = false;

    protected $primaryKey = 'equipement_id';

    public $incrementing = false;

    protected $fillable = [
        'equipement_id',
        'type_mobile_id',
        'imei_1',
        'imei_2',
        'num_tel_associe',
        'version_os',
        'statut_mdm',
        'capacite_batterie_mah',
        'etat_ecran',
        'a_coque_protection',
    ];

    protected $casts = [
        'a_coque_protection'    => 'boolean',
        'capacite_batterie_mah' => 'integer',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class, 'equipement_id');
    }

    public function typeMobile()
    {
        return $this->belongsTo(TypeMobile::class, 'type_mobile_id');
    }
}
