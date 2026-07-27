<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Achat\Traits\GeneratesDocumentNumbers;
use Modules\Achat\Traits\HasAuditFields;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\Fournisseur;

class BonCommande extends Model
{
    use GeneratesDocumentNumbers, HasAuditFields, HasFactory, SoftDeletes;

    protected $table = 'achat_bons_commande';

    protected $fillable = [
        'numero_commande',
        'fournisseur_id',
        'date_commande',
        'statut',
        'montant_ht',
        'montant_tva',
        'montant_ttc',
        'commentaire',
        'valide_par',
        'date_validation',
        'annule_par',
        'date_annulation',
        'motif_annulation',
        'cloture_par',
        'date_cloture',
        'motif_cloture',
    ];

    protected function casts(): array
    {
        return [
            'date_commande' => 'date',
            'date_validation' => 'datetime',
            'date_annulation' => 'datetime',
            'date_cloture' => 'datetime',
            'montant_ht' => 'decimal:2',
            'montant_tva' => 'decimal:2',
            'montant_ttc' => 'decimal:2',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function annulateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'annule_par');
    }

    public function clotureur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cloture_par');
    }

    public function lignesCommande(): HasMany
    {
        return $this->hasMany(LigneCommande::class, 'bon_de_commande_id');
    }

    public function bordereauxLivraison(): HasMany
    {
        return $this->hasMany(BordereauLivraison::class, 'bon_de_commande_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    /** RG-BC-08 : périmètre des bons engageant réellement de la dépense. */
    public function scopeEngages(Builder $query): Builder
    {
        return $query->whereIn('statut', ['valide', 'partiel', 'livre', 'cloture']);
    }

    /** RG-BL-01 : bons pouvant recevoir une livraison. */
    public function scopeLivrables(Builder $query): Builder
    {
        return $query->whereIn('statut', ['valide', 'partiel']);
    }

    // ── Règles métier ──────────────────────────────────────────────────────

    /** RGC-01 / RG-BC-03 : seul un brouillon est modifiable. */
    public function estModifiable(): bool
    {
        return $this->statut === 'brouillon';
    }

    /** RG-BC-03 : seul un brouillon est supprimable. */
    public function estSupprimable(): bool
    {
        return $this->statut === 'brouillon' && ! $this->bordereauxLivraison()->exists();
    }

    /** RG-BC-04 : brouillon disposant d'au moins une ligne. */
    public function estValidable(): bool
    {
        return $this->statut === 'brouillon' && $this->lignesCommande()->exists();
    }

    /** RG-BC-05 / RGC-10 : jamais annulable dès qu'une livraison existe. */
    public function estAnnulable(): bool
    {
        return in_array($this->statut, ['brouillon', 'valide'], true)
            && ! $this->bordereauxLivraison()->where('statut', 'valide')->exists();
    }

    /** EF-BC-18 : clôture d'un reliquat abandonné, sans annuler les livraisons. */
    public function estCloturable(): bool
    {
        return $this->statut === 'partiel';
    }

    /** RG-BL-01 : le bon peut-il recevoir un nouveau bordereau ? */
    public function accepteLivraison(): bool
    {
        return in_array($this->statut, ['valide', 'partiel'], true);
    }

    // ── Accesseurs calculés ────────────────────────────────────────────────

    public function getQuantiteTotaleAttribute(): int
    {
        return (int) $this->lignesCommande->sum('quantite');
    }

    public function getQuantiteLivreeAttribute(): int
    {
        return (int) $this->lignesCommande->sum('quantite_livree');
    }

    public function getResteALivrerAttribute(): int
    {
        return max(0, $this->quantite_totale - $this->quantite_livree);
    }

    public function getEstEntierementLivreAttribute(): bool
    {
        return $this->quantite_livree >= $this->quantite_totale;
    }

    public function getStatutLabelAttribute(): string
    {
        return config("achat.statuts_bc.{$this->statut}.label", $this->statut);
    }

    public function getStatutColorAttribute(): string
    {
        return config("achat.statuts_bc.{$this->statut}.color", 'secondary');
    }

    protected static function newFactory()
    {
        return \Modules\Achat\Database\Factories\BonCommandeFactory::new();
    }
}
