<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Serveur extends Model
{
    protected $table = 'parc_info_serveurs';

    public $timestamps = false;

    protected $primaryKey = 'equipement_id';

    public $incrementing = false;

    protected $fillable = [
        'equipement_id',
        'type_serveur',
        'role_serveur',
        'ram_type_id',
        'ram_capacite_go',
        'cpu_type_id',
        'nb_processeurs',
        'nb_coeurs_total',
        'disque_type_id',
        'stockage_capacite_go',
        'os_type_id',
        'nom_hote',
        'domaine',
        'adresse_ip',
        'adresse_mac',
        'hyperviseur',
        'serveur_hote_id',
        'u_position_depart',
        'u_position_fin',
    ];

    protected $casts = [
        'ram_capacite_go'      => 'integer',
        'nb_processeurs'       => 'integer',
        'nb_coeurs_total'      => 'integer',
        'stockage_capacite_go' => 'integer',
        'u_position_depart'    => 'integer',
        'u_position_fin'       => 'integer',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class, 'equipement_id');
    }

    public function typeRam()
    {
        return $this->belongsTo(TypeRam::class, 'ram_type_id');
    }

    public function typeCpu()
    {
        return $this->belongsTo(TypeCpu::class, 'cpu_type_id');
    }

    public function typeDisque()
    {
        return $this->belongsTo(TypeDisque::class, 'disque_type_id');
    }

    public function typeOs()
    {
        return $this->belongsTo(TypeOs::class, 'os_type_id');
    }

    /** Serveur physique hôte (si VM) */
    public function serveurHote()
    {
        return $this->belongsTo(Serveur::class, 'serveur_hote_id', 'equipement_id');
    }

    /** VMs hébergées sur ce serveur physique */
    public function vms()
    {
        return $this->hasMany(Serveur::class, 'serveur_hote_id', 'equipement_id');
    }
}
