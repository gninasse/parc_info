<?php

namespace Modules\Stock\Tests\Unit;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Database\Factories\ParcInfoDeTest;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Sortie;
use Tests\TestCase;

/**
 * Chaque CHECK de migration est testé par insertion invalide directe
 * (DB::table — les gardes applicatives sont contournées exprès) : le
 * « double filet » SFD §9.3 doit tenir sur SQLite comme sur PostgreSQL.
 */
class MigrationChecksTest extends TestCase
{
    use RefreshDatabase;

    private function attendreRejet(callable $insertion): void
    {
        $this->expectException(QueryException::class);
        $insertion();
    }

    public function test_niveau_quantite_negative_rejetee(): void
    {
        $magasin = Magasin::factory()->create();
        $article = Article::factory()->consommable()->create();

        $this->attendreRejet(fn () => DB::table('stock_niveaux')->insert([
            'magasin_id' => $magasin->id,
            'article_id' => $article->id,
            'quantite' => -1,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_ligne_entree_avec_article_et_equipement_rejetee(): void
    {
        $entree = Entree::factory()->create();
        $article = Article::factory()->consommable()->create();
        $equipement = ParcInfoDeTest::equipement();

        $this->attendreRejet(fn () => DB::table('stock_lignes_entrees')->insert([
            'entree_id' => $entree->id,
            'article_id' => $article->id,
            'equipement_id' => $equipement->id,
            'quantite' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_ligne_entree_sans_article_ni_equipement_rejetee(): void
    {
        $entree = Entree::factory()->create();

        $this->attendreRejet(fn () => DB::table('stock_lignes_entrees')->insert([
            'entree_id' => $entree->id,
            'quantite' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_ligne_entree_quantite_nulle_rejetee(): void
    {
        $entree = Entree::factory()->create();
        $article = Article::factory()->consommable()->create();

        $this->attendreRejet(fn () => DB::table('stock_lignes_entrees')->insert([
            'entree_id' => $entree->id,
            'article_id' => $article->id,
            'quantite' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_ligne_sortie_quantite_nulle_rejetee(): void
    {
        $sortie = Sortie::factory()->create();
        $article = Article::factory()->consommable()->create();

        $this->attendreRejet(fn () => DB::table('stock_lignes_sorties')->insert([
            'sortie_id' => $sortie->id,
            'article_id' => $article->id,
            'quantite' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_transfert_source_egale_cible_rejete(): void
    {
        $magasin = Magasin::factory()->create();

        $this->attendreRejet(fn () => DB::table('stock_transferts')->insert([
            'date_document' => now()->toDateString(),
            'statut' => 'BROUILLON',
            'magasin_source_id' => $magasin->id,
            'magasin_cible_id' => $magasin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_ligne_transfert_quantite_negative_rejetee(): void
    {
        $transfert = \Modules\Stock\Models\Transfert::factory()->create();
        $article = Article::factory()->consommable()->create();

        $this->attendreRejet(fn () => DB::table('stock_lignes_transferts')->insert([
            'transfert_id' => $transfert->id,
            'article_id' => $article->id,
            'quantite' => -3,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_tampon_sans_aucune_ligne_rejete(): void
    {
        $this->attendreRejet(fn () => DB::table('stock_tampon_equipements')->insert([
            'numero_serie' => 'SN-CHECK-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_tampon_avec_deux_lignes_rejete(): void
    {
        $ligneEntree = LigneEntree::factory()->create();
        $ligneSortie = \Modules\Stock\Models\LigneSortie::factory()->create();

        $this->attendreRejet(fn () => DB::table('stock_tampon_equipements')->insert([
            'ligne_entree_id' => $ligneEntree->id,
            'ligne_sortie_id' => $ligneSortie->id,
            'numero_serie' => 'SN-CHECK-2',
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_ligne_inventaire_avec_article_et_equipement_rejetee(): void
    {
        $inventaire = \Modules\Stock\Models\Inventaire::factory()->create();
        $article = Article::factory()->consommable()->create();
        $equipement = ParcInfoDeTest::equipement();

        $this->attendreRejet(fn () => DB::table('stock_lignes_inventaire')->insert([
            'inventaire_id' => $inventaire->id,
            'article_id' => $article->id,
            'equipement_id' => $equipement->id,
            'quantite_theorique' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    // ── stock_mouvements : le CHECK document/origine du SFD §6.2 ──────────

    private function mouvementValide(array $surcharge = []): array
    {
        $magasin = Magasin::factory()->create();
        $article = Article::factory()->consommable()->create();
        $entree = Entree::factory()->validee()->create(['magasin_id' => $magasin->id]);

        return array_merge([
            'entree_id' => $entree->id,
            'magasin_id' => $magasin->id,
            'type' => 'ENTREE',
            'sens' => 1,
            'article_id' => $article->id,
            'quantite' => 5,
            'created_at' => now(),
        ], $surcharge);
    }

    public function test_mouvement_valide_accepte_temoin(): void
    {
        DB::table('stock_mouvements')->insert($this->mouvementValide());

        $this->assertSame(1, DB::table('stock_mouvements')->count());
    }

    public function test_mouvement_sans_document_ni_origine_rejete(): void
    {
        $this->attendreRejet(fn () => DB::table('stock_mouvements')->insert(
            $this->mouvementValide(['entree_id' => null])
        ));
    }

    public function test_mouvement_avec_deux_documents_rejete(): void
    {
        $sortie = Sortie::factory()->validee()->create();

        $this->attendreRejet(fn () => DB::table('stock_mouvements')->insert(
            $this->mouvementValide(['sortie_id' => $sortie->id])
        ));
    }

    public function test_mouvement_avec_document_et_origine_rejete(): void
    {
        DB::table('stock_mouvements')->insert($this->mouvementValide());
        $origineId = (int) DB::table('stock_mouvements')->max('id');

        $this->attendreRejet(fn () => DB::table('stock_mouvements')->insert(
            $this->mouvementValide(['mouvement_origine_id' => $origineId])
        ));
    }

    public function test_contre_mouvement_autoporte_accepte_temoin(): void
    {
        DB::table('stock_mouvements')->insert($this->mouvementValide());
        $origineId = (int) DB::table('stock_mouvements')->max('id');

        DB::table('stock_mouvements')->insert($this->mouvementValide([
            'entree_id' => null,
            'mouvement_origine_id' => $origineId,
            'type' => 'AJUSTEMENT',
            'sens' => -1,
            'motif' => 'contre-mouvement témoin',
        ]));

        $this->assertSame(2, DB::table('stock_mouvements')->count());
    }

    public function test_mouvement_article_et_equipement_rejete(): void
    {
        $equipement = ParcInfoDeTest::equipement();

        $this->attendreRejet(fn () => DB::table('stock_mouvements')->insert(
            $this->mouvementValide(['equipement_id' => $equipement->id])
        ));
    }

    public function test_mouvement_quantite_nulle_rejete(): void
    {
        $this->attendreRejet(fn () => DB::table('stock_mouvements')->insert(
            $this->mouvementValide(['quantite' => 0])
        ));
    }

    public function test_mouvement_equipement_quantite_deux_rejete(): void
    {
        $equipement = ParcInfoDeTest::equipement();

        $this->attendreRejet(fn () => DB::table('stock_mouvements')->insert(
            $this->mouvementValide(['article_id' => null, 'equipement_id' => $equipement->id, 'quantite' => 2])
        ));
    }

    public function test_mouvement_sens_invalide_rejete(): void
    {
        $this->attendreRejet(fn () => DB::table('stock_mouvements')->insert(
            $this->mouvementValide(['sens' => 2])
        ));
    }

    public function test_ajustement_sans_motif_rejete(): void
    {
        $inventaire = \Modules\Stock\Models\Inventaire::factory()->create();

        $this->attendreRejet(fn () => DB::table('stock_mouvements')->insert(
            $this->mouvementValide([
                'entree_id' => null,
                'inventaire_id' => $inventaire->id,
                'type' => 'AJUSTEMENT',
                'motif' => null,
            ])
        ));
    }
}
