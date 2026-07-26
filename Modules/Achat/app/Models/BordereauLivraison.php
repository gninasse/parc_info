<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Achat\Traits\GeneratesDocumentNumbers;
use Modules\Achat\Traits\HasAuditFields;
use Modules\Core\Models\User;

class BordereauLivraison extends Model
{
    use GeneratesDocumentNumbers, HasAuditFields, HasFactory, SoftDeletes;

    protected $table = 'achat_bordereaux_livraison';

    protected $fillable = [
        'numero_livraison',
        'bon_de_commande_id',
        'date_livraison',
        'ref_bordereau_physique',
        'statut',
        'commentaire',
        'valide_par',
        'date_validation',
    ];

    protected function casts(): array
    {
        return [
            'date_livraison' => 'date',
            'date_validation' => 'datetime',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────

    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(BonCommande::class, 'bon_de_commande_id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function lignesLivraison(): HasMany
    {
        return $this->hasMany(LigneLivraison::class, 'bordereau_livraison_id');
    }

    public function wizardData(): HasMany
    {
        return $this->hasMany(WizardData::class, 'bordereau_livraison_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    // ── Règles métier ──────────────────────────────────────────────────────

    /** RG-BL-06 : seul un brouillon est modifiable. */
    public function estModifiable(): bool
    {
        return $this->statut === 'brouillon';
    }

    /** RG-BL-06 : seul un brouillon est supprimable. */
    public function estSupprimable(): bool
    {
        return $this->statut === 'brouillon';
    }

    /** RG-WZ-01 : l'assistant est accessible en brouillon ou déjà engagé. */
    public function peutLancerWizard(): bool
    {
        return in_array($this->statut, ['brouillon', 'wizard'], true);
    }

    /**
     * EF-BL-12 — Retour arrière possible tant qu'aucune intégration n'a été
     * finalisée. Corrige l'irréversibilité d'une ouverture accidentelle de
     * l'assistant.
     */
    public function peutRevenirEnBrouillon(): bool
    {
        return $this->statut === 'wizard';
    }

    /** RGC-03 : un bordereau validé est définitivement figé. */
    public function estValide(): bool
    {
        return $this->statut === 'valide';
    }

    // ── Accesseurs calculés ────────────────────────────────────────────────

    public function getQuantiteTotaleAttribute(): int
    {
        return (int) $this->lignesLivraison->sum('quantite_livree');
    }

    /** RG-WZ-03 : lignes imposant une saisie d'inventaire unitaire. */
    public function lignesNecessitantWizard()
    {
        return $this->lignesLivraison->filter(
            fn (LigneLivraison $ligne) => $ligne->article?->necessiteWizard() ?? false
        );
    }

    public function getStatutLabelAttribute(): string
    {
        return config("achat.statuts_bl.{$this->statut}.label", $this->statut);
    }

    public function getStatutColorAttribute(): string
    {
        return config("achat.statuts_bl.{$this->statut}.color", 'secondary');
    }
}
