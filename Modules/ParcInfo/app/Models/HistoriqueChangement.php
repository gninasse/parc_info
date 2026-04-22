<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriqueChangement extends Model
{
    protected $table = 'parc_info_historique_changements';

    protected $fillable = [
        'equipement_id',
        'date_changement',
        'utilisateur_id',
        'type_changement',
        'ancien_statut',
        'nouveau_statut',
        'ancien_etat',
        'nouvel_etat',
        'motif',
        'reference_document',
    ];

    protected $casts = [
        'date_changement' => 'datetime',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class, 'equipement_id');
    }
}
