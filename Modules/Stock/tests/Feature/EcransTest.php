<?php

namespace Modules\Stock\Tests\Feature;

class EcransTest extends StockTestCase
{
    public function test_le_tableau_de_bord_s_affiche(): void
    {
        $this->get(route('stock.dashboard.index'))->assertOk();
    }

    public function test_les_donnees_du_tableau_de_bord_sont_structurees(): void
    {
        $this->creerMagasin();

        $this->getJson(route('stock.dashboard.data'))
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'magasins_actifs', 'articles_en_stock', 'valeur_totale',
                'mouvements_du_jour', 'derniers_mouvements',
            ]]);
    }

    public function test_un_invite_est_redirige_vers_la_connexion(): void
    {
        auth()->logout();

        $this->get(route('stock.magasins.index'))->assertRedirect();
    }
}
