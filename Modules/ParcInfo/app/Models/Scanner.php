<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Scanner extends Model
{
    protected $table = 'parc_info_scanners';

    public $timestamps = false;

    protected $primaryKey = 'equipement_id';

    public $incrementing = false;

    protected $fillable = [
        'equipement_id',
        'resolution_dpi_max',
        'format_max',
        'est_recto_verso',
        'a_chargeur_auto',
        'type_capteur',
    ];

    protected $casts = [
        'est_recto_verso'   => 'boolean',
        'a_chargeur_auto'   => 'boolean',
        'resolution_dpi_max'=> 'integer',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class, 'equipement_id');
    }
}
