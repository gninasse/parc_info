<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Exceptions\MouvementImmuableException;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

/**
 * Journal des mouvements — source de vérité, EN INSERTION SEULE (S1).
 * Toute écriture passe par MouvementService (S2) ; les corrections sont
 * des contre-mouvements. Le modèle refuse update et delete.
 */
class Mouvement extends Model
{
    use HasFactory, JournaliseActiviteStock;

    public const TYPE_ENTREE = 'ENTREE';

    public const TYPE_SORTIE = 'SORTIE';

    public const TYPE_TRANSFERT_ENTREE = 'TRANSFERT_ENTREE';

    public const TYPE_TRANSFERT_SORTIE = 'TRANSFERT_SORTIE';

    public const TYPE_AJUSTEMENT = 'AJUSTEMENT';

    public const TYPES = [
        self::TYPE_ENTREE,
        self::TYPE_SORTIE,
        self::TYPE_TRANSFERT_ENTREE,
        self::TYPE_TRANSFERT_SORTIE,
        self::TYPE_AJUSTEMENT,
    ];

    /** Icône + texte, jamais la couleur seule (S7). */
    public const TYPE_LABELS = [
        self::TYPE_ENTREE => '⬆ ENTREE',
        self::TYPE_SORTIE => '⬇ SORTIE',
        self::TYPE_TRANSFERT_ENTREE => '⬆ TRANSFERT',
        self::TYPE_TRANSFERT_SORTIE => '⬇ TRANSFERT',
        self::TYPE_AJUSTEMENT => '± AJUSTEMENT',
    ];

    public const SENS_ENTREE = 1;

    public const SENS_SORTIE = -1;

    public const UPDATED_AT = null; // journal : une ligne ne se modifie jamais

    protected $table = 'stock_mouvements';

    protected $fillable = [
        'entree_id',
        'sortie_id',
        'transfert_id',
        'inventaire_id',
        'mouvement_origine_id',
        'magasin_id',
        'type',
        'sens',
        'article_id',
        'equipement_id',
        'quantite',
        'cout_unitaire',
        'affectation_equipement_id',
        'motif',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'sens' => 'integer',
        'quantite' => 'decimal:2',
        'cout_unitaire' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw MouvementImmuableException::creer());
        static::deleting(fn () => throw MouvementImmuableException::creer());
    }

    public function entree(): BelongsTo
    {
        return $this->belongsTo(Entree::class);
    }

    public function sortie(): BelongsTo
    {
        return $this->belongsTo(Sortie::class);
    }

    public function transfert(): BelongsTo
    {
        return $this->belongsTo(Transfert::class);
    }

    public function inventaire(): BelongsTo
    {
        return $this->belongsTo(Inventaire::class);
    }

    public function origine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mouvement_origine_id');
    }

    public function contreMouvements(): HasMany
    {
        return $this->hasMany(self::class, 'mouvement_origine_id');
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class);
    }

    public function affectationEquipement(): BelongsTo
    {
        return $this->belongsTo(AffectationEquipement::class, 'affectation_equipement_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDuMagasin(Builder $query, int $magasinId): Builder
    {
        return $query->where('magasin_id', $magasinId);
    }

    /** Quantité signée en toutes lettres : « +2 » / « −2 » (S7). */
    public function getQuantiteSigneeAttribute(): string
    {
        $quantite = rtrim(rtrim(number_format((float) $this->quantite, 2, ',', ' '), '0'), ',');

        return ($this->sens >= 0 ? '+' : '−').$quantite;
    }

    public function estContreMouvement(): bool
    {
        return $this->mouvement_origine_id !== null;
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\MouvementFactory::new();
    }
}
