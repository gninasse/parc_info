<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalogue\Models\Article;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

class LigneEntree extends Model
{
    use HasFactory, JournaliseActiviteStock;

    protected $table = 'stock_lignes_entrees';

    protected $fillable = [
        'entree_id',
        'article_id',
        'equipement_id',
        'quantite',
        'cout_unitaire',
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
        'cout_unitaire' => 'decimal:2',
    ];

    public function entree(): BelongsTo
    {
        return $this->belongsTo(Entree::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class);
    }

    public function tampons(): HasMany
    {
        return $this->hasMany(TamponEquipement::class, 'ligne_entree_id');
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\LigneEntreeFactory::new();
    }
}
