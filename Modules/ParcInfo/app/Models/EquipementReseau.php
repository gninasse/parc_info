<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class EquipementReseau extends Model
{
    protected $table = 'parc_info_equipements_reseaux';

    public $timestamps = false;

    protected $primaryKey = 'equipement_id';

    public $incrementing = false;

    protected $fillable = [
        'equipement_id',
        'type_reseau_id',
        'nb_ports',
        'vitesse_max_mbps',
        'est_poe',
        'version_firmware',
        'u_position_depart',
        'u_position_fin',
        'vlan_management',
        'adresse_ip',
        'masque_sous_reseau',
        'passerelle',
        'communaute_snmp',
        'est_manageable',
    ];

    protected $casts = [
        'est_poe'           => 'boolean',
        'est_manageable'    => 'boolean',
        'nb_ports'          => 'integer',
        'vitesse_max_mbps'  => 'integer',
        'u_position_depart' => 'integer',
        'u_position_fin'    => 'integer',
        'vlan_management'   => 'integer',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class, 'equipement_id');
    }

    public function typeReseau()
    {
        return $this->belongsTo(TypeReseau::class, 'type_reseau_id');
    }
}
