<?php

namespace Modules\Achat\Tests\Feature;

use Modules\Achat\Exceptions\RegleMetierException;
use Modules\Achat\Models\Article;
use Modules\Achat\Services\ArticleService;

/**
 * Catalogue des articles.
 * Couvre RGC-08 et les scénarios REC-01, REC-02.
 */
class CatalogueTest extends AchatTestCase
{
    public function test_l_ecran_du_catalogue_repond(): void
    {
        $this->get(route('achat.articles.index'))->assertOk();
    }

    public function test_la_route_de_donnees_renvoie_la_structure_attendue(): void
    {
        $this->creerArticle();

        $this->getJson(route('achat.articles.data'))
            ->assertOk()
            ->assertJsonStructure(['total', 'rows']);
    }

    /** REC-01 — Référencement des trois natures principales. */
    public function test_un_equipement_exige_une_categorie(): void
    {
        $this->expectException(RegleMetierException::class);

        app(ArticleService::class)->creer([
            'designation' => 'Poste sans catégorie',
            'type_article' => 'equipement',
            'marque_id' => $this->marque->id,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
        ]);
    }

    public function test_la_categorie_est_ignoree_pour_un_consommable(): void
    {
        $article = app(ArticleService::class)->creer([
            'designation' => 'Cartouche encre noire',
            'type_article' => 'consommable',
            'marque_id' => $this->marque->id,
            'categorie_equipement_id' => $this->categorie->id, // ne doit pas être conservée
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
        ]);

        $this->assertNull($article->categorie_equipement_id);
    }

    /** EF-CAT-13 — Le code est généré lorsqu'il n'est pas fourni. */
    public function test_le_code_article_est_genere_lorsqu_il_est_absent(): void
    {
        $article = app(ArticleService::class)->creer([
            'designation' => 'Article sans code',
            'type_article' => 'consommable',
            'marque_id' => $this->marque->id,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
        ]);

        $this->assertMatchesRegularExpression('/^CNS-\d{5}$/', $article->code_article);
    }

    public function test_les_codes_generes_ne_se_chevauchent_pas(): void
    {
        $codes = collect(range(1, 4))->map(fn () => app(ArticleService::class)->creer([
            'designation' => 'Consommable en série',
            'type_article' => 'consommable',
            'marque_id' => $this->marque->id,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
        ])->code_article);

        $this->assertCount(4, $codes->unique());
    }

    /** RGC-08 / REC-02 — Un article engagé est désactivé, jamais supprimé. */
    public function test_un_article_engage_est_desactive_et_non_supprime(): void
    {
        $article = $this->creerArticle();
        $this->creerBonCommande($article, 2);

        $supprime = app(ArticleService::class)->supprimer($article);

        $this->assertFalse($supprime, 'Le service doit signaler une désactivation.');
        $this->assertFalse($article->refresh()->actif);
        $this->assertDatabaseHas('achat_articles', ['id' => $article->id, 'deleted_at' => null]);
    }

    public function test_un_article_libre_est_reellement_supprime(): void
    {
        $article = $this->creerArticle();

        $supprime = app(ArticleService::class)->supprimer($article);

        $this->assertTrue($supprime);
        $this->assertSoftDeleted('achat_articles', ['id' => $article->id]);
    }

    /** ENF-FIA-06 — La désactivation n'est pas annoncée comme une suppression. */
    public function test_la_reponse_distingue_suppression_et_desactivation(): void
    {
        $article = $this->creerArticle();
        $this->creerBonCommande($article, 1);

        $this->deleteJson(route('achat.articles.destroy', $article))
            ->assertOk()
            ->assertJson(['success' => true, 'supprime' => false]);
    }

    public function test_la_duplication_produit_un_code_libre(): void
    {
        $article = $this->creerArticle(['code_article' => 'ART-SOURCE']);

        $copie = app(ArticleService::class)->dupliquer($article);

        $this->assertNotSame($article->code_article, $copie->code_article);
        $this->assertStringContainsString('COPY', $copie->code_article);
        $this->assertNull($copie->reference_constructeur);
    }

    /** RG-ART-09 — Seuls les articles actifs sont commandables. */
    public function test_le_formulaire_de_commande_ne_propose_que_les_articles_actifs(): void
    {
        $this->creerArticle(['designation' => 'Article actif']);
        $this->creerArticle(['designation' => 'Article retiré', 'actif' => false]);

        $reponse = $this->get(route('achat.bons-commande.create'))->assertOk();

        $catalogue = collect($reponse->viewData('articlesCatalogue'));

        $this->assertTrue($catalogue->contains('designation', 'Article actif'));
        $this->assertFalse($catalogue->contains('designation', 'Article retiré'));
    }

}
