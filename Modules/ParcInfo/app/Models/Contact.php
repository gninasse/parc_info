<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'parc_info_contacts';

    protected $fillable = [
        'fournisseur_id',
        'nom',
        'prenom',
        'email',
        'telephone',
        'fonction',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    public function fournisseurs()
    {
        return $this->hasMany(Fournisseur::class, 'contact_principal_id');
    }
}
