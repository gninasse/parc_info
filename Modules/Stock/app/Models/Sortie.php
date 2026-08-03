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
use Modules\Stock\Models\Concerns\PorteBeneficiaire;

class Sortie extends Model
{
    use EstDocumentStock, HasFactory, JournaliseActiviteStock, PorteBeneficiaire;

    public const STATUT_BROUILLON = 'BROUILLON';

    public const STATUT_POINTAGE = 'POINTAGE';

    public const STATUT_VALIDE = 'VALIDE';

    public const STATUT_ANNULE = 'ANNULE';

    /** Phase de références du triptyque (D14). */
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

    protected $table = 'stock_sorties';

    /** Reflet en mémoire du défaut du schéma (statut géré par transitions). */
    protected $attributes = [
        'statut' => self::STATUT_BROUILLON,
    ];

    protected $fillable = [
        'date_document',
        'magasin_id',
        'motif_type',
        'motif_texte',
        'remise_reelle_le',
        'beneficiaire_type',
        'beneficiaire_direction_id',
        'beneficiaire_service_id',
        'beneficiaire_unite_id',
        'beneficiaire_poste_id',
        'beneficiaire_local_id',
        'beneficiaire_employe_id',
        'remis_a_nom',
        'remis_a_employe_id',
        'observation',
        'created_by',
    ];

    protected $casts = [
        'date_document' => 'date',
        'remise_reelle_le' => 'datetime',
        'valide_le' => 'datetime',
    ];

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneSortie::class, 'sortie_id');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(Mouvement::class, 'sortie_id');
    }

    public function remisAEmploye(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'remis_a_employe_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function scopeDuMagasin(Builder $query, int $magasinId): Builder
    {
        return $query->where('magasin_id', $magasinId);
    }

    /** BROUILLON → POINTAGE : quantités verrouillées, les unités se pointent (D14/D16). */
    public function passerEnPointage(): void
    {
        $this->transitionner([self::STATUT_BROUILLON], self::STATUT_POINTAGE);
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\SortieFactory::new();
    }
}
