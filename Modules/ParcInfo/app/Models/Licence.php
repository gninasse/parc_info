<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Licence extends Model
{
    protected $table = 'parc_info_licences';

    protected $fillable = [
        'logiciel_id',
        'cle_licence',
        'numero_contrat',
        'contrat_maintenance_id',
        'type_activation',
        'nombre_postes_accordes',
        'nombre_postes_utilises',
        'modele_licencing',
        'date_acquisition',
        'date_activation',
        'date_expiration',
        'date_renouvellement_prochain',
        'cout_unitaire',
        'cout_total',
        'devise',
        'fournisseur_id',
        'contact_support_id',
        'statut',
        'conditions_utilisation',
        'actif',
        'notes',
    ];

    protected $casts = [
        'date_acquisition' => 'date',
        'date_activation' => 'date',
        'date_expiration' => 'date',
        'date_renouvellement_prochain' => 'date',
        'nombre_postes_accordes' => 'integer',
        'nombre_postes_utilises' => 'integer',
        'cout_unitaire' => 'decimal:2',
        'cout_total' => 'decimal:2',
        'actif' => 'boolean',
    ];

    public function logiciel()
    {
        return $this->belongsTo(Logiciel::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function contactSupport()
    {
        return $this->belongsTo(Contact::class, 'contact_support_id');
    }

    public function affectations()
    {
        return $this->hasMany(AffectationLicence::class);
    }

    public function contratMaintenance()
    {
        return $this->belongsTo(ContratMaintenance::class);
    }

    public function documents()
    {
        return $this->hasMany(DocumentLicence::class);
    }

    // Scopes
    public function scopeExpirantProchainement($query)
    {
        return $query->whereDate('date_expiration', '<=', now()->addDays(30))
            ->where('actif', true);
    }

    public function scopeExpire($query)
    {
        return $query->whereDate('date_expiration', '<', now());
    }

    public function scopeEnSurexploitation($query)
    {
        return $query->whereRaw('nombre_postes_utilises > nombre_postes_accordes');
    }

    // Accesseurs
    public function getTauxUtilisationAttribute()
    {
        if ($this->nombre_postes_accordes == 0) {
            return 0;
        }

        return round(
            ($this->nombre_postes_utilises / $this->nombre_postes_accordes) * 100,
            2
        );
    }

    public function getStatutValiditeAttribute()
    {
        if ($this->date_expiration < now()) {
            return 'EXPIREE';
        }
        if ($this->date_expiration < now()->addDays(30)) {
            return 'ALERTE';
        }

        return 'VALIDE';
    }

    public function getDisponibilitesAttribute()
    {
        return max(0, $this->nombre_postes_accordes - $this->nombre_postes_utilises);
    }
}
