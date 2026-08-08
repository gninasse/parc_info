<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Database\Factories\ArticleFactory;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Catalogue\Models\Fournisseur;
use Modules\ParcInfo\Models\CategorieEquipement;
use Tests\TestCase;

class CodeGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_prefixe_article_par_nature_et_sequences_independantes(): void
    {
        $categorie = Categorie::create(['libelle' => 'Générale']);
        $categorieEquipement = CategorieEquipement::firstOrCreate(['code' => 'ORDI'], ['libelle' => 'Ordinateurs']);

        $base = ['categorie_id' => $categorie->id];
        $make = fn (array $attrs) => Article::create(array_merge(
            ['nom' => 'Article '.uniqid()],
            $base,
            $attrs
        ));

        $cons1 = $make(['nature' => Article::NATURE_CONSOMMABLE]);
        $cons2 = $make(['nature' => Article::NATURE_CONSOMMABLE]);
        $piece = $make(['nature' => Article::NATURE_PIECE]);
        $equipement = $make(['nature' => Article::NATURE_EQUIPEMENT, 'categorie_equipement_id' => $categorieEquipement->id]);
        $licence = $make(['nature' => Article::NATURE_LICENCE, 'logiciel_id' => ArticleFactory::logicielDeReference()->id]);

        $this->assertSame('CONS-00001', $cons1->code);
        $this->assertSame('CONS-00002', $cons2->code);
        $this->assertSame('PIE-00001', $piece->code);
        $this->assertSame('EQP-00001', $equipement->code);
        $this->assertSame('LIC-00001', $licence->code);
    }

    public function test_code_fournisseur_deux_lettres_et_sequence_par_prefixe(): void
    {
        $softsell = Fournisseur::create(['raison_sociale' => 'SoftSell']);
        $solutions = Fournisseur::create(['raison_sociale' => 'Solutions Plus']);
        $ebusiness = Fournisseur::create(['raison_sociale' => 'E-Business Faso']);
        $mono = Fournisseur::create(['raison_sociale' => 'K 2000']);

        $this->assertSame('FOUR-SO001', $softsell->code);
        $this->assertSame('FOUR-SO002', $solutions->code);
        $this->assertSame('FOUR-EB001', $ebusiness->code);
        $this->assertSame('FOUR-KX001', $mono->code);
    }

    public function test_unicite_des_codes_sur_une_rafale_de_creations_entrelacees(): void
    {
        $categorie = Categorie::create(['libelle' => 'Rafale']);

        for ($i = 0; $i < 30; $i++) {
            Article::create([
                'nom' => "Article rafale {$i}",
                'nature' => $i % 2 === 0 ? Article::NATURE_CONSOMMABLE : Article::NATURE_PIECE,
                'categorie_id' => $categorie->id,
            ]);
        }

        $codes = Article::pluck('code');

        $this->assertCount(30, $codes);
        $this->assertSame($codes->count(), $codes->unique()->count(), 'Des codes en double ont été générés.');
    }

    public function test_deux_generations_concurrentes_sont_serialisees_par_le_verrou(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Verrou de ligne testé sur PostgreSQL ; SQLite sérialise nativement toutes les écritures.');
        }

        // Deux connexions distinctes, hors transaction du test (auto-commit),
        // pour reproduire deux requêtes HTTP simultanées.
        config([
            'database.connections.catalogue_test_a' => config('database.connections.pgsql'),
            'database.connections.catalogue_test_b' => config('database.connections.pgsql'),
        ]);
        $a = DB::connection('catalogue_test_a');
        $b = DB::connection('catalogue_test_b');

        try {
            $a->table('catalogue_sequences')->insertOrIgnore([
                'prefix' => 'TEST-CC',
                'last_value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // A ouvre une transaction et verrouille la ligne de séquence.
            $a->beginTransaction();
            $valeurA = (int) $a->table('catalogue_sequences')->where('prefix', 'TEST-CC')->lockForUpdate()->value('last_value');
            $a->table('catalogue_sequences')->where('prefix', 'TEST-CC')->update(['last_value' => $valeurA + 1]);

            // B tente la même génération : le verrou doit la bloquer.
            $b->statement("SET lock_timeout = '300ms'");
            $bloquee = false;
            try {
                $b->table('catalogue_sequences')->where('prefix', 'TEST-CC')->lockForUpdate()->value('last_value');
            } catch (QueryException) {
                $bloquee = true;
            }
            $this->assertTrue($bloquee, 'La génération concurrente aurait dû attendre le verrou (lock_timeout dépassé).');

            // Après le commit de A, B repart de la valeur incrémentée : pas de collision possible.
            $a->commit();
            $b->statement('SET lock_timeout = 0');
            $valeurB = (int) $b->table('catalogue_sequences')->where('prefix', 'TEST-CC')->lockForUpdate()->value('last_value');

            $this->assertSame($valeurA + 1, $valeurB);
        } finally {
            if ($a->transactionLevel() > 0) {
                $a->rollBack();
            }
            $b->statement('SET lock_timeout = 0');
            $b->table('catalogue_sequences')->where('prefix', 'TEST-CC')->delete();
            DB::purge('catalogue_test_a');
            DB::purge('catalogue_test_b');
        }
    }
}
