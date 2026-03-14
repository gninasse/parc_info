<?php

namespace Modules\Organisation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosteTravail extends Model
{
    protected $table = 'organisation_postes_travail';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'direction_id',
        'service_id',
        'unite_id',
        'local_id',
        'agent_id',
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
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeVacant($query)
    {
        return $query->whereNull('agent_id');
    }

    public function scopeParService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    public static function generateCode($serviceId)
    {
        $service = Service::find($serviceId);
        $count = static::where('service_id', $serviceId)->count() + 1;

        return sprintf('POST-%s-%03d', strtoupper($service->code ?? 'SRV'), $count);
    }
}
