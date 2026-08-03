<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;
use Modules\Grh\Models\Employe;
use Modules\Stock\Models\Concerns\EstDocumentStock;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

class Transfert extends Model
{
    use EstDocumentStock, HasFactory, JournaliseActiviteStock;

    public const STATUT_BROUILLON = 'BROUILLON';

    public const STATUT_POINTAGE = 'POINTAGE';

    public const STATUT_VALIDE = 'VALIDE';

    public const STATUT_ANNULE = 'ANNULE';

    /** Phase de références du triptyque (D17). */
    public const STATUT_PHASE = self::STATUT_POINTAGE;

    public const STATUTS = [
        self::STATUT_BROUILLON,
        self::STATUT_POINTAGE,
        self::STATUT_VALIDE,
        self::STATUT_ANNULE,
    ];

    /** Libellés orientés geste (amendement UX n°2). */
    public const STATUT_LABELS = [
        self::STATUT_BROUILLON => 'Brouillon',
        self::STATUT_POINTAGE => 'Pointage en cours',
        self::STATUT_VALIDE => 'Validé',
        self::STATUT_ANNULE => 'Annulé',
    ];

    protected $table = 'stock_transferts';

    /** Reflet en mémoire du défaut du schéma (statut géré par transitions). */
    protected $attributes = [
        'statut' => self::STATUT_BROUILLON,
    ];

    protected $fillable = [
        'date_document',
        'magasin_source_id',
        'magasin_cible_id',
        'transporte_par_nom',
        'transporte_par_employe_id',
        'observation',
        'created_by',
    ];

    protected $casts = [
        'date_document' => 'date',
        'valide_le' => 'datetime',
    ];

    public function magasinSource(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_source_id');
    }

    public function magasinCible(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_cible_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneTransfert::class, 'transfert_id');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(Mouvement::class, 'transfert_id');
    }

    public function transporteParEmploye(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'transporte_par_employe_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    /** Un transfert « appartient » au magasin en source comme en cible. */
    public function scopeDuMagasin(Builder $query, int $magasinId): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('magasin_source_id', $magasinId)
            ->orWhere('magasin_cible_id', $magasinId));
    }

    public function passerEnPointage(): void
    {
        $this->transitionner([self::STATUT_BROUILLON], self::STATUT_POINTAGE);
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\TransfertFactory::new();
    }
}
