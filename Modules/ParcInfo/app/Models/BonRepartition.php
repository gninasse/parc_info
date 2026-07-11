<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonRepartition extends Model
{
    protected $table = 'parc_info_bons_repartition';

    protected $fillable = [
        'numero_bon',
        'date_bon',
        'fournisseur_id',
        'observation',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_bon' => 'date',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneBonRepartition::class, 'bon_id');
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    // ── Accesseurs ─────────────────────────────────────────────────────────────

    /**
     * @return array{label: string, color: string}
     */
    public function getStatutLabelAttribute(): array
    {
        $total = $this->lignes->count();
        $signees = $this->lignes->where('est_signe', true)->count();

        if ($total === 0) {
            return ['label' => 'Vide', 'color' => 'secondary'];
        }

        if ($signees === $total) {
            return ['label' => 'Clôturé', 'color' => 'success'];
        }

        return ['label' => 'En cours', 'color' => 'warning'];
    }

    public function getProgressionAttribute(): int
    {
        $total = $this->lignes->count();

        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->lignes->where('est_signe', true)->count() / $total) * 100);
    }

    // ── Boot ───────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (BonRepartition $bon) {
            if (! $bon->numero_bon) {
                $annee = now()->year;
                $sequence = static::whereYear('created_at', $annee)->count() + 1;
                $bon->numero_bon = sprintf('BON-%d-%04d', $annee, $sequence);
            }

            if (! $bon->created_by) {
                $bon->created_by = auth()->id();
            }
        });
    }
}
