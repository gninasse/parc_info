<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Stock\Traits\HasAuditFields;

class Magasin extends Model
{
    use HasAuditFields, HasFactory, SoftDeletes;

    protected $table = 'stock_magasins';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'statut',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function responsables(): HasMany
    {
        return $this->hasMany(ResponsableMagasin::class, 'magasin_id');
    }

    public function stocksArticles(): HasMany
    {
        return $this->hasMany(StockArticleMagasin::class, 'magasin_id');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(StockMouvement::class, 'magasin_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('statut', 'actif');
    }

    // ── Méthodes métier ────────────────────────────────────────────────────

    public function estActif(): bool
    {
        return $this->statut === 'actif';
    }

    /** RG-F1-03 est portée par les services ; ici l'état seulement. */
    public function estSupprimable(): bool
    {
        return ! $this->mouvements()->exists()
            && ! $this->stocksArticles()->where('quantite_actuelle', '>', 0)->exists();
    }

    public function responsablePrincipal(): ?ResponsableMagasin
    {
        return $this->responsables
            ->firstWhere(fn (ResponsableMagasin $r) => $r->role === 'principal' && $r->estEnCours());
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\MagasinFactory::new();
    }
}
