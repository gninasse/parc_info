<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;
use Modules\Stock\Exceptions\TransitionInterditeException;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

/**
 * Machine à états propre (pas de triptyque) : EN_COURS → VALIDE | ANNULE.
 * Pas de suppression (SFD §8 : aucune route destroy) — l'annulation est
 * la seule issue sans effet sur les niveaux.
 */
class Inventaire extends Model
{
    use HasFactory, JournaliseActiviteStock;

    public const STATUT_EN_COURS = 'EN_COURS';

    public const STATUT_VALIDE = 'VALIDE';

    public const STATUT_ANNULE = 'ANNULE';

    public const STATUTS = [
        self::STATUT_EN_COURS,
        self::STATUT_VALIDE,
        self::STATUT_ANNULE,
    ];

    public const STATUT_LABELS = [
        self::STATUT_EN_COURS => 'En cours',
        self::STATUT_VALIDE => 'Validé',
        self::STATUT_ANNULE => 'Annulé',
    ];

    public const PERIMETRE_MAGASIN = 'magasin';

    public const PERIMETRE_SELECTION = 'selection';

    protected $table = 'stock_inventaires';

    /** Reflet en mémoire du défaut du schéma (statut géré par transitions). */
    protected $attributes = [
        'statut' => self::STATUT_EN_COURS,
    ];

    protected $fillable = [
        'date_document',
        'magasin_id',
        'perimetre',
        'motif_global',
        'observation',
        'created_by',
    ];

    protected $casts = [
        'date_document' => 'date',
        'valide_le' => 'datetime',
    ];

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneInventaire::class, 'inventaire_id');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(Mouvement::class, 'inventaire_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function scopeValides(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_VALIDE);
    }

    public function scopeNonValides(Builder $query): Builder
    {
        return $query->where('statut', '<>', self::STATUT_VALIDE);
    }

    public function scopeEnCours(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_EN_COURS);
    }

    public function scopeDuMagasin(Builder $query, int $magasinId): Builder
    {
        return $query->where('magasin_id', $magasinId);
    }

    public function getNumeroAfficheAttribute(): string
    {
        return $this->numero ?? 'Brouillon #'.$this->id;
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUT_LABELS[$this->statut] ?? $this->statut;
    }

    public function estValide(): bool
    {
        return $this->statut === self::STATUT_VALIDE;
    }

    /** La saisie du comptage n'est possible qu'EN_COURS. */
    public function canEdit(): bool
    {
        return $this->statut === self::STATUT_EN_COURS;
    }

    /** Un inventaire ne se supprime jamais : il se valide ou s'annule. */
    public function canDelete(): bool
    {
        return false;
    }

    public function canValidate(): bool
    {
        return $this->statut === self::STATUT_EN_COURS;
    }

    public function valider(?int $validePar = null): void
    {
        if ($this->statut !== self::STATUT_EN_COURS) {
            throw TransitionInterditeException::pour($this->numero_affiche, $this->statut, self::STATUT_VALIDE);
        }

        $this->forceFill([
            'statut' => self::STATUT_VALIDE,
            'valide_par' => $validePar,
            'valide_le' => now(),
        ])->save();
    }

    public function annuler(): void
    {
        if ($this->statut !== self::STATUT_EN_COURS) {
            throw TransitionInterditeException::pour($this->numero_affiche, $this->statut, self::STATUT_ANNULE);
        }

        $this->forceFill(['statut' => self::STATUT_ANNULE])->save();
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\InventaireFactory::new();
    }
}
