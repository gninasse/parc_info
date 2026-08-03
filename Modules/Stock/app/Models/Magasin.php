<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\Site;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

class Magasin extends Model
{
    use HasFactory, JournaliseActiviteStock;

    protected $table = 'stock_magasins';

    protected $fillable = [
        'code',
        'libelle',
        'site_id',
        'local_id',
        'responsable_id',
        'est_actif',
        'created_by',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'responsable_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function niveaux(): HasMany
    {
        return $this->hasMany(Niveau::class, 'magasin_id');
    }

    public function entrees(): HasMany
    {
        return $this->hasMany(Entree::class, 'magasin_id');
    }

    public function sorties(): HasMany
    {
        return $this->hasMany(Sortie::class, 'magasin_id');
    }

    public function transfertsSortants(): HasMany
    {
        return $this->hasMany(Transfert::class, 'magasin_source_id');
    }

    public function transfertsEntrants(): HasMany
    {
        return $this->hasMany(Transfert::class, 'magasin_cible_id');
    }

    public function inventaires(): HasMany
    {
        return $this->hasMany(Inventaire::class, 'magasin_id');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(Mouvement::class, 'magasin_id');
    }

    public function equipementsRattaches(): HasMany
    {
        return $this->hasMany(EquipementMagasin::class, 'magasin_id');
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('est_actif', true);
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\MagasinFactory::new();
    }
}
