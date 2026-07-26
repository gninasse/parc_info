<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DroitMagasin extends Model
{
    protected $table = 'stock_droits_magasin';

    protected $fillable = [
        'magasin_id',
        'type_sujet',
        'sujet_id',
        'peut_lire',
        'peut_entrer_stock',
        'peut_sortir_stock',
        'peut_transferer',
        'peut_inventorier',
        'peut_administrer',
    ];

    protected $casts = [
        'peut_lire' => 'boolean',
        'peut_entrer_stock' => 'boolean',
        'peut_sortir_stock' => 'boolean',
        'peut_transferer' => 'boolean',
        'peut_inventorier' => 'boolean',
        'peut_administrer' => 'boolean',
    ];

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }
}
