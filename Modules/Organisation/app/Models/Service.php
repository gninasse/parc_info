<?php

namespace Modules\Organisation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'organisation_services';

    protected $fillable = [
        'direction_id',
        'site_id',
        'code',
        'libelle',
        'type_service',
        'chef_service_id',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    protected $with = ['direction.site'];

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function direction()
    {
        return $this->belongsTo(Direction::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function chefService()
    {
        return $this->belongsTo(User::class, 'chef_service_id');
    }

    public function unites()
    {
        return $this->hasMany(Unite::class);
    }

    public function postesTravail()
    {
        return $this->hasMany(PosteTravail::class);
    }

    public function getNomCompletAttribute(): string
    {
        return $this->direction->nom_complet.' > '.$this->libelle;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->direction_id) {
                $model->site_id = $model->direction->site_id;
            }
        });
    }
}
