<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;

class LigneBonRepartition extends Model
{
    protected $table = 'parc_info_lignes_bon_repartition';

    protected $fillable = [
        'bon_id',
        'equipement_id',
        'type_cible',
        'direction_id',
        'service_id',
        'nom_receptionniste',
        'date_livraison',
        'est_signe',
        'date_signature',
        'affectation_id',
        'observation',
    ];

    protected function casts(): array
    {
        return [
            'date_livraison' => 'date',
            'date_signature' => 'datetime',
            'est_signe' => 'boolean',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function bon(): BelongsTo
    {
        return $this->belongsTo(BonRepartition::class, 'bon_id');
    }

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class, 'equipement_id');
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class, 'direction_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function affectation(): BelongsTo
    {
        return $this->belongsTo(AffectationEquipement::class, 'affectation_id');
    }

    // ── Accesseurs ─────────────────────────────────────────────────────────────

    public function getCibleLabelAttribute(): string
    {
        if ($this->type_cible === 'DIRECTION') {
            return $this->direction?->libelle ?? '—';
        }

        if ($this->type_cible === 'SERVICE') {
            return $this->service?->libelle ?? '—';
        }

        return '—';
    }
}
