<?php

namespace Modules\Stock\Tests\Feature;

use Modules\Achat\Services\WizardValidationService;
use Modules\Achat\Tests\Feature\AchatTestCase;
use Modules\Stock\Models\Magasin;

/**
 * Intégration réelle Achat → Stock, sans aucun mock : la validation d'un
 * bordereau de livraison de consommables doit produire mouvement, lot FIFO
 * et projection dans le magasin de réception paramétré.
 */
class EntreeDepuisBlTest extends AchatTestCase
{
    protected Magasin $magasin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->magasin = Magasin::create([
            'code' => config('stock.magasin_reception_defaut', 'MAG-PRINCIPAL'),
            'libelle' => 'Magasin principal',
            'statut' => 'actif',
        ]);
    }

    /** Conserve l'implémentation réelle de StockIntegrationInterface. */
    protected function neutraliserIntegrationStock(): void
    {
        // Volontairement vide : ce test vérifie la chaîne complète.
    }

    public function test_la_validation_d_un_bl_consommable_alimente_le_stock_fifo(): void
    {
        $article = $this->creerConsommable();
        $bonCommande = $this->creerBonCommandeValide($article, 20);
        $bordereau = $this->creerBordereau($bonCommande, $article, 20);

        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);

        $this->assertSame('valide', $bordereau->refresh()->statut);

        // RGC-06 — le coût du lot est le prix commandé (100 000), pas le
        // prix indicatif du catalogue.
        $this->assertDatabaseHas('stock_mouvements', [
            'type_mouvement' => 'ENTREE',
            'type_origine' => 'BL',
            'origine_id' => $bordereau->id,
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 20,
        ]);
        $this->assertDatabaseHas('stock_lots', [
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite_restante' => 20,
            'cout_unitaire' => 100000,
        ]);
        $this->assertDatabaseHas('stock_articles_magasin', [
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite_actuelle' => 20,
            'valeur_stock_fifo' => 2000000,
        ]);
    }

    public function test_un_bl_d_equipements_ne_produit_aucune_entree_stock(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 2);
        $bordereau = $this->creerBordereau($bonCommande, $article, 2);
        $this->saisirInventaire($bordereau, $article, 2);

        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);

        // AN-12 — les équipements deviennent des fiches ParcInfo, pas du stock.
        $this->assertDatabaseCount('stock_mouvements', 0);
        $this->assertDatabaseCount('stock_lots', 0);
    }
}
