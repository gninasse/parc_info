<?php

namespace Modules\Stock\Support;

/**
 * Alerte informative d'écart de coût (SFD §9.3) : le coût saisi s'écarte du
 * prix indicatif du catalogue de plus de ± stock.seuil_alerte_cout (20 %).
 * Même calcul que le popover JS du brouillon — non bloquant.
 */
final class EcartCout
{
    public static function estSuspect(?float $coutSaisi, ?float $prixIndicatif): bool
    {
        if ($coutSaisi === null || $prixIndicatif === null || $prixIndicatif <= 0) {
            return false;
        }

        $seuil = (float) config('stock.seuil_alerte_cout', 0.20);

        return abs($coutSaisi - $prixIndicatif) / $prixIndicatif > $seuil;
    }
}
