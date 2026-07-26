<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Grh\Models\Employe;

class ResponsableMagasin extends Model
{
    protected $table = 'stock_responsables_magasin';

    protected $fillable = [
        'magasin_id',
        'employe_id',
        'role',
        'date_debut',
        'date_fin',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'employe_id');
    }
}
