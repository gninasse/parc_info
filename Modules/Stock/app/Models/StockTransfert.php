<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Achat\Models\Article;
use Modules\Stock\Traits\GeneratesDocumentNumbers;
use Modules\Stock\Traits\HasAuditFields;

class StockTransfert extends Model
{
    use GeneratesDocumentNumbers, HasAuditFields, SoftDeletes;

    protected $table = 'stock_transferts';

    protected $fillable = [
        'numero_transfert',
        'magasin_source_id',
        'magasin_destination_id',
        'article_id',
        'quantite',
        'statut',
        'motif_creation',
        'motif_rejet',
        'valide_par',
        'date_validation',
        'mouvement_sortant_id',
        'mouvement_entrant_id',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'date_validation' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_source_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_destination_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\User::class, 'valide_par');
    }

    public function mouvementSortant(): BelongsTo
    {
        return $this->belongsTo(StockMouvement::class, 'mouvement_sortant_id');
    }

    public function mouvementEntrant(): BelongsTo
    {
        return $this->belongsTo(StockMouvement::class, 'mouvement_entrant_id');
    }
}
