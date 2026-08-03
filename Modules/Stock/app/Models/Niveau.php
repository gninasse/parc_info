<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

class Niveau extends Model
{
    use HasFactory, JournaliseActiviteStock;

    public const STATUT_OK = 'OK';

    public const STATUT_SOUS_SEUIL = 'SOUS_SEUIL';

    public const STATUT_RUPTURE = 'RUPTURE';

    public const ORIGINE_SEUIL_LOCAL = 'local';

    public const ORIGINE_SEUIL_ARTICLE = 'article';

    protected $table = 'stock_niveaux';

    protected $fillable = [
        'magasin_id',
        'article_id',
        'quantite',
        'seuil',
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
        'seuil' => 'decimal:2',
    ];

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function scopeDuMagasin(Builder $query, int $magasinId): Builder
    {
        return $query->where('magasin_id', $magasinId);
    }

    /**
     * Seuil effectif (§7.6) — cascade à deux niveaux :
     * seuil local → catalogue_articles.seuil_defaut → aucun.
     */
    public function getSeuilEffectifAttribute(): ?float
    {
        $seuil = $this->seuil ?? $this->article?->seuil_defaut;

        return $seuil === null ? null : (float) $seuil;
    }

    public function getSeuilOrigineAttribute(): ?string
    {
        if ($this->seuil !== null) {
            return self::ORIGINE_SEUIL_LOCAL;
        }

        return $this->article?->seuil_defaut !== null ? self::ORIGINE_SEUIL_ARTICLE : null;
    }

    public function getStatutAlerteAttribute(): string
    {
        if ((float) $this->quantite <= 0) {
            return self::STATUT_RUPTURE;
        }

        $seuil = $this->seuil_effectif;

        if ($seuil !== null && (float) $this->quantite <= $seuil) {
            return self::STATUT_SOUS_SEUIL;
        }

        return self::STATUT_OK;
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\NiveauFactory::new();
    }
}
