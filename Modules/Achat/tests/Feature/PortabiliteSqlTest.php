<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Tests\TestCase;

/**
 * Portabilité SQL (convention 6 / SFD §1.7) : le module doit tourner à
 * l'identique sur SQLite et PostgreSQL.
 *
 * Ces tests sont agnostiques : ils s'exécutent sur le pilote configuré. La
 * suite PostgreSQL se lance avec `Modules/Achat/tests/postgres.sh`, qui rejoue
 * exactement ces mêmes cas sur un schéma isolé.
 */
class PortabiliteSqlTest extends TestCase
{
    use RefreshDatabase;

    /** Les 8 tables du module existent, quel que soit le SGBD. */
    public function test_toutes_les_tables_du_module_sont_creees(): void
    {
        $tables = [
            'achat_sequences',
            'achat_parametres',
            'achat_bons_commande',
            'achat_lignes_commande',
            'achat_receptions_licences',
            'achat_tampon_licences',
            'achat_documents',
            'achat_regularisation_rattachements',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), 'Table absente sur '.DB::getDriverName()." : {$table}");
        }
    }

    /**
     * Les CHECK sont actifs sur les deux SGBD (double filet) : c'est le point
     * qui casse le plus facilement, SQLite ne sachant pas les ajouter après
     * coup — d'où la recréation de table par SchemaChecks.
     */
    public function test_les_contraintes_check_sont_actives(): void
    {
        $ligne = LigneCommande::factory()->create(['quantite' => 5]);

        $refus = false;
        try {
            DB::table('achat_lignes_commande')->where('id', $ligne->id)->update(['quantite_livree' => 99]);
        } catch (\Illuminate\Database\QueryException) {
            $refus = true;
        }

        $this->assertTrue($refus, 'Le CHECK du plafond de réception est inactif sur '.DB::getDriverName());
    }

    /** Les index et l'unicité survivent à la recréation de table SQLite. */
    public function test_l_unicite_du_numero_est_active(): void
    {
        BonCommande::factory()->valide()->create(['numero' => 'BC-2026-7777']);

        $refus = false;
        try {
            BonCommande::factory()->valide()->create(['numero' => 'BC-2026-7777']);
        } catch (\Illuminate\Database\QueryException) {
            $refus = true;
        }

        $this->assertTrue($refus, 'L\'unicité du numéro est inactive sur '.DB::getDriverName());
    }

    /**
     * Aucune fonction propriétaire : la recherche se fait en LIKE, jamais en
     * ILIKE ni TO_CHAR (convention 6). On vérifie ici que le LIKE portable
     * fonctionne sur le pilote courant.
     */
    public function test_la_recherche_utilise_un_like_portable(): void
    {
        BonCommande::factory()->valide()->create(['numero' => 'BC-2026-0041']);
        BonCommande::factory()->valide()->create(['numero' => 'BC-2026-0042']);

        $trouves = BonCommande::query()->where('numero', 'LIKE', '%0041%')->count();

        $this->assertSame(1, $trouves);
    }

    /** Les décimaux se relisent avec la même précision partout (IA-1). */
    public function test_les_montants_decimaux_traversent_le_sgbd_sans_derive(): void
    {
        $bon = BonCommande::factory()->create();
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'quantite' => 3,
            'prix_unitaire_ht' => 650000.55,
            'taux_tva' => 18,
        ]);

        $totaux = app(\Modules\Achat\Services\CalculMontantsService::class)->recalculer($bon);

        $this->assertEqualsWithDelta(1950001.65, $totaux['montant_ht'], 0.001);
        $this->assertEqualsWithDelta(1950001.65, (float) $bon->fresh()->montant_ht, 0.001);
    }

    /** Les booléens (true/false pgsql, 0/1 sqlite) se relisent en bool PHP. */
    public function test_les_booleens_se_relisent_identiquement(): void
    {
        $regularisation = BonCommande::factory()->regularisation()->create();
        $ordinaire = BonCommande::factory()->create();

        $this->assertTrue($regularisation->fresh()->est_regularisation);
        $this->assertFalse($ordinaire->fresh()->est_regularisation);

        $this->assertSame(1, BonCommande::query()->where('est_regularisation', true)->count());
        $this->assertSame(1, BonCommande::query()->horsRegularisation()->count());
    }
}
