<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Ordinateur extends Model
{
    protected $table = 'parc_info_ordinateurs';

    public $timestamps = false;

    protected $primaryKey = 'equipement_id';

    public $incrementing = false;

    protected $fillable = [
        'equipement_id',
        'type_pc',
        'ram_type_id',
        'ram_capacite_go',
        'cpu_type_id',
        'processeur_model',
        'disque_type_id',
        'stockage_capacite_go',
        'os_type_id',
        'licence_windows_type',
        'licence_windows_cle',
        'licence_office_type',
        'licence_office_cle',
        'support_tpm2',
        'support_secure_boot',
        'bios_version',
        'uefi_version',
        'nom_hote',
        'domaine_workgroup',
        'adresse_mac_wifi',
        'adresse_mac_ethernet',
        'cycle_batterie',
    ];

    protected $casts = [
        'support_tpm2'        => 'boolean',
        'support_secure_boot' => 'boolean',
        'ram_capacite_go'     => 'integer',
        'stockage_capacite_go'=> 'integer',
        'cycle_batterie'      => 'integer',
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
}
