<?php

namespace Modules\Organisation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Organisation\Models\Referentiel\Magasin;

class Site extends Model
{
    use HasFactory;

    protected $table = 'organisation_sites';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'adresse',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function directions()
    {
        return $this->hasMany(Direction::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function unites()
    {
        return $this->hasMany(Unite::class);
    }

    public function magasins()
    {
        return $this->hasMany(Magasin::class);
    }

    public function getNomCompletAttribute(): string
    {
        return $this->libelle;
    }
}
