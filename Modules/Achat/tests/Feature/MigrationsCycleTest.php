<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Cycle de vie des migrations sur SQLite (critère « migrate/rollback propres »).
 *
 * Un `migrate` qui passe ne prouve rien sur le `rollback` : c'est au retrait
 * que se révèlent les dépendances. Sur SQLite le risque est spécifique —
 * `SchemaChecks` recrée les tables pour y intégrer les CHECK, et une table
 * recréée pourrait perdre ce qu'il faut pour la retirer proprement.
 *
 * Le pendant PostgreSQL est `Modules/Achat/tests/migrations.sh`, qui vérifie
 * en plus le dénouement de la FK posée par le raccordement.
 */
class MigrationsCycleTest extends TestCase
{
    /** Les 9 tables du module (7 du SFD §6.2 + séquences + intégrations). */
    private const TABLES = [
        'achat_sequences',
        'achat_parametres',
        'achat_bons_commande',
        'achat_lignes_commande',
        'achat_receptions_licences',
        'achat_tampon_licences',
        'achat_documents',
        'achat_regularisation_rattachements',
        'achat_integrations_receptions',
    ];

    /**
     * Ce test pilote lui-même les migrations : RefreshDatabase les rejouerait
     * en parallèle et masquerait le résultat.
     *
     * Restreint à SQLite (base en mémoire, propre à chaque test) : sur un
     * PostgreSQL partagé, démonter puis remonter le schéma priverait de tables
     * les tests exécutés ensuite. Le cycle PostgreSQL est couvert par
     * `Modules/Achat/tests/migrations.sh`, qui travaille dans son propre
     * schéma jetable.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped(
                'Cycle de migrations vérifié sur PostgreSQL par Modules/Achat/tests/migrations.sh.'
            );
        }

        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_le_module_se_migre_puis_se_retire_puis_se_reinstalle(): void
    {
        // 1. Installé
        foreach (self::TABLES as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table absente après migrate : {$table}");
        }

        // 2. Retiré sans laisser de trace
        $code = Artisan::call('migrate:rollback', [
            '--path' => 'Modules/Achat/database/migrations',
            '--step' => 20,
            '--force' => true,
        ]);

        $this->assertSame(0, $code, 'Le rollback du module a échoué : '.Artisan::output());

        foreach (self::TABLES as $table) {
            $this->assertFalse(Schema::hasTable($table), "Table encore présente après rollback : {$table}");
        }

        // 3. Réinstallable : un module retiré doit pouvoir revenir
        Artisan::call('migrate', ['--force' => true]);

        foreach (self::TABLES as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table absente après réinstallation : {$table}");
        }
    }

    /** Les CHECK survivent à une réinstallation (SchemaChecks recrée les tables). */
    public function test_les_contraintes_sont_actives_apres_reinstallation(): void
    {
        Artisan::call('migrate:rollback', [
            '--path' => 'Modules/Achat/database/migrations',
            '--step' => 20,
            '--force' => true,
        ]);
        Artisan::call('migrate', ['--force' => true]);

        $bon = \Modules\Achat\Models\BonCommande::factory()->valide()->create();
        $ligne = \Modules\Achat\Models\LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'quantite' => 5,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        \Illuminate\Support\Facades\DB::table('achat_lignes_commande')
            ->where('id', $ligne->id)
            ->update(['quantite_livree' => 99]);
    }

    /**
     * Les CHECK doivent survivre à TOUTES les migrations, pas seulement à
     * celle qui les pose.
     *
     * C'est un défaut réellement rencontré : la migration qui ajoutait les
     * colonnes de renvoi posait une clé étrangère, chose que SQLite ne sait
     * pas faire sur une table existante. Laravel recréait donc la table, et
     * les CHECK disparaissaient SANS ERREUR — la base restait fonctionnelle,
     * mais son filet de sécurité s'était évaporé en silence.
     *
     * Ce test lit la DDL réelle après l'ensemble des migrations : c'est le
     * seul moyen de constater une contrainte manquante plutôt que de la
     * découvrir le jour où elle aurait dû protéger une donnée.
     */
    public function test_les_check_survivent_a_toutes_les_migrations(): void
    {
        $ddl = \Illuminate\Support\Facades\DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
            ['achat_bons_commande']
        )->sql;

        foreach (array_keys(\Modules\Achat\Support\SchemaChecks::bonsCommande()) as $contrainte) {
            $this->assertStringContainsString(
                $contrainte,
                $ddl,
                "Le CHECK « {$contrainte} » a disparu : une migration ultérieure a fait recréer la table."
            );
        }

        // Et la contrainte doit être ACTIVE, pas seulement présente dans la DDL.
        $bon = \Modules\Achat\Models\BonCommande::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        \Illuminate\Support\Facades\DB::table('achat_bons_commande')
            ->where('id', $bon->id)
            ->update(['numero' => 'BC-2026-0001']); // un brouillon n'a pas de numéro
    }

    /** Les colonnes ajoutées après coup sont bien là, CHECK ou pas. */
    public function test_les_colonnes_de_renvoi_sont_posees(): void
    {
        foreach (['renvoi_motif', 'renvoi_par', 'renvoi_le'] as $colonne) {
            $this->assertTrue(
                Schema::hasColumn('achat_bons_commande', $colonne),
                "Colonne absente : {$colonne}"
            );
        }
    }
}
