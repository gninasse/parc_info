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
        'quantite_stock_actuel',
        'quantite_stock_min',
        'quantite_stock_max',
        'date_dernier_approvisionnement',
        'stock_reserve_maintenance',
        'est_actif',
        'notes',
    ];

    protected $casts = [
        'compatible_equipements' => 'array',
        'cout_unitaire' => 'decimal:2',
        'quantite_stock_actuel' => 'integer',
        'quantite_stock_min' => 'integer',
        'quantite_stock_max' => 'integer',
        'stock_reserve_maintenance' => 'integer',
        'est_actif' => 'boolean',
        'date_dernier_approvisionnement' => 'date',
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

    public function mouvementsStock()
    {
        return $this->hasMany(MouvementConsommable::class);
    }

    public function affectations()
    {
        return $this->hasMany(AffectationConsommable::class);
    }

    // SCOPES
    public function scopeEnRupture($query)
    {
        return $query->whereRaw('quantite_stock_actuel <= quantite_stock_min');
    }

    public function scopeActifs($query)
    {
        return $query->where('est_actif', true);
    }

    // ACCESSEURS
    public function getStatutStockAttribute()
    {
        if ($this->quantite_stock_actuel <= 0) {
            return 'RUPTURE';
        }
        if ($this->quantite_stock_actuel <= $this->quantite_stock_min) {
            return 'ALERTE';
        }
        if ($this->quantite_stock_actuel >= $this->quantite_stock_max) {
            return 'SURSTOCK';
        }

        return 'NORMAL';
    }

    public function getValeurStockAttribute()
    {
        return $this->quantite_stock_actuel * $this->cout_unitaire;
    }
}
