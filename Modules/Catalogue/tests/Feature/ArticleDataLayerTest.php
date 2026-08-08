<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Catalogue\Database\Factories\ArticleFactory;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\ParcInfo\Models\CategorieEquipement;
use Tests\TestCase;

class ArticleDataLayerTest extends TestCase
{
    use RefreshDatabase;

    private Categorie $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categorie = Categorie::create(['libelle' => 'Générale']);
    }

    private function article(array $attributes = []): Article
    {
        return Article::create(array_merge([
            'nom' => 'Article test '.uniqid(),
            'nature' => Article::NATURE_CONSOMMABLE,
            'categorie_id' => $this->categorie->id,
        ], $attributes));
    }

    public function test_est_stockable_derive_de_la_nature(): void
    {
        $consommable = $this->article();
        $piece = $this->article(['nature' => Article::NATURE_PIECE]);
        $equipement = $this->article([
            'nature' => Article::NATURE_EQUIPEMENT,
            'categorie_equipement_id' => $this->categorieEquipement()->id,
        ]);
        $licence = $this->article([
            'nature' => Article::NATURE_LICENCE,
            'logiciel_id' => ArticleFactory::logicielDeReference()->id,
        ]);

        $this->assertTrue($consommable->est_stockable);
        $this->assertTrue($piece->est_stockable);
        $this->assertTrue($equipement->est_stockable);
        $this->assertFalse($licence->est_stockable);
    }

    public function test_la_nature_est_immuable_regle_c6(): void
    {
        $article = $this->article();

        try {
            $article->update(['nature' => Article::NATURE_PIECE]);
            $this->fail('Le changement de nature aurait dû être refusé.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('immuable', $e->getMessage());
        }

        $this->assertSame(Article::NATURE_CONSOMMABLE, $article->fresh()->nature);
    }

    public function test_equipement_exige_une_categorie_equipement(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("catégorie d'équipements");

        $this->article(['nature' => Article::NATURE_EQUIPEMENT]);
    }

    public function test_categorie_equipement_interdite_hors_nature_equipement(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->article(['categorie_equipement_id' => $this->categorieEquipement()->id]);
    }

    public function test_licence_exige_un_logiciel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('logiciel');

        $this->article(['nature' => Article::NATURE_LICENCE]);
    }

    public function test_logiciel_interdit_hors_nature_licence(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->article([
            'nature' => Article::NATURE_PIECE,
            'logiciel_id' => ArticleFactory::logicielDeReference()->id,
        ]);
    }

    public function test_check_sql_bloque_une_insertion_incoherente_en_contournant_le_modele(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('CHECK SQL posé sur PostgreSQL uniquement ; sur SQLite la règle est portée par le modèle (tests ci-dessus).');
        }

        $this->expectException(QueryException::class);

        DB::table('catalogue_articles')->insert([
            'code' => 'CONS-99999',
            'nom' => 'Insertion brute incohérente',
            'nature' => Article::NATURE_CONSOMMABLE,
            'est_stockable' => true,
            'categorie_id' => $this->categorie->id,
            'categorie_equipement_id' => $this->categorieEquipement()->id,
            'unite_stock' => 'unité',
            'taux_tva' => 18.00,
            'est_actif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_unite_stock_forcee_a_unite_pour_equipement_et_licence(): void
    {
        $equipement = $this->article([
            'nature' => Article::NATURE_EQUIPEMENT,
            'categorie_equipement_id' => $this->categorieEquipement()->id,
            'unite_stock' => 'carton',
        ]);
        $licence = $this->article([
            'nature' => Article::NATURE_LICENCE,
            'logiciel_id' => ArticleFactory::logicielDeReference()->id,
            'unite_stock' => 'pack',
        ]);
        $consommable = $this->article(['unite_stock' => 'cartouche']);

        $this->assertSame('unité', $equipement->unite_stock);
        $this->assertSame('unité', $licence->unite_stock);
        $this->assertSame('cartouche', $consommable->unite_stock);
    }

    public function test_seuil_defaut_refuse_pour_une_licence(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('seuil');

        $this->article([
            'nature' => Article::NATURE_LICENCE,
            'logiciel_id' => ArticleFactory::logicielDeReference()->id,
            'seuil_defaut' => 5,
        ]);
    }

    public function test_compatibilites_reservees_aux_consommables_et_pieces(): void
    {
        $piece = $this->article([
            'nature' => Article::NATURE_PIECE,
            'compatibilites' => ['HP LaserJet P1102'],
        ]);
        $this->assertSame(['HP LaserJet P1102'], $piece->compatibilites);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('compatibilités');

        $this->article([
            'nature' => Article::NATURE_EQUIPEMENT,
            'categorie_equipement_id' => $this->categorieEquipement()->id,
            'compatibilites' => ['X'],
        ]);
    }

    public function test_accessors_nature_label_et_categorie_chemin(): void
    {
        $enfant = Categorie::create(['libelle' => 'Toners', 'parent_id' => $this->categorie->id]);
        $article = $this->article(['categorie_id' => $enfant->id]);

        $this->assertSame('Consommable', $article->nature_label);
        $this->assertSame('Générale > Toners', $article->categorie_chemin);
    }

    public function test_reference_constructeur_unique_par_marque(): void
    {
        $marque = \Modules\ParcInfo\Models\Marque::firstOrCreate(['libelle' => 'HP']);

        $this->article(['marque_id' => $marque->id, 'reference_constructeur' => 'CE285A']);

        $this->expectException(QueryException::class);

        $this->article(['marque_id' => $marque->id, 'reference_constructeur' => 'CE285A']);
    }

    private function categorieEquipement(): CategorieEquipement
    {
        return CategorieEquipement::firstOrCreate(['code' => 'ORDI'], ['libelle' => 'Ordinateurs']);
    }
}
