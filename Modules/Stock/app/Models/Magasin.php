<?php

namespace Modules\Stock\Models;

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
        'nom',
        'type',
        'description',
        'est_actif',
    ];

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\MagasinFactory::new();
    }

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    public function responsables(): HasMany
    {
        return $this->hasMany(ResponsableMagasin::class, 'magasin_id');
    }

    public function droits(): HasMany
    {
        return $this->hasMany(DroitMagasin::class, 'magasin_id');
    }

    public function stockArticles(): HasMany
    {
        return $this->hasMany(StockArticleMagasin::class, 'magasin_id');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(StockMouvement::class, 'magasin_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(StockLot::class, 'magasin_id');
    }

    public function estSupprimable(): bool
    {
        // Supprimable s'il n'y a pas de mouvement lié
        return ! $this->mouvements()->exists() && ! $this->lots()->exists();
    }
}
