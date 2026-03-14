<?php

namespace Modules\Organisation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batiment extends Model
{
    use HasFactory;

    protected $table = 'organisation_batiments';

    protected $fillable = [
        'site_id',
        'code',
        'libelle',
        'description',
        'nombre_etages',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'nombre_etages' => 'integer',
    ];

    protected $with = ['site'];

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function etages()
    {
        return $this->hasMany(Etage::class);
    }

    public function getNomCompletAttribute(): string
    {
        return $this->site->libelle.' > '.$this->libelle;
    }
}
