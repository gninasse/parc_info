<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\User;
use Modules\Stock\Traits\HasAuditFields;

/**
 * Transfert inter-magasins (F5) — EN_ATTENTE → VALIDE | REJETE | ANNULE.
 */
class StockTransfert extends Model
{
    use HasAuditFields, SoftDeletes;

    protected $table = 'stock_transferts';

    protected $fillable = [
        'numero_transfert',
        'magasin_source_id',
        'magasin_destination_id',
        'article_id',
        'quantite',
        'statut',
        'motif_creation',
        'motif_rejet',
        'valide_par',
        'date_validation',
        'mouvement_sortant_id',
        'mouvement_entrant_id',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'date_validation' => 'datetime',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────

    public function magasinSource(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_source_id');
    }

    public function magasinDestination(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_destination_id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function mouvementSortant(): BelongsTo
    {
        return $this->belongsTo(StockMouvement::class, 'mouvement_sortant_id');
    }

    public function mouvementEntrant(): BelongsTo
    {
        return $this->belongsTo(StockMouvement::class, 'mouvement_entrant_id');
    }

    // ── Méthodes métier ────────────────────────────────────────────────────

    public function estEnAttente(): bool
    {
        return $this->statut === 'EN_ATTENTE';
    }

    public function getStatutLabelAttribute(): string
    {
        return config("stock.statuts_transfert.{$this->statut}.label", $this->statut);
    }

    public function getStatutColorAttribute(): string
    {
        return config("stock.statuts_transfert.{$this->statut}.color", 'secondary');
    }
}
