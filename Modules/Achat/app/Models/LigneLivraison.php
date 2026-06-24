<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneLivraison extends Model
{
    protected $table = 'achat_lignes_livraison';

    protected $fillable = [
        'bordereau_livraison_id',
        'article_id',
        'quantite_livree',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'quantite_livree' => 'integer',
        ];
    }

    /**
     * Get the delivery slip parent.
     */
    public function bordereauLivraison(): BelongsTo
    {
        return $this->belongsTo(BordereauLivraison::class, 'bordereau_livraison_id');
    }

    /**
     * Get the delivered article.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
