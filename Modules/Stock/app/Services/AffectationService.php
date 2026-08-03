<?php

namespace Modules\Stock\Services;

use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;

/**
 * Écritures d'affectations ParcInfo (S10/D8), dans la transaction de la
 * validation appelante. Sémantique alignée sur
 * EquipementDynamiqueController::desaffecter (statut booléen actif,
 * clôture = statut false + date_fin).
 */
class AffectationService
{
    /**
     * Retour d'une unité au stock (entrées nature « retour ») : clôture de
     * l'affectation active et statut « en_stock » — motif obligatoire
     * (§7.0), tracé dans l'activity log.
     */
    public function retournerEnStock(Equipement $equipement, string $motif): ?AffectationEquipement
    {
        $affectation = AffectationEquipement::query()
            ->where('equipement_id', $equipement->id)
            ->where('statut', true)
            ->latest('date_debut')
            ->first();

        AffectationEquipement::query()
            ->where('equipement_id', $equipement->id)
            ->where('statut', true)
            ->update(['statut' => false, 'date_fin' => now()]);

        $equipement->update(['statut' => 'en_stock', 'local_id' => null]);

        activity('stock')
            ->performedOn($equipement)
            ->withProperties(['motif' => $motif, 'affectation_cloturee' => $affectation?->id])
            ->log('retour_en_stock');

        return $affectation;
    }
}
