<?php

namespace Modules\Organisation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unite extends Model
{
    use HasFactory;

    protected $table = 'organisation_unites';

    protected $fillable = [
        'service_id',
        'site_id',
        'code',
        'libelle',
        'major_id',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    protected $with = ['service.direction.site'];

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function major()
    {
        return $this->belongsTo(User::class, 'major_id');
    }

    public function getNomCompletAttribute(): string
    {
        return $this->service->nom_complet.' > '.$this->libelle;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->service_id) {
                $model->site_id = $model->service->site_id;
            }
        });
    }
}
