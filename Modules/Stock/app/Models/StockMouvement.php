<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Stock\Traits\HasAuditFields;

/**
 * Mouvement de stock — table unique pour F3 (entrées), F4 (sorties),
 * F5 (transferts) et F6 (écarts d'inventaire).
 *
 * L'article est référencé par son identifiant achat_articles ; sa fiche
 * s'obtient via AchatIntegrationInterface.
 */
class StockMouvement extends Model
{
    use HasAuditFields, SoftDeletes;

    protected $table = 'stock_mouvements';

    protected $fillable = [
        'numero_mouvement',
        'type_mouvement',
        'article_id',
        'magasin_id',
        'quantite',
        'cout_unitaire',
        'type_origine',
        'origine_id',
        'reference_document',
        'motif',
        'valide_at',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'cout_unitaire' => 'decimal:4',
            'valide_at' => 'datetime',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(StockLot::class, 'mouvement_id');
    }

    public function affectation(): HasOne
    {
        return $this->hasOne(StockMouvementAffectation::class, 'mouvement_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeEntrees(Builder $query): Builder
    {
        return $query->whereIn('type_mouvement', ['ENTREE', 'REGULARISATION_PLUS']);
    }

    public function scopeSorties(Builder $query): Builder
    {
        return $query->whereIn('type_mouvement', ['SORTIE', 'REGULARISATION_MOINS']);
    }

    // ── Méthodes métier ────────────────────────────────────────────────────

    /** Sens du mouvement : +1 (entrée en stock) ou -1 (sortie de stock). */
    public function sens(): int
    {
        return (int) config("stock.types_mouvement.{$this->type_mouvement}.sens", 0);
    }

    /** RG-F3-05 — Une entrée issue d'un bordereau de livraison est intouchable. */
    public function provientDunBordereau(): bool
    {
        return $this->type_origine === 'BL';
    }

    public function getTypeLabelAttribute(): string
    {
        return config("stock.types_mouvement.{$this->type_mouvement}.label", $this->type_mouvement);
    }

    public function getTypeColorAttribute(): string
    {
        return config("stock.types_mouvement.{$this->type_mouvement}.color", 'secondary');
    }
}
