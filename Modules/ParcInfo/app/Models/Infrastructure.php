<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Infrastructure extends Model
{
    protected $table = 'parc_info_infrastructures';

    public $timestamps = false;

    protected $primaryKey = 'equipement_id';

    public $incrementing = false;

    protected $fillable = [
        'equipement_id',
        'type_infra_id',
        'puissance_va',
        'autonomie_minutes',
        'date_dernier_remplacement_batterie',
        'nb_prises_pdu',
        'u_capacite_totale',
        'est_redondant',
    ];

    protected $casts = [
        'est_redondant'                      => 'boolean',
        'puissance_va'                       => 'integer',
        'autonomie_minutes'                  => 'integer',
        'nb_prises_pdu'                      => 'integer',
        'u_capacite_totale'                  => 'integer',
        'date_dernier_remplacement_batterie' => 'date',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class, 'equipement_id');
    }

    public function typeInfrastructure()
    {
        return $this->belongsTo(TypeInfrastructure::class, 'type_infra_id');
    }
}
