<?php

namespace Modules\Achat\Models;

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

    /**
     * Get the attributes that should be cast.
     */
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

    /**
     * Get the brand of this article.
     */
    public function marque(): BelongsTo
    {
        return $this->belongsTo(Marque::class, 'marque_id');
    }

    /**
     * Get the equipment category (if type is equipment).
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieEquipement::class, 'categorie_equipement_id');
    }

    /**
     * Get the preferred supplier for this article.
     */
    public function fournisseurPrefere(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_prefere_id');
    }

    /**
     * Command lines that reference this article.
     */
    public function lignesCommande(): HasMany
    {
        return $this->hasMany(LigneCommande::class, 'article_id');
    }

    /**
     * Delivery lines that reference this article.
     */
    public function lignesLivraison(): HasMany
    {
        return $this->hasMany(LigneLivraison::class, 'article_id');
    }
}
