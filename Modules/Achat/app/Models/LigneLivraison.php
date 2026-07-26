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
        'quantite_refusee',
        'motif_refus',
    ];

    protected function casts(): array
    {
        return [
            'quantite_livree' => 'integer',
            'quantite_refusee' => 'integer',
        ];
    }

    public function bordereauLivraison(): BelongsTo
    {
        return $this->belongsTo(BordereauLivraison::class, 'bordereau_livraison_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /** Ligne de commande correspondante sur le bon rattaché. */
    public function ligneCommande(): ?LigneCommande
    {
        return LigneCommande::where('bon_de_commande_id', $this->bordereauLivraison->bon_de_commande_id)
            ->where('article_id', $this->article_id)
            ->first();
    }
}
