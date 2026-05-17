<?php

namespace Modules\ParcInfo\Services;

use Modules\ParcInfo\Models\AffectationConsommable;
use Modules\ParcInfo\Models\Consommable;

class GestionStockService
{
    public function detecterRuptures()
    {
        return Consommable::enRupture()
            ->with(['typeConsommable', 'fournisseur'])
            ->get();
    }

    public function calculerRecommandations()
    {
        return Consommable::actifs()
            ->get()
            ->filter(fn ($c) => $c->statut_stock === 'ALERTE' || $c->statut_stock === 'RUPTURE')
            ->map(function ($consommable) {
                $quantite_a_commander = $consommable->quantite_stock_max - $consommable->quantite_stock_actuel;

                return [
                    'consommable' => $consommable,
                    'quantite_a_commander' => $quantite_a_commander,
                    'cout_previsionnel' => $quantite_a_commander * $consommable->cout_unitaire,
                    'urgence' => $consommable->quantite_stock_actuel <= 0 ? 'CRITIQUE' : 'NORMAL',
                ];
            });
    }

    public function detecterRenouvellementsPrevus()
    {
        return AffectationConsommable::with(['consommable', 'equipement'])
            ->where('date_remplacement_prochain_prevu', '<=', now()->addDays(7))
            ->get();
    }
}
