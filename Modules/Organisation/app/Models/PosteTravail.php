<?php

namespace Modules\Organisation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Grh\Models\Employe;

class PosteTravail extends Model
{
    protected $table = 'organisation_postes_travail';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'niveau_rattachement',
        'direction_id',
        'service_id',
        'unite_id',
        'local_id',
        'dossier_employe_id',
        'statut',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function unite(): BelongsTo
    {
        return $this->belongsTo(Unite::class);
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'dossier_employe_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeVacant($query)
    {
        return $query->whereNull('dossier_employe_id');
    }

    public static function generateCode($parentId, $isService = true)
    {
        if ($isService) {
            $service = Service::find($parentId);
            $count = static::where('service_id', $parentId)->count() + 1;
            $code = $service->code ?? 'SRV';
        } else {
            $direction = Direction::find($parentId);
            $count = static::where('direction_id', $parentId)->whereNull('service_id')->count() + 1;
            $code = $direction->code ?? 'DIR';
        }

        return sprintf('POST-%s-%03d', strtoupper($code), $count);
    }
}
