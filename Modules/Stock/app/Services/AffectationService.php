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
     * Affectation D8 à la validation d'une sortie : cible = bénéficiaire du
     * bon, date_debut = validation, emplacement optionnel, unité « en
     * service ». Mapping des 6 types Stock vers le modèle ParcInfo :
     * employé/poste/local → type_cible EMPLOYE/POSTE/LOCAL ;
     * direction/service/unité → niveau_rattachement (type_cible null),
     * comme les affectations organisationnelles existantes.
     */
    public function affecter(
        Equipement $equipement,
        string $beneficiaireType,
        ?int $beneficiaireId,
        ?int $emplacementLocalId = null
    ): AffectationEquipement {
        $attributs = match ($beneficiaireType) {
            'employe' => ['type_cible' => 'EMPLOYE', 'dossier_employe_id' => $beneficiaireId],
            'poste' => ['type_cible' => 'POSTE', 'poste_travail_id' => $beneficiaireId],
            'local' => ['type_cible' => 'LOCAL', 'local_id' => $beneficiaireId],
            'direction' => ['niveau_rattachement' => 'DIRECTION', 'direction_id' => $beneficiaireId],
            'service' => ['niveau_rattachement' => 'SERVICE', 'service_id' => $beneficiaireId],
            'unite' => ['niveau_rattachement' => 'UNITE', 'unite_id' => $beneficiaireId],
            default => [],
        };

        if ($emplacementLocalId !== null && ! isset($attributs['local_id'])) {
            $attributs['local_id'] = $emplacementLocalId;
        }

        $affectation = AffectationEquipement::create(array_merge([
            'code' => 'AFF-'.strtoupper(uniqid()),
            'equipement_id' => $equipement->id,
            'statut' => true,
            'type_affectation' => 'PERMANENTE',
            'date_debut' => now()->format('Y-m-d'),
        ], $attributs));

        $equipement->update(array_filter([
            'statut' => 'en_service',
            'local_id' => $emplacementLocalId,
        ], fn ($valeur) => $valeur !== null));

        return $affectation;
    }

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
