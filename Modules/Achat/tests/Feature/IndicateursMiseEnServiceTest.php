<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Magasin;
use Tests\TestCase;

/**
 * D-20 — les indicateurs du jalon J+30 (SFD §9.2).
 *
 * Un plan de mise en service qui annonce « on mesurera à J+30 » ne mesure
 * rien : le jour venu, personne ne sait où lire les chiffres. La commande
 * `achat:indicateurs` les sort ; ces tests garantissent qu'elle dit vrai,
 * notamment sur le seul indicateur qui puisse tromper — la part de
 * réceptions liées.
 */
class IndicateursMiseEnServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);
    }

    public function test_la_commande_sort_les_trois_indicateurs(): void
    {
        $this->artisan('achat:indicateurs')
            ->expectsOutputToContain('Dette de régularisation')
            ->expectsOutputToContain('Délai de visa')
            ->expectsOutputToContain('Réceptions liées à un bon')
            ->assertSuccessful();
    }

    /**
     * L'indicateur d'adoption compte les livraisons liées sur le total des
     * livraisons validées. C'est le chiffre que la direction regardera.
     */
    public function test_la_part_des_receptions_liees_est_exacte(): void
    {
        $magasin = Magasin::factory()->create();
        $bon = BonCommande::factory()->valide()->create();

        // Trois livraisons validées, dont une seule liée à une commande.
        Entree::factory()->validee()->create([
            'magasin_id' => $magasin->id,
            'bon_commande_id' => $bon->id,
        ]);
        Entree::factory()->validee()->create(['magasin_id' => $magasin->id]);
        Entree::factory()->validee()->create(['magasin_id' => $magasin->id]);

        $this->artisan('achat:indicateurs')
            ->expectsOutputToContain('33,3 %')
            ->assertSuccessful();
    }

    /**
     * Un RETOUR de bénéficiaire ne livre aucune commande : le compter ferait
     * chuter l'indicateur sans qu'aucune chaîne ne soit rompue, et la
     * direction chercherait un problème qui n'existe pas.
     */
    public function test_les_retours_ne_penalisent_pas_l_indicateur(): void
    {
        $magasin = Magasin::factory()->create();
        $bon = BonCommande::factory()->valide()->create();

        Entree::factory()->validee()->create([
            'magasin_id' => $magasin->id,
            'bon_commande_id' => $bon->id,
        ]);

        // Deux retours validés : hors sujet pour cet indicateur.
        Entree::factory()->validee()->retour()->create(['magasin_id' => $magasin->id]);
        Entree::factory()->validee()->retour()->create(['magasin_id' => $magasin->id]);

        $this->artisan('achat:indicateurs')
            ->expectsOutputToContain('100,0 %')
            ->assertSuccessful();
    }

    /** Les brouillons ne comptent pas : seule une livraison validée existe. */
    public function test_les_brouillons_ne_comptent_pas(): void
    {
        $magasin = Magasin::factory()->create();
        $bon = BonCommande::factory()->valide()->create();

        Entree::factory()->validee()->create([
            'magasin_id' => $magasin->id,
            'bon_commande_id' => $bon->id,
        ]);

        Entree::factory()->create(['magasin_id' => $magasin->id]); // brouillon

        $this->artisan('achat:indicateurs')
            ->expectsOutputToContain('100,0 %')
            ->assertSuccessful();
    }

    /**
     * Sans le module Stock, la commande le dit plutôt que d'échouer.
     *
     * La table est RENOMMÉE et non supprimée : sur PostgreSQL, un `DROP` bute
     * sur les clés étrangères qui la référencent (`stock_lignes_entrees`,
     * `stock_mouvements`) et exigerait un `CASCADE` qui détruirait bien plus
     * que la table visée. Le renommage produit le même effet observable —
     * `Schema::hasTable()` répond faux — sans dépendre du SGBD.
     */
    public function test_la_commande_se_degrade_sans_le_module_stock(): void
    {
        \Illuminate\Support\Facades\Schema::rename('stock_entrees', 'stock_entrees_absentes');

        try {
            $this->artisan('achat:indicateurs')
                ->expectsOutputToContain('Module Stock indisponible')
                ->assertSuccessful();
        } finally {
            \Illuminate\Support\Facades\Schema::rename('stock_entrees_absentes', 'stock_entrees');
        }
    }

    /** Aucune donnée : la commande reste lisible, sans division par zéro. */
    public function test_la_commande_supporte_une_base_vide(): void
    {
        $this->artisan('achat:indicateurs')
            ->expectsOutputToContain('Aucune livraison validée sur la période')
            ->assertSuccessful();
    }
}
