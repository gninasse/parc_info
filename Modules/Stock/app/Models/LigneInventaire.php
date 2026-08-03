<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalogue\Models\Article;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

class LigneInventaire extends Model
{
    use HasFactory, JournaliseActiviteStock;

    public const POINTAGE_PRESENT = 'PRESENT';

    public const POINTAGE_ABSENT = 'ABSENT';

    public const POINTAGE_TROUVE = 'TROUVE';

    protected $table = 'stock_lignes_inventaire';

    protected $fillable = [
        'inventaire_id',
        'article_id',
        'equipement_id',
        'quantite_theorique',
        'quantite_physique',
        'pointage',
        'mouvement_id',
    ];

    protected $casts = [
        'quantite_theorique' => 'decimal:2',
        'quantite_physique' => 'decimal:2',
    ];

    public function inventaire(): BelongsTo
    {
        return $this->belongsTo(Inventaire::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class);
    }

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(Mouvement::class, 'mouvement_id');
    }

    /** Écart de comptage — null tant que la ligne n'est pas comptée. */
    public function getEcartAttribute(): ?float
    {
        if ($this->quantite_physique === null) {
            return null;
        }

        return (float) $this->quantite_physique - (float) $this->quantite_theorique;
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\LigneInventaireFactory::new();
    }
}
