<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

class LigneTransfert extends Model
{
    use HasFactory, JournaliseActiviteStock;

    protected $table = 'stock_lignes_transferts';

    protected $fillable = [
        'transfert_id',
        'article_id',
        'quantite',
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
    ];

    public function transfert(): BelongsTo
    {
        return $this->belongsTo(Transfert::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function tampons(): HasMany
    {
        return $this->hasMany(TamponEquipement::class, 'ligne_transfert_id');
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\LigneTransfertFactory::new();
    }
}
