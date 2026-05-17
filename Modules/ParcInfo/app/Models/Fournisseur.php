<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Fournisseur extends Model
{
    protected $table = 'parc_info_fournisseurs';

    protected $fillable = [
        'code',
        'nom',
        'type',
        'email',
        'telephone',
        'adresse',
        'contact_principal_id',
        'conditions_paiement',
        'delai_livraison',
        'fiabilite_score',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'fiabilite_score' => 'integer',
    ];

    public function licences()
    {
        return $this->hasMany(Licence::class);
    }

    public function contactPrincipal()
    {
        return $this->belongsTo(Contact::class, 'contact_principal_id');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'fournisseur_id');
    }

    public function contrats()
    {
        return $this->hasMany(ContratMaintenance::class, 'fournisseur_id');
    }
}
