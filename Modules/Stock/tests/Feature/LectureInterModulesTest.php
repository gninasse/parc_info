<?php

namespace Modules\Stock\Tests\Feature;

use Modules\ParcInfo\Models\Consommable;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\TypeConsommable;
use Modules\Stock\Services\EntreeStockService;

/**
 * EF-STK-05 — Les écrans des autres modules lisent le stock via les contrats,
 * sans mock : E-14 (Achat) et la liste des consommables (ParcInfo) affichent
 * les quantités du module Stock.
 */
class LectureInterModulesTest extends StockTestCase
{
    public function test_l_ecran_e14_achat_affiche_les_quantites_du_module_stock(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable(['seuil_alerte' => 3]);

        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 7,
            'cout_unitaire' => 500,
        ], $this->utilisateur->id);

        $reponse = $this->getJson(route('achat.stocks.data'))->assertOk()->json();

        $this->assertSame(1, $reponse['total']);
        $this->assertSame(7, $reponse['rows'][0]['stock_actuel']);
        $this->assertSame(3500.0, (float) $reponse['rows'][0]['valeur_fifo']);
        $this->assertSame('normal', $reponse['rows'][0]['niveau']);
    }

    public function test_la_liste_des_consommables_parcinfo_lit_le_module_stock(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();

        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 5,
            'cout_unitaire' => 200,
        ], $this->utilisateur->id);

        // Fiche catalogue ParcInfo liée à l'article par article_id.
        $type = TypeConsommable::create([
            'code' => 'GEN-CONS', 'nom' => 'Divers', 'categorie' => 'Accessoires',
            'unite_stock' => 'Unité', 'seul_reapprovisionnement' => 5,
        ]);
        $fournisseur = Fournisseur::create(['code' => 'FRS-01', 'nom' => 'Fournisseur', 'est_actif' => true]);

        Consommable::create([
            'code' => $article->code_article,
            'nom' => $article->designation,
            'article_id' => $article->id,
            'type_consommable_id' => $type->id,
            'fournisseur_principal_id' => $fournisseur->id,
            'cout_unitaire' => 200,
            'quantite_stock_min' => 2,
            'est_actif' => true,
        ]);

        $reponse = $this->getJson(route('parc-info.consommables.data'))->assertOk()->json();

        $this->assertSame(1, $reponse['total']);
        $this->assertSame(5, $reponse['rows'][0]['stock_actuel']);
        $this->assertSame('NORMAL', $reponse['rows'][0]['statut_stock']);
        $this->assertSame(1000, (int) $reponse['stats']['valeur_totale']);
    }
}
