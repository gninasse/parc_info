<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalogue\Models\Article;
use Modules\Organisation\Models\Local;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

class LigneSortie extends Model
{
    use HasFactory, JournaliseActiviteStock;

    protected $table = 'stock_lignes_sorties';

    protected $fillable = [
        'sortie_id',
        'article_id',
        'quantite',
        'emplacement_local_id',
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
    ];

    public function sortie(): BelongsTo
    {
        return $this->belongsTo(Sortie::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function emplacementLocal(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'emplacement_local_id');
    }

    public function tampons(): HasMany
    {
        return $this->hasMany(TamponEquipement::class, 'ligne_sortie_id');
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\LigneSortieFactory::new();
    }
}
