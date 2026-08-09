<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Services\LignesBonCommandeService;
use Modules\Achat\Services\StatistiquesAchatService;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-24 — l'état des dépenses par imputation comptable.
 *
 * Il répond à une question que l'établissement ne pouvait pas poser :
 * « combien avons-nous engagé sur tel compte cette année ? ».
 *
 * Deux exigences, et la première est comptable avant d'être technique :
 *
 *   1. le compte est FIGÉ à la ligne, comme le prix et la TVA (IA-2).
 *      Réaffecter un article à un autre compte au Catalogue ne doit pas
 *      réécrire rétroactivement l'imputation d'exercices clos ;
 *   2. les lignes sans compte ne sont JAMAIS masquées : elles figurent sous
 *      « Non imputé ». Un état qui tairait ce qu'il ne sait pas classer
 *      laisserait croire que son total est complet.
 */
class ImputationComptableTest extends TestCase
{
    use RefreshDatabase;

    private User $lecteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        $this->lecteur = User::create([
            'name' => 'Lecteur', 'last_name' => 'Rapports', 'user_name' => 'lecteur_imput',
            'email' => 'lecteur-imput@example.com', 'password' => bcrypt('password'),
        ]);

        foreach (['achat.rapports.view', 'achat.rapports.export'] as $permission) {
            $this->lecteur->givePermissionTo($permission);
        }
    }

    /** Un bon engagé portant une ligne de l'article donné. */
    private function bonAvecLigne(Article $article, float $quantite = 2, float $prix = 100000): BonCommande
    {
        $bon = BonCommande::factory()->valide()->create();

        app(LignesBonCommandeService::class)->synchroniser($bon, [
            ['article_id' => $article->id, 'quantite' => $quantite, 'prix_unitaire_ht' => $prix, 'taux_tva' => 18],
        ]);

        return $bon->refresh();
    }

    // ── Le figeage (IA-2) ──────────────────────────────────────────────────

    public function test_le_compte_est_fige_sur_la_ligne_a_la_creation(): void
    {
        $article = Article::factory()->create(['compte_comptable' => '6063']);

        $bon = $this->bonAvecLigne($article);

        $this->assertSame('6063', $bon->lignes()->first()->compte_comptable);
    }

    /**
     * LE point de l'invariant : réaffecter l'article au Catalogue ne doit
     * pas changer ce qui a été imputé sur un bon déjà engagé. Sans cela, un
     * exercice clos se réécrirait tout seul.
     */
    public function test_reaffecter_l_article_ne_reecrit_pas_les_commandes_passees(): void
    {
        $article = Article::factory()->create(['compte_comptable' => '6063']);

        $bon = $this->bonAvecLigne($article);

        $article->forceFill(['compte_comptable' => '6188'])->save();

        $this->assertSame(
            '6063',
            $bon->lignes()->first()->fresh()->compte_comptable,
            'L\'imputation d\'un bon engagé ne doit pas suivre le Catalogue.'
        );
    }

    /** Un article sans imputation donne une ligne sans imputation, sans erreur. */
    public function test_un_article_sans_compte_ne_bloque_pas_la_saisie(): void
    {
        $article = Article::factory()->create(['compte_comptable' => null]);

        $bon = $this->bonAvecLigne($article);

        $this->assertNull($bon->lignes()->first()->compte_comptable);
    }

    // ── L'agrégat ──────────────────────────────────────────────────────────

    public function test_les_depenses_sont_regroupees_par_compte(): void
    {
        $a = Article::factory()->create(['compte_comptable' => '6063']);
        $b = Article::factory()->create(['compte_comptable' => '6063']);
        $c = Article::factory()->create(['compte_comptable' => '6188']);

        $this->bonAvecLigne($a, 2, 100000);   // 200 000 sur 6063
        $this->bonAvecLigne($b, 1, 50000);    //  50 000 sur 6063
        $this->bonAvecLigne($c, 3, 30000);    //  90 000 sur 6188

        $lignes = collect(app(StatistiquesAchatService::class)->depensesParImputation());

        $compte6063 = $lignes->firstWhere('compte', '6063');
        $compte6188 = $lignes->firstWhere('compte', '6188');

        $this->assertEqualsWithDelta(250000, $compte6063['montant_ht'], 0.01);
        $this->assertSame(2, $compte6063['nombre_lignes']);
        $this->assertEqualsWithDelta(90000, $compte6188['montant_ht'], 0.01);

        // Le TTC se recompose depuis le taux de CHAQUE ligne : un bon peut
        // mêler des comptes, on ne peut pas lire le total du bon.
        $this->assertEqualsWithDelta(250000 * 1.18, $compte6063['montant_ttc'], 0.01);
    }

    /**
     * Les lignes sans compte apparaissent sous « Non imputé », jamais
     * masquées : c'est précisément ce qu'il faut aller corriger.
     */
    public function test_les_lignes_sans_compte_apparaissent_en_non_impute(): void
    {
        $impute = Article::factory()->create(['compte_comptable' => '6063']);
        $orphelin = Article::factory()->create(['compte_comptable' => null]);

        $this->bonAvecLigne($impute, 1, 100000);
        $this->bonAvecLigne($orphelin, 1, 70000);

        $lignes = collect(app(StatistiquesAchatService::class)->depensesParImputation());

        $nonImpute = $lignes->firstWhere('compte', 'Non imputé');

        $this->assertNotNull($nonImpute, 'Les lignes sans compte doivent être visibles.');
        $this->assertTrue($nonImpute['non_impute']);
        $this->assertEqualsWithDelta(70000, $nonImpute['montant_ht'], 0.01);

        // Et le total de l'état couvre bien TOUTES les dépenses.
        $this->assertEqualsWithDelta(170000, $lignes->sum('montant_ht'), 0.01);
    }

    /** Un brouillon n'engage rien : il n'entre pas dans l'état. */
    public function test_les_brouillons_sont_exclus(): void
    {
        $article = Article::factory()->create(['compte_comptable' => '6063']);

        $brouillon = BonCommande::factory()->create(['statut' => BonCommande::STATUT_BROUILLON]);
        app(LignesBonCommandeService::class)->synchroniser($brouillon, [
            ['article_id' => $article->id, 'quantite' => 5, 'prix_unitaire_ht' => 100000, 'taux_tva' => 18],
        ]);

        $this->assertSame([], app(StatistiquesAchatService::class)->depensesParImputation());
    }

    /** Les régularisations restent exclues par défaut, comme les autres états. */
    public function test_les_regularisations_sont_exclues_par_defaut(): void
    {
        $article = Article::factory()->create(['compte_comptable' => '6063']);

        $regul = BonCommande::factory()->valide()->create(['est_regularisation' => true]);
        app(LignesBonCommandeService::class)->synchroniser($regul, [
            ['article_id' => $article->id, 'quantite' => 4, 'prix_unitaire_ht' => 100000, 'taux_tva' => 18],
        ]);

        $service = app(StatistiquesAchatService::class);

        $this->assertSame([], $service->depensesParImputation());
        $this->assertCount(1, $service->depensesParImputation(avecRegularisations: true));
    }

    // ── L'écran et les exports ─────────────────────────────────────────────

    public function test_la_carte_est_proposee_et_servie(): void
    {
        $article = Article::factory()->create(['compte_comptable' => '6063']);
        $this->bonAvecLigne($article);

        $this->actingAs($this->lecteur)
            ->get(route('achat.rapports.index'))
            ->assertOk()
            ->assertSee('data-carte="imputation"', false)
            ->assertSee('Imputation comptable');

        $this->actingAs($this->lecteur)
            ->getJson(route('achat.rapports.donnees', 'imputation'))
            ->assertOk()
            ->assertJsonPath('lignes.0.compte', '6063');
    }

    public function test_l_export_de_la_carte_fonctionne(): void
    {
        $article = Article::factory()->create(['compte_comptable' => '6063']);
        $this->bonAvecLigne($article);

        $this->actingAs($this->lecteur)
            ->get(route('achat.rapports.export', ['carte' => 'imputation', 'format' => 'csv']))
            ->assertOk()
            ->assertDownload();
    }

    public function test_la_carte_exige_la_permission(): void
    {
        $intrus = User::create([
            'name' => 'Intrus', 'last_name' => 'Imput', 'user_name' => 'intrus_imput',
            'email' => 'intrus-imput@example.com', 'password' => bcrypt('password'),
        ]);

        $this->actingAs($intrus)
            ->getJson(route('achat.rapports.donnees', 'imputation'))
            ->assertForbidden();
    }
}
