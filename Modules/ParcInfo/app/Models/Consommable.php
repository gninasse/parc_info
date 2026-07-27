<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Consommable extends Model
{
    protected $table = 'parc_info_consommables';

    protected $fillable = [
        'code',
        'nom',
        'article_id',
        'type_consommable_id',
        'marque_id',
        'modele_reference',
        'compatible_equipements',
        'fournisseur_principal_id',
        'cout_unitaire',
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
    public function scopeActifs($query)
    {
        return $query->where('est_actif', true);
    }

    // EF-STK-05 — la fiche est un catalogue : les quantités, la valorisation
    // et le statut d'alerte se lisent auprès du module Stock via
    // Modules\ParcInfo\Contracts\StockIntegrationInterface.
}
