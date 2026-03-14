<?php

namespace Modules\Organisation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Direction extends Model
{
    use HasFactory;

    protected $table = 'organisation_directions';

    protected $fillable = [
        'site_id',
        'code',
        'libelle',
        'responsable_id',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
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

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function getNomCompletAttribute(): string
    {
        return $this->site->libelle.' > '.$this->libelle;
    }
}
