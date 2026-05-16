<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class AffectationConsommable extends Model
{
    protected $table = 'parc_info_affectations_consommables';
    
    public $timestamps = true;
    
    protected $fillable = [
        'consommable_id',
        'equipement_id',
        'quantite_fournie',
        'date_affectation',
        'date_remplacement_prochain_prevu',
        'cycle_remplacement_jours',
        'notes',
    ];
    
    protected $casts = [
        'date_affectation' => 'date',
        'date_remplacement_prochain_prevu' => 'date',
        'quantite_fournie' => 'integer',
        'cycle_remplacement_jours' => 'integer',
    ];
    
    public function consommable()
    {
        return $this->belongsTo(Consommable::class);
    }
    
    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }
    
    // Vérifier si remplacement est proche
    public function necessiteRemplacementProchainement()
    {
        return $this->date_remplacement_prochain_prevu 
            && $this->date_remplacement_prochain_prevu <= now()->addDays(7);
    }
}
