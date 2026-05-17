<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Grh\Models\Employe;

class AffectationLicence extends Model
{
    protected $table = 'parc_info_affectations_licences';

    protected $fillable = [
        'licence_id',
        'equipement_id',
        'employe_id',
        'type_affectation',
        'date_affectation',
        'date_fin_affectation',
        'actif',
        'notes',
    ];

    protected $casts = [
        'date_affectation' => 'date',
        'date_fin_affectation' => 'date',
        'actif' => 'boolean',
    ];

    public function licence()
    {
        return $this->belongsTo(Licence::class);
    }

    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }

    public function employe()
    {
        return $this->belongsTo(Employe::class, 'employe_id', 'dossier_employe_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true)
            ->where(function ($q) {
                $q->whereNull('date_fin_affectation')
                    ->orWhere('date_fin_affectation', '>=', now());
            });
    }
}
