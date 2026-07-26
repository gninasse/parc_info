<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Achat\Traits\HasAuditFields;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Marque;

class Article extends Model
{
    use HasAuditFields, HasFactory, SoftDeletes;

    protected $table = 'achat_articles';

    protected $fillable = [
        'code_article',
        'designation',
        'description',
        'type_article',
        'reference_constructeur',
        'marque_id',
        'categorie_equipement_id',
        'fournisseur_prefere_id',
        'prix_indicatif',
        'unite_mesure',
        'taux_tva',
        'compte_comptable',
        'seuil_alerte',
        'stock_actuel',
        'duree_validite_mois',
        'url_fiche_technique',
        'image',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'prix_indicatif' => 'decimal:2',
            'taux_tva' => 'decimal:2',
            'seuil_alerte' => 'integer',
            'stock_actuel' => 'integer',
            'duree_validite_mois' => 'integer',
            'actif' => 'boolean',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────

    public function marque(): BelongsTo
    {
        return $this->belongsTo(Marque::class, 'marque_id');
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieEquipement::class, 'categorie_equipement_id');
    }

    public function fournisseurPrefere(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_prefere_id');
    }

    public function lignesCommande(): HasMany
    {
        return $this->hasMany(LigneCommande::class, 'article_id');
    }

    public function lignesLivraison(): HasMany
    {
        return $this->hasMany(LigneLivraison::class, 'article_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeConsommables(Builder $query): Builder
    {
        return $query->where('type_article', 'consommable');
    }

    public function scopeSousSeuil(Builder $query): Builder
    {
        return $query->whereColumn('stock_actuel', '<=', 'seuil_alerte');
    }

    // ── Règles métier ──────────────────────────────────────────────────────

    /**
     * RG-ART-10 : un article engagé dans une commande n'est jamais supprimé,
     * il est désactivé.
     */
    public function estSupprimable(): bool
    {
        return ! $this->lignesCommande()->exists();
    }

    /** RG-WZ-03 : le type impose-t-il une saisie d'inventaire unitaire ? */
    public function necessiteWizard(): bool
    {
        return in_array($this->type_article, config('achat.types_avec_wizard', []), true);
    }

    /** Le type alimente-t-il le stock physique à la réception ? */
    public function alimenteStock(): bool
    {
        return in_array($this->type_article, config('achat.types_avec_stock', []), true);
    }

    // ── Accesseurs ─────────────────────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return config("achat.types_articles.{$this->type_article}", $this->type_article);
    }

    /**
     * EF-STK-07 : niveau de stock qualifié par rapport au seuil d'alerte.
     * Retourne l'une des clés de config('achat.seuils_stock').
     */
    public function getNiveauStockAttribute(): string
    {
        if ($this->stock_actuel <= 0) {
            return 'rupture';
        }

        if ($this->seuil_alerte <= 0) {
            return 'normal';
        }

        $ratio = $this->stock_actuel / $this->seuil_alerte;

        foreach (config('achat.seuils_stock', []) as $cle => $seuil) {
            if ($seuil['max_ratio'] !== null && $ratio <= $seuil['max_ratio']) {
                return $cle;
            }
        }

        return 'normal';
    }
}
