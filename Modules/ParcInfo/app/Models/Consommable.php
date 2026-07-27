<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Consommable extends Model
{
    protected $table = 'parc_info_consommables';

    protected $fillable = [
        'code',
        'nom',
        'type_consommable_id',
        'marque_id',
        'modele_reference',
        'compatible_equipements',
        'fournisseur_principal_id',
        'cout_unitaire',
        'est_actif',
        'notes',
    ];

    protected $casts = [
        'compatible_equipements' => 'array',
        'cout_unitaire' => 'decimal:2',
        'est_actif' => 'boolean',
    ];

    public function typeConsommable()
    {
        return $this->belongsTo(TypeConsommable::class);
    }

    public function marque()
    {
        return $this->belongsTo(Marque::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_principal_id');
    }

    public function affectations()
    {
        return $this->hasMany(AffectationConsommable::class);
    }

    // SCOPES
    public function scopeActifs($query)
    {
        return $query->where('est_actif', true);
    }
}
