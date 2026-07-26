<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Achat\Models\Article;
use Modules\Stock\Traits\HasAuditFields;

class StockMouvement extends Model
{
    use HasAuditFields, SoftDeletes;

    protected $table = 'stock_mouvements';

    protected $fillable = [
        'type_mouvement',
        'article_id',
        'magasin_id',
        'quantite',
        'cout_unitaire',
        'type_origine',
        'origine_id',
        'reference_document',
        'motif',
        'valide_at',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'cout_unitaire' => 'decimal:4',
        'valide_at' => 'datetime',
    ];

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function affectation(): HasOne
    {
        return $this->hasOne(StockMouvementAffectation::class, 'mouvement_id');
    }
}
