<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Equipement extends Model
{
    protected $table = 'parc_info_equipements';

    protected $fillable = [
        'code_inventaire',
        'numero_serie',
        'marque_id',
        'modele',
        'date_acquisition',
        'date_mise_en_service',
        'valeur_achat',
        'duree_vie_probable',
        'date_fin_garantie',
        'statut',
        'etat',
        'tags',
    ];

    protected $casts = [
        'date_acquisition'      => 'date',
        'date_mise_en_service'  => 'date',
        'date_fin_garantie'     => 'date',
        'valeur_achat'          => 'decimal:2',
        'duree_vie_probable'    => 'integer',
        'tags'                  => 'array',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeEnService(Builder $query): Builder
    {
        return $query->where('statut', 'en_service');
    }

    public function scopeEnStock(Builder $query): Builder
    {
        return $query->where('statut', 'en_stock');
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function marque()
    {
        return $this->belongsTo(Marque::class, 'marque_id');
    }

    public function affectations()
    {
        return $this->hasMany(AffectationEquipement::class, 'equipement_id');
    }

    public function affectationActive()
    {
        return $this->hasOne(AffectationEquipement::class, 'equipement_id')
                    ->where('statut', true)
                    ->whereNull('date_fin');
    }

    public function historique()
    {
        return $this->hasMany(HistoriqueChangement::class, 'equipement_id');
    }

    // ── Spécialisations ───────────────────────────────────────────────────────

    public function ordinateur()
    {
        return $this->hasOne(Ordinateur::class, 'equipement_id');
    }

    public function serveur()
    {
        return $this->hasOne(Serveur::class, 'equipement_id');
    }

    public function mobile()
    {
        return $this->hasOne(Mobile::class, 'equipement_id');
    }

    public function imprimante()
    {
        return $this->hasOne(Imprimante::class, 'equipement_id');
    }

    public function scanner()
    {
        return $this->hasOne(Scanner::class, 'equipement_id');
    }

    public function reseau()
    {
        return $this->hasOne(EquipementReseau::class, 'equipement_id');
    }

    public function telephone()
    {
        return $this->hasOne(Telephone::class, 'equipement_id');
    }

    public function infrastructure()
    {
        return $this->hasOne(Infrastructure::class, 'equipement_id');
    }

    // ── Accesseurs ────────────────────────────────────────────────────────────

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            'en_stock'       => 'En stock',
            'en_service'     => 'En service',
            'en_reparation'  => 'En réparation',
            'perdu'          => 'Perdu',
            'reforme'        => 'Réformé',
            default          => $this->statut,
        };
    }

    public function getEtatLabelAttribute(): string
    {
        return match ($this->etat) {
            'bon'      => 'Bon',
            'passable' => 'Passable',
            'mauvais'  => 'Mauvais',
            'avarie'   => 'Avarié',
            default    => $this->etat,
        };
    }
}
