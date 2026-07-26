<?php

namespace Modules\Achat\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Achat\Models\BordereauLivraison;

/**
 * Émis après l'intégration effective du bordereau au parc.
 *
 * Correction AN-08 : cet événement est désormais réellement déclenché par
 * WizardValidationService. Il est émis dans la transaction d'intégration ; un
 * écouteur qui échoue annule donc l'intégration complète.
 *
 * @param  array<int>  $equipements  Identifiants des équipements créés
 * @param  array<int>  $licences  Identifiants des licences créées
 */
class BordereauLivraisonValide
{
    use SerializesModels;

    public function __construct(
        public readonly BordereauLivraison $bordereau,
        public readonly int $userId,
        public readonly array $equipements = [],
        public readonly array $licences = [],
    ) {}
}
