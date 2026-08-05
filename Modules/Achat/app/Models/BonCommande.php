<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Achat\Exceptions\TransitionInterditeException;
use Modules\Achat\Models\Concerns\JournaliseActiviteAchat;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Modules\Organisation\Models\Service;

/**
 * Le bon de commande — colonne vertébrale du module (SFD §1.4).
 *
 *   BROUILLON ──Soumettre──▶ SOUMIS ──Valider──▶ VALIDE ──réceptions──▶ PARTIEL ──▶ LIVRE
 *       ▲                      │                   │                       │
 *       └──────Renvoyer────────┘                Annuler                Clôturer
 *              (motivé)                     (si 0 réception)              ▼
 *                                                 ▼                    CLOTURE
 *                                              ANNULE
 *
 * Le numéro n'est attribué qu'à la validation, sous verrou (IA-3) : un
 * brouillon supprimé ne laisse aucun trou dans la séquence.
 */
class BonCommande extends Model
{
    use HasFactory, JournaliseActiviteAchat;

    public const STATUT_BROUILLON = 'BROUILLON';

    public const STATUT_SOUMIS = 'SOUMIS';

    public const STATUT_VALIDE = 'VALIDE';

    public const STATUT_PARTIEL = 'PARTIEL';

    public const STATUT_LIVRE = 'LIVRE';

    public const STATUT_CLOTURE = 'CLOTURE';

    public const STATUT_ANNULE = 'ANNULE';

    public const STATUTS = [
        self::STATUT_BROUILLON,
        self::STATUT_SOUMIS,
        self::STATUT_VALIDE,
        self::STATUT_PARTIEL,
        self::STATUT_LIVRE,
        self::STATUT_CLOTURE,
        self::STATUT_ANNULE,
    ];

    /** Libellés d'interface (SPEC_UX §0.2). */
    public const STATUT_LABELS = [
        self::STATUT_BROUILLON => 'Brouillon',
        self::STATUT_SOUMIS => 'Soumis',
        self::STATUT_VALIDE => 'Validé',
        self::STATUT_PARTIEL => 'Partiel',
        self::STATUT_LIVRE => 'Livré',
        self::STATUT_CLOTURE => 'Clôturé',
        self::STATUT_ANNULE => 'Annulé',
    ];

    /**
     * Couleurs de pilule (SPEC_UX §0.2 — ⚠ DESIGN.md : le JAUNE signifie
     * « verrouillé, en cours d'officialisation », comme RÉFÉRENCEMENT et
     * POINTAGE du module Stock).
     */
    public const STATUT_COULEURS = [
        self::STATUT_BROUILLON => 'secondary',
        self::STATUT_SOUMIS => 'warning',
        self::STATUT_VALIDE => 'primary',
        self::STATUT_PARTIEL => 'orange',
        self::STATUT_LIVRE => 'success',
        self::STATUT_CLOTURE => 'dark',
        self::STATUT_ANNULE => 'danger',
    ];

    /** Statuts engagés : le bon porte un numéro et n'est plus modifiable. */
    public const STATUTS_ENGAGES = [
        self::STATUT_VALIDE,
        self::STATUT_PARTIEL,
        self::STATUT_LIVRE,
        self::STATUT_CLOTURE,
    ];

    /** Statuts ouverts aux réceptions (SFD §7.3). */
    public const STATUTS_RECEPTIONNABLES = [
        self::STATUT_VALIDE,
        self::STATUT_PARTIEL,
    ];

    protected $table = 'achat_bons_commande';

    /** Reflet en mémoire des défauts du schéma (statut géré par transitions). */
    protected $attributes = [
        'statut' => self::STATUT_BROUILLON,
        'est_regularisation' => false,
    ];

    protected $fillable = [
        'fournisseur_id',
        'date_document',
        'est_regularisation',
        'service_demandeur_id',
        'reference_demande',
        'observation_type',
        'observation_texte',
        'created_by',
    ];

    protected $casts = [
        'date_document' => 'date',
        'est_regularisation' => 'boolean',
        'montant_ht' => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
        'soumis_le' => 'datetime',
        'valide_le' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneCommande::class, 'bon_commande_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'bon_commande_id');
    }

    public function rattachements(): HasMany
    {
        return $this->hasMany(RegularisationRattachement::class, 'bon_commande_id');
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function serviceDemandeur(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_demandeur_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function soumetteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'soumis_par');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeEngages(Builder $query): Builder
    {
        return $query->whereIn('statut', self::STATUTS_ENGAGES);
    }

    public function scopeAValider(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_SOUMIS);
    }

