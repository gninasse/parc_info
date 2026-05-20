<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class EquipementReseau extends Model
{
    protected $table = 'parc_info_equipements_reseau';

    public $timestamps = false;

    protected $primaryKey = 'equipement_id';

    public $incrementing = false;

    protected $fillable = [
        'equipement_id',
        'type_reseau_id',
        'nombre_ports',
        'vitesse_port',
        'support_poe',
        'poe_budget_watts',
        'support_vlan',
        'support_stp',
        'support_lacp',
        'support_snmp',
        'firmware_version',
        'adresse_ip_management',
        'snmp_community',
        'snmp_version',
        'vlans_configures',
        'modele_reference',
        'nombre_ports_uplink',
        'support_redundance',
        'location_detail',
    ];

    protected $casts = [
        'support_poe' => 'boolean',
        'support_vlan' => 'boolean',
        'support_stp' => 'boolean',
        'support_lacp' => 'boolean',
        'support_snmp' => 'boolean',
        'support_redundance' => 'boolean',
        'nombre_ports' => 'integer',
        'poe_budget_watts' => 'integer',
        'nombre_ports_uplink' => 'integer',
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
