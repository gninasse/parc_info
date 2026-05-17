<?php

namespace Modules\ParcInfo\Services;

use Modules\ParcInfo\Models\AffectationLicence;
use Modules\ParcInfo\Models\Licence;
use Modules\ParcInfo\Models\Logiciel;

class LicenceService
{
    public function detecterSurexploitations()
    {
        return Licence::where('actif', true)
            ->whereRaw('nombre_postes_utilises > nombre_postes_accordes')
            ->where('nombre_postes_accordes', '>', 0)
            ->get();
    }

    public function calculerROI(Logiciel $logiciel)
    {
        $licences = $logiciel->licences()->where('actif', true)->get();
        $cout_total = $licences->sum('cout_total');
        $affectations = AffectationLicence::whereIn('licence_id', $licences->pluck('id'))
            ->where('actif', true)
            ->count();

        return [
            'cout_total' => $cout_total,
            'utilisateurs' => $affectations,
            'cout_par_utilisateur' => $affectations > 0 ? round($cout_total / $affectations, 2) : 0,
        ];
    }

    public function optimiserAllocation()
    {
        $recommandations = [];

        Licence::where('actif', true)->each(function ($licence) use (&$recommandations) {
            $taux = $licence->taux_utilisation;

            if ($taux < 30 && $licence->nombre_postes_accordes > 5) {
                $recommandations[] = [
                    'type' => 'sous_exploitee',
                    'logiciel' => $licence->logiciel->nom,
                    'taux' => $taux,
                    'action' => 'Réduire nombre de postes ou supprimer',
                ];
            }

            if ($taux > 90) {
                $recommandations[] = [
                    'type' => 'surexploitee',
                    'logiciel' => $licence->logiciel->nom,
                    'taux' => $taux,
                    'action' => 'Augmenter licences immédiatement',
                ];
            }
        });

        return $recommandations;
    }

    public function rapportConformite()
    {
        return [
            'licences_expirees' => Licence::expire()->count(),
            'licences_en_alerte' => Licence::expirantProchainement()->count(),
            'licences_surexploitees' => Licence::enSurexploitation()->count(),
            'taux_conformite' => $this->calculerTauxConformite(),
        ];
    }

    private function calculerTauxConformite()
    {
        $total = Licence::where('actif', true)->count();
        if ($total === 0) {
            return 100;
        }

        $conformes = Licence::where('actif', true)
            ->where('statut', 'actif')
            ->whereDate('date_expiration', '>=', now())
            ->count();

        return round(($conformes / $total) * 100, 2);
    }
}
