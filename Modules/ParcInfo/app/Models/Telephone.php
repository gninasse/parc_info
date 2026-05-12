<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Telephone extends Model
{
    protected $table = 'parc_info_telephones';

    public $timestamps = false;

    protected $primaryKey = 'equipement_id';

    public $incrementing = false;

    protected $fillable = [
        'equipement_id',
        'est_ip',
        'extension',
        'protocole',
        'adresse_mac_ethernet',
        'adresse_ip',
        'modele_expansion_count',
    ];

    protected $casts = [
        'est_ip'                 => 'boolean',
        'modele_expansion_count' => 'integer',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class, 'equipement_id');
    }
}
