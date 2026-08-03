<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Database\Factories\ParcInfoDeTest;
use Modules\Stock\Exceptions\ArticleInactifException;
use Modules\Stock\Exceptions\ArticleNonStockableException;
use Modules\Stock\Exceptions\ContreMouvementInterditException;
use Modules\Stock\Exceptions\MagasinInactifException;
use Modules\Stock\Exceptions\MotifRequisException;
use Modules\Stock\Exceptions\NiveauNegatifException;
use Modules\Stock\Exceptions\QuantiteInvalideException;
use Modules\Stock\Exceptions\StockInsuffisantException;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Inventaire;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Models\Transfert;
use Modules\Stock\Services\MouvementService;
use Tests\TestCase;

class MouvementServiceTest extends TestCase
{
    use RefreshDatabase;

    private MouvementService $service;

    private Magasin $magasin;

    private Article $article;

    private Entree $entree;

    private Sortie $sortie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MouvementService::class);
        $this->magasin = Magasin::factory()->create();
        $this->article = Article::factory()->consommable()->create(['seuil_defaut' => null]);
        $this->entree = Entree::factory()->validee()->create(['magasin_id' => $this->magasin->id]);
        $this->sortie = Sortie::factory()->validee()->create(['magasin_id' => $this->magasin->id]);
    }

    private function approvisionner(float $quantite): void
    {
        $this->service->entree([
            'entree_id' => $this->entree->id,
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => $quantite,
        ]);
    }

    private function niveau(): ?Niveau
    {
        return Niveau::query()
            ->where('magasin_id', $this->magasin->id)
            ->where('article_id', $this->article->id)
            ->first();
    }

    public function test_une_entree_cree_le_niveau_puis_l_incremente(): void
    {
        $this->approvisionner(5);
        $this->assertSame(5.0, (float) $this->niveau()->quantite);

        $this->approvisionner(3);
        $this->assertSame(8.0, (float) $this->niveau()->quantite);
        $this->assertSame(2, Mouvement::query()->count());
    }

    public function test_invariant_I1_sortie_superieure_au_disponible_refusee(): void
    {
        $this->approvisionner(5);

        try {
            $this->service->sortie([
                'sortie_id' => $this->sortie->id,
                'magasin_id' => $this->magasin->id,
                'article_id' => $this->article->id,
                'quantite' => 10,
            ]);
            $this->fail('La sortie aurait dû être refusée (I1).');
        } catch (StockInsuffisantException $e) {
            $this->assertSame(422, $e->status());
        }

        // niveau inchangé, aucun mouvement de sortie créé
        $this->assertSame(5.0, (float) $this->niveau()->quantite);
        $this->assertSame(1, Mouvement::query()->count());
    }

    public function test_invariant_I2_deux_sorties_dont_la_somme_depasse_le_disponible(): void
    {
        // Concurrence simulée séquentiellement : la seconde transaction
        // relit le niveau sous verrou et voit 4, pas 10.
        $this->approvisionner(10);

        $sortir = fn () => $this->service->sortie([
            'sortie_id' => $this->sortie->id,
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => 6,
        ]);

        $sortir();

        try {
            $sortir();
            $this->fail('La seconde sortie aurait dû être refusée (I2).');
        } catch (StockInsuffisantException) {
        }

        $this->assertSame(4.0, (float) $this->niveau()->quantite);
        $this->assertSame(2, Mouvement::query()->count()); // 1 entrée + 1 seule sortie
    }

    public function test_invariant_I2_le_service_verrouille_la_ligne_de_niveau(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Verrou FOR UPDATE observable sur PostgreSQL uniquement (no-op SQLite).');
        }

        $this->approvisionner(10);

        DB::enableQueryLog();
        $this->service->sortie([
            'sortie_id' => $this->sortie->id,
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => 1,
        ]);
        $requetes = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $this->assertTrue(
            $requetes->contains(fn (string $sql) => str_contains($sql, 'stock_niveaux') && str_contains(strtolower($sql), 'for update')),
            'La lecture du niveau doit être verrouillante (lockForUpdate — D9).'
        );
    }

    public function test_transfert_paire_atomique_et_niveaux(): void
    {
        $this->approvisionner(10);
        $cible = Magasin::factory()->create();
        $transfert = Transfert::factory()->validee()->create([
            'magasin_source_id' => $this->magasin->id,
            'magasin_cible_id' => $cible->id,
        ]);

        $paire = $this->service->transfert([
            'transfert_id' => $transfert->id,
            'magasin_source_id' => $this->magasin->id,
            'magasin_cible_id' => $cible->id,
            'article_id' => $this->article->id,
            'quantite' => 4,
        ]);

        $this->assertSame(Mouvement::TYPE_TRANSFERT_SORTIE, $paire['sortie']->type);
        $this->assertSame(Mouvement::TYPE_TRANSFERT_ENTREE, $paire['entree']->type);
        $this->assertSame($transfert->id, $paire['sortie']->transfert_id);
        $this->assertSame($transfert->id, $paire['entree']->transfert_id);

        $this->assertSame(6.0, (float) $this->niveau()->quantite);
        $this->assertSame(4.0, (float) Niveau::query()
            ->where('magasin_id', $cible->id)
            ->where('article_id', $this->article->id)
            ->value('quantite'));
    }

    public function test_transfert_insuffisant_ne_laisse_aucune_trace(): void
    {
        // I4 au niveau service : jamais l'entrée cible sans la sortie source
        $this->approvisionner(2);
        $cible = Magasin::factory()->create();
        $transfert = Transfert::factory()->validee()->create([
            'magasin_source_id' => $this->magasin->id,
            'magasin_cible_id' => $cible->id,
        ]);

        try {
            $this->service->transfert([
                'transfert_id' => $transfert->id,
                'magasin_source_id' => $this->magasin->id,
                'magasin_cible_id' => $cible->id,
                'article_id' => $this->article->id,
                'quantite' => 5,
            ]);
            $this->fail('Le transfert aurait dû être refusé.');
        } catch (StockInsuffisantException) {
        }

        $this->assertSame(2.0, (float) $this->niveau()->quantite);
        $this->assertSame(0, Mouvement::query()->where('transfert_id', $transfert->id)->count());
        $this->assertNull(Niveau::query()->where('magasin_id', $cible->id)->where('article_id', $this->article->id)->first());
    }

    public function test_invariant_I7_contre_mouvement_borne(): void
    {
        $this->approvisionner(10);
        $mouvementEntree = Mouvement::query()->where('type', Mouvement::TYPE_ENTREE)->first();

        $this->service->sortie([
            'sortie_id' => $this->sortie->id,
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => 8,
        ]);

        // Contrer l'entrée de 10 alors qu'il ne reste que 2 → niveau négatif
        try {
            $this->service->contreMouvement($mouvementEntree, 'Erreur de saisie');
            $this->fail('Le contre-mouvement aurait dû être refusé (I7).');
        } catch (NiveauNegatifException $e) {
            $this->assertSame(422, $e->status());
        }

        $this->assertSame(2.0, (float) $this->niveau()->quantite);
        $this->assertSame(2, Mouvement::query()->count());
    }

    public function test_contre_mouvement_nominal_autoporte(): void
    {
        $this->approvisionner(10);
        $mouvementEntree = Mouvement::query()->where('type', Mouvement::TYPE_ENTREE)->first();

        $contre = $this->service->contreMouvement($mouvementEntree, 'Bon saisi en double');

        $this->assertSame(Mouvement::TYPE_AJUSTEMENT, $contre->type);
        $this->assertSame($mouvementEntree->id, $contre->mouvement_origine_id);
        $this->assertNull($contre->entree_id);
        $this->assertSame(-1, $contre->sens);
        $this->assertSame(0.0, (float) $this->niveau()->quantite);
    }

    public function test_contre_mouvement_d_un_contre_mouvement_refuse(): void
    {
        $this->approvisionner(10);
        $contre = $this->service->contreMouvement(
            Mouvement::query()->where('type', Mouvement::TYPE_ENTREE)->first(),
            'premier contre'
        );

        $this->expectException(ContreMouvementInterditException::class);
        $this->service->contreMouvement($contre, 'contre du contre');
    }

    public function test_contre_mouvement_d_equipement_renvoye_vers_le_retour(): void
    {
        $equipement = ParcInfoDeTest::equipement();
        $mouvement = $this->service->entree([
            'entree_id' => $this->entree->id,
            'magasin_id' => $this->magasin->id,
            'equipement_id' => $equipement->id,
            'quantite' => 1,
        ]);

        $this->expectException(ContreMouvementInterditException::class);
        $this->service->contreMouvement($mouvement, 'tentative');
    }

    public function test_invariant_I12_article_non_stockable_refuse_partout(): void
    {
        $licence = Article::factory()->licence()->create();

        $operations = [
            fn () => $this->service->entree([
                'entree_id' => $this->entree->id,
                'magasin_id' => $this->magasin->id,
                'article_id' => $licence->id,
                'quantite' => 1,
            ]),
            fn () => $this->service->sortie([
                'sortie_id' => $this->sortie->id,
                'magasin_id' => $this->magasin->id,
                'article_id' => $licence->id,
                'quantite' => 1,
            ]),
        ];

        foreach ($operations as $operation) {
            try {
                $operation();
                $this->fail('L\'article non stockable aurait dû être refusé (I12).');
            } catch (ArticleNonStockableException) {
            }
        }

        // aucun niveau créé
        $this->assertSame(0, Niveau::query()->where('article_id', $licence->id)->count());
        $this->assertSame(0, Mouvement::query()->count());
    }

    public function test_invariant_I13_brouillon_sans_effet(): void
    {
        $sequencesAvant = DB::table('stock_sequences')->sum('last_value');

        // Création, modifications, suppression d'un brouillon complet
        $brouillon = Entree::factory()->create(['magasin_id' => $this->magasin->id]);
        $ligne = LigneEntree::factory()->create([
            'entree_id' => $brouillon->id,
            'article_id' => $this->article->id,
            'quantite' => 12,
        ]);

        $ligne->update(['quantite' => 20]);
        $brouillon->update(['reference_externe' => 'BL-TEST']);
        $brouillon->delete();

        $this->assertSame(0, Mouvement::query()->count());
        $this->assertSame(0, Niveau::query()->count());
        $this->assertNull($brouillon->fresh());
        $this->assertNull($ligne->fresh()); // cascade
        $this->assertNull($brouillon->numero); // aucun numéro consommé
        $this->assertEquals($sequencesAvant, DB::table('stock_sequences')->sum('last_value'));
    }

    // ── Gardes transverses (§7.0) ──────────────────────────────────────────

    public function test_garde_magasin_inactif(): void
    {
        $inactif = Magasin::factory()->inactif()->create();

        $this->expectException(MagasinInactifException::class);
        $this->service->entree([
            'entree_id' => $this->entree->id,
            'magasin_id' => $inactif->id,
            'article_id' => $this->article->id,
            'quantite' => 1,
        ]);
    }

    public function test_garde_article_inactif_refuse_en_entree_seulement(): void
    {
        $this->approvisionner(5);
        $this->article->update(['est_actif' => false]);

        try {
            $this->approvisionner(1);
            $this->fail('L\'entrée d\'un article désactivé aurait dû être refusée.');
        } catch (ArticleInactifException) {
        }

        // ... mais la sortie du stock existant reste possible
        $this->service->sortie([
            'sortie_id' => $this->sortie->id,
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => 2,
        ]);

        $this->assertSame(3.0, (float) $this->niveau()->quantite);
    }

    public function test_garde_quantite_invalide(): void
    {
        $this->expectException(QuantiteInvalideException::class);
        $this->service->entree([
            'entree_id' => $this->entree->id,
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => 0,
        ]);
    }

    public function test_garde_equipement_toujours_unitaire(): void
    {
        $equipement = ParcInfoDeTest::equipement();

        $this->expectException(QuantiteInvalideException::class);
        $this->service->entree([
            'entree_id' => $this->entree->id,
            'magasin_id' => $this->magasin->id,
            'equipement_id' => $equipement->id,
            'quantite' => 2,
        ]);
    }

    public function test_garde_ajustement_sans_motif(): void
    {
        $inventaire = Inventaire::factory()->create(['magasin_id' => $this->magasin->id]);

        $this->expectException(MotifRequisException::class);
        $this->service->ajustement([
            'inventaire_id' => $inventaire->id,
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => 1,
            'sens' => 1,
        ]);
    }

    public function test_ajustement_negatif_borne_a_zero(): void
    {
        $this->approvisionner(3);
        $inventaire = Inventaire::factory()->create(['magasin_id' => $this->magasin->id]);

        $this->expectException(NiveauNegatifException::class);
        $this->service->ajustement([
            'inventaire_id' => $inventaire->id,
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => 5,
            'sens' => -1,
            'motif' => 'Écart d\'inventaire',
        ]);
    }

    public function test_un_mouvement_d_equipement_ne_touche_pas_les_niveaux(): void
    {
        $equipement = ParcInfoDeTest::equipement();

        $this->service->entree([
            'entree_id' => $this->entree->id,
            'magasin_id' => $this->magasin->id,
            'equipement_id' => $equipement->id,
            'quantite' => 1,
        ]);

        $this->assertSame(0, Niveau::query()->count());
        $this->assertSame(1, Mouvement::query()->whereNotNull('equipement_id')->count());
    }
}
