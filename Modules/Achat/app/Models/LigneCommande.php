<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneCommande extends Model
{
    protected $table = 'achat_lignes_commande';

    protected $fillable = [
        'bon_de_commande_id',
        'article_id',
        'quantite',
        'prix_unitaire',
        'quantite_livree',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'prix_unitaire' => 'decimal:2',
            'quantite_livree' => 'integer',
        ];
    }

    /**
     * Get the purchase order parent.
     */
    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(BonCommande::class, 'bon_de_commande_id');
    }

    /**
     * Get the referenced article.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * Get the remaining quantity to deliver.
     */
    public function getResteALivrerAttribute(): int
    {
        return max(0, $this->quantite - $this->quantite_livree);
    }

    /**
     * Get the line amount HT.
     */
    public function getMontantLigneAttribute(): float
    {
        return $this->quantite * $this->prix_unitaire;
    }
}
