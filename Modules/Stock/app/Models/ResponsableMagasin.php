<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Responsable (principal ou adjoint) d'un magasin — information métier.
 *
 * L'employé est référencé par son identifiant grh_dossiers_employes ; son
 * libellé s'obtient via GrhIntegrationInterface, jamais par un modèle Grh.
 */
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

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function estEnCours(): bool
    {
        return $this->date_fin === null || $this->date_fin->isFuture();
    }
}
