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
            'duree_validite_mois' => 'integer',
            'actif' => 'boolean',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────
    // Les belongsTo vers les référentiels ParcInfo (Marque, CategorieEquipement,
    // Fournisseur) sont une dépendance admise par PATTERNS §11 ; tout autre accès
    // à ParcInfo passe par ParcInfoIntegrationInterface.

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

    // ── Accesseurs ─────────────────────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return config("achat.types_articles.{$this->type_article}", $this->type_article);
    }


    protected static function newFactory()
    {
        return \Modules\Achat\Database\Factories\ArticleFactory::new();
    }
}
