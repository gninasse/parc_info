<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class ContratMaintenance extends Model
{
    protected $table = 'parc_info_contrats_maintenances';

    protected $fillable = [
        'reference',
        'nom',
        'fournisseur_id',
        'date_debut',
        'date_fin',
        'cout',
        'est_actif',
        'notes',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'cout' => 'decimal:2',
        'est_actif' => 'boolean',
    ];

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function licences()
    {
        return $this->hasMany(Licence::class);
    }
}
