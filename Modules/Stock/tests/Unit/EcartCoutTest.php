<?php

namespace Modules\Stock\Tests\Unit;

use Modules\Stock\Support\EcartCout;
use Tests\TestCase;

/**
 * Alerte de coût ±20 % (config stock.seuil_alerte_cout) — même calcul que le
 * popover JS du brouillon.
 */
class EcartCoutTest extends TestCase
{
    public function test_ecart_au_dela_du_seuil_est_suspect(): void
    {
        // prix indicatif 38 000 : ±20 % → [30 400, 45 600]
        $this->assertTrue(EcartCout::estSuspect(46000.0, 38000.0));
        $this->assertTrue(EcartCout::estSuspect(30000.0, 38000.0));
    }

    public function test_ecart_dans_le_seuil_est_accepte(): void
    {
        $this->assertFalse(EcartCout::estSuspect(38000.0, 38000.0));
        $this->assertFalse(EcartCout::estSuspect(45600.0, 38000.0)); // exactement +20 % : non suspect
        $this->assertFalse(EcartCout::estSuspect(31000.0, 38000.0));
    }

    public function test_sans_prix_indicatif_ou_sans_cout_jamais_suspect(): void
    {
        $this->assertFalse(EcartCout::estSuspect(null, 38000.0));
        $this->assertFalse(EcartCout::estSuspect(46000.0, null));
        $this->assertFalse(EcartCout::estSuspect(46000.0, 0.0));
    }

    public function test_le_seuil_vient_de_la_config(): void
    {
        config(['stock.seuil_alerte_cout' => 0.5]);

        $this->assertFalse(EcartCout::estSuspect(50000.0, 38000.0)); // +31 % < 50 %
        $this->assertTrue(EcartCout::estSuspect(60000.0, 38000.0)); // +58 % > 50 %
    }
}
