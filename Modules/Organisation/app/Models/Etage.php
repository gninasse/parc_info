<?php

namespace Modules\Organisation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etage extends Model
{
    use HasFactory;

    protected $table = 'organisation_etages';

    protected $fillable = [
        'batiment_id',
        'numero',
        'libelle',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'numero' => 'integer',
    ];

    protected $with = ['batiment.site'];

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function batiment()
    {
        return $this->belongsTo(Batiment::class);
    }

    public function locaux()
    {
        return $this->hasMany(Local::class);
    }

    public function getNomCompletAttribute(): string
    {
        return $this->batiment->nom_complet.' > '.$this->libelle;
    }
}
