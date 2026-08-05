<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Collection;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;

/**
 * Calcul des montants — LE SEUL ENDROIT du module où l'on additionne
 * (convention 5, IA-1).
 *
 * Les montants sont calculés côté serveur à partir des valeurs FIGÉES à la
 * ligne, jamais recalculés en JavaScript pour l'enregistrement, puis
 * dénormalisés sur le bon. Écran, PDF et exports lisent la même source : ils
 * ne peuvent donc pas diverger.
 *
 * Arrondi : chaque ligne est arrondie à 2 décimales AVANT sommation, et la
 * TVA est calculée par ligne puis sommée. Sommer d'abord et arrondir ensuite
 * donnerait des écarts d'un franc entre l'écran et le PDF ; la règle retenue
 * est donc « arrondir au plus tôt, une seule fois par ligne ».
 */
class CalculMontantsService
{
    /** Montant HT d'une ligne : quantité × prix unitaire figé. */
    public function montantHtLigne(LigneCommande $ligne): float
    {
        return round((float) $ligne->quantite * (float) $ligne->prix_unitaire_ht, 2);
    }

    /** TVA d'une ligne, au taux figé à l'ajout. */
    public function montantTvaLigne(LigneCommande $ligne): float
    {
        return round($this->montantHtLigne($ligne) * (float) $ligne->taux_tva / 100, 2);
    }

    /**
     * Recalcule et enregistre les montants du bon à partir de ses lignes.
     * Appelée à chaque écriture de ligne, dans la transaction appelante.
     *
     * @return array{montant_ht: float, montant_tva: float, montant_ttc: float}
     */
    public function recalculer(BonCommande $bon): array
    {
        $totaux = $this->totaux($bon->lignes()->get());

        $bon->forceFill($totaux)->save();

        return $totaux;
    }

    /**
     * Totaux d'une collection de lignes, sans écriture — sert au récapitulatif
     * de l'étape ② et au Swal de visa, qui doivent afficher exactement ce qui
     * sera enregistré.
     *
     * @param  Collection<int, LigneCommande>  $lignes
     * @return array{montant_ht: float, montant_tva: float, montant_ttc: float}
     */
    public function totaux(Collection $lignes): array
    {
        $ht = 0.0;
        $tva = 0.0;

        foreach ($lignes as $ligne) {
            $ht += $this->montantHtLigne($ligne);
            $tva += $this->montantTvaLigne($ligne);
        }

        $ht = round($ht, 2);
        $tva = round($tva, 2);

        return [
            'montant_ht' => $ht,
            'montant_tva' => $tva,
            'montant_ttc' => round($ht + $tva, 2),
        ];
    }

    /**
     * Décomposition par taux de TVA — PO-02 « Décomposition des totaux »,
     * l'outil de diagnostic quand un total surprend (SPEC_UX A-03).
     *
     * @param  Collection<int, LigneCommande>  $lignes
     * @return list<array{taux: float, base_ht: float, tva: float, ttc: float}>
     */
    public function decompositionParTaux(Collection $lignes): array
    {
        $parTaux = [];

        foreach ($lignes as $ligne) {
            $taux = (float) $ligne->taux_tva;
            $cle = (string) $taux;

            $parTaux[$cle] ??= ['taux' => $taux, 'base_ht' => 0.0, 'tva' => 0.0, 'ttc' => 0.0];
            $parTaux[$cle]['base_ht'] += $this->montantHtLigne($ligne);
            $parTaux[$cle]['tva'] += $this->montantTvaLigne($ligne);
        }

        $decomposition = array_map(function (array $entree) {
            $entree['base_ht'] = round($entree['base_ht'], 2);
            $entree['tva'] = round($entree['tva'], 2);
            $entree['ttc'] = round($entree['base_ht'] + $entree['tva'], 2);

            return $entree;
        }, array_values($parTaux));

        usort($decomposition, fn (array $a, array $b) => $a['taux'] <=> $b['taux']);

        return $decomposition;
    }
}