    public function scopeReceptionnables(Builder $query): Builder
    {
        return $query->whereIn('statut', self::STATUTS_RECEPTIONNABLES);
    }

    /** Les régularisations sont exclues des statistiques par défaut (SFD §7.6). */
    public function scopeHorsRegularisation(Builder $query): Builder
    {
        return $query->where('est_regularisation', false);
    }

    // ── Présentation ───────────────────────────────────────────────────────

    /** « BC-2026-0041 » ou « Brouillon #12 » (SPEC_UX §0.2). */
    public function getNumeroAfficheAttribute(): string
    {
        return $this->numero ?? 'Brouillon #'.$this->id;
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUT_LABELS[$this->statut] ?? $this->statut;
    }

    public function getStatutCouleurAttribute(): string
    {
        return self::STATUT_COULEURS[$this->statut] ?? 'secondary';
    }

    // ── État et droits métier (SFD §1.4) ───────────────────────────────────

    public function estEngage(): bool
    {
        return in_array($this->statut, self::STATUTS_ENGAGES, true);
    }

    /** Modifiable uniquement en brouillon — 409 sinon (SFD §5). */
    public function estModifiable(): bool
    {
        return $this->statut === self::STATUT_BROUILLON;
    }

    /** Suppression réelle (cascade), brouillon uniquement. */
    public function estSupprimable(): bool
    {
        return $this->statut === self::STATUT_BROUILLON;
    }

    public function estSoumettable(): bool
    {
        return $this->statut === self::STATUT_BROUILLON;
    }

    public function estValidable(): bool
    {
        return $this->statut === self::STATUT_SOUMIS;
    }

    /** Annulable seulement avant toute réception (SFD §7.5). */
    public function estAnnulable(): bool
    {
        return $this->statut === self::STATUT_VALIDE && ! $this->aDesReceptions();
    }

    public function estCloturable(): bool
    {
        return $this->statut === self::STATUT_PARTIEL;
    }

    public function aDesReceptions(): bool
    {
        return $this->lignes()->where('quantite_livree', '>', 0)->exists();
    }

    /** Diagnostic affiché en infobulle sur un bouton grisé (SPEC_UX §0.3). */
    public function diagnosticModification(): ?string
    {
        return $this->estModifiable()
            ? null
            : 'Bon validé — utilisez l\'annulation ou la clôture';
    }

    // ── Transitions (toutes les écritures de statut passent par ici) ───────

    public function soumettre(int $parUtilisateur): void
    {
        $this->verifierTransition([self::STATUT_BROUILLON], self::STATUT_SOUMIS);

        $this->forceFill([
            'statut' => self::STATUT_SOUMIS,
            'soumis_par' => $parUtilisateur,
            'soumis_le' => now(),
        ])->save();
    }

    /** Renvoi motivé par le validateur, ou reprise par l'auteur (SFD §7.1). */
    public function renvoyerEnBrouillon(): void
    {
        $this->verifierTransition([self::STATUT_SOUMIS], self::STATUT_BROUILLON);

        $this->forceFill([
            'statut' => self::STATUT_BROUILLON,
            'soumis_par' => null,
            'soumis_le' => null,
        ])->save();
    }

    public function annuler(string $motif): void
    {
        $this->verifierTransition([self::STATUT_VALIDE], self::STATUT_ANNULE);

        if ($this->aDesReceptions()) {
            throw TransitionInterditeException::avecReceptions($this->numero_affiche);
        }

        $this->forceFill([
            'statut' => self::STATUT_ANNULE,
            'motif_annulation' => $motif,
        ])->save();
    }

    public function cloturer(string $motif): void
    {
        $this->verifierTransition([self::STATUT_PARTIEL], self::STATUT_CLOTURE);

        $this->forceFill([
            'statut' => self::STATUT_CLOTURE,
            'motif_cloture' => $motif,
        ])->save();
    }

    protected function verifierTransition(array $depuis, string $vers): void
    {
        if (! in_array($this->statut, $depuis, true)) {
            throw TransitionInterditeException::pour($this->numero_affiche, $this->statut, $vers);
        }
    }

    protected static function newFactory()
    {
        return \Modules\Achat\Database\Factories\BonCommandeFactory::new();
    }
}
