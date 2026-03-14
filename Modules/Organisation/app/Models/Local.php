<?php

namespace Modules\Organisation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Local extends Model
{
    use HasFactory;

    protected $table = 'organisation_locaux';

    protected $fillable = [
        'etage_id',
        'code',
        'libelle',
        'type_local',
        'superficie_m2',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'superficie_m2' => 'decimal:2',
    ];

    protected $with = ['etage.batiment.site'];

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function etage()
    {
        return $this->belongsTo(Etage::class);
    }

    public function getNomCompletAttribute(): string
    {
        return $this->etage->nom_complet.' > '.$this->libelle;
    }

    public function getTypeLocalLabelAttribute(): string
    {
        return match ($this->type_local) {
            'bureau' => 'Bureau',
            'salle_soins' => 'Salle de soins',
            'salle_attente' => 'Salle d\'attente',
            'magasin' => 'Magasin',
            'couloir' => 'Couloir',
            'autre' => 'Autre',
            default => $this->type_local,
        };
    }
}
