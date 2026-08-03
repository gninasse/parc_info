<?php

namespace Modules\Stock\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\PosteTravail;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Unite;

/**
 * Bénéficiaire à 6 types (D5) : FK `set null` + type + libellé dénormalisé
 * à la validation (l'historique survit à la suppression du bénéficiaire).
 */
trait PorteBeneficiaire
{
    public static function typesBeneficiaire(): array
    {
        return [
            'direction' => 'Direction',
            'service' => 'Service',
            'unite' => 'Unité',
            'poste' => 'Poste de travail',
            'local' => 'Local',
            'employe' => 'Employé',
        ];
    }

    public function beneficiaireDirection(): BelongsTo
    {
        return $this->belongsTo(Direction::class, 'beneficiaire_direction_id');
    }

    public function beneficiaireService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'beneficiaire_service_id');
    }

    public function beneficiaireUnite(): BelongsTo
    {
        return $this->belongsTo(Unite::class, 'beneficiaire_unite_id');
    }

    public function beneficiairePoste(): BelongsTo
    {
        return $this->belongsTo(PosteTravail::class, 'beneficiaire_poste_id');
    }

    public function beneficiaireLocal(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'beneficiaire_local_id');
    }

    public function beneficiaireEmploye(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'beneficiaire_employe_id');
    }
}
