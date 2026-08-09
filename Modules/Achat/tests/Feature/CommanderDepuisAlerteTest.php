<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Http\Controllers\ApiController;
use Modules\Achat\Models\BonCommande;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\Activity;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-21 — « Commander » depuis une alerte de seuil.
 *
 * Ferme la boucle rupture → commande. Aujourd'hui le magasinier constate un
 * manque à l'écran, le signale par téléphone, et l'acheteur ressaisit tout :
 * chaque relais perd de l'information, et l'article reste en rupture pendant
 * ce temps.
 *
 * Trois garde-fous que ces tests protègent :
 *
 *   1. le geste crée un BROUILLON, jamais un bon engagé — il part du magasin,
 *      il ne saurait engager l'établissement ;
 *   2. le fournisseur n'est PAS deviné quand les articles en ont plusieurs :
 *      un bon s'adresse à un fournisseur unique, et choisir au hasard
 *      produirait des lignes commandées au mauvais endroit — une erreur
 *      silencieuse, découverte à la livraison ;
 *   3. la permission de CRÉER un bon est exigée, pas seulement celle de lire
 *      l'API : ce point d'entrée écrit.
 */
class CommanderDepuisAlerteTest extends TestCase
{
    use RefreshDatabase;

    private User $magasinier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        // Le profil réel du magasinier autorisé à déclencher une commande :
        // il lit l'API d'Achat et peut créer un brouillon, rien de plus.
        $this->magasinier = User::create([
            'name' => 'Salamata', 'last_name' => 'Magasin', 'user_name' => 'salamata_d21',
            'email' => 'salamata-d21@example.com', 'password' => bcrypt('password'),
        ]);

        foreach (['achat.api.view', 'achat.bons_commande.store'] as $permission) {
            $this->magasinier->givePermissionTo($permission);
        }
    }

    private function commander(array $articleIds, array $extra = [])
    {
        return $this->actingAs($this->magasinier)->postJson(
            route('achat.api.bons-commande.brouillon-depuis-articles'),
            array_merge(['article_ids' => $articleIds], $extra)
        );
    }

    // ── Le geste nominal ───────────────────────────────────────────────────

    public function test_un_brouillon_pre_rempli_est_cree(): void
    {
        $fournisseur = Fournisseur::factory()->create();

        $article = Article::factory()->create([
            'nom' => 'Toner 26A',
            'seuil_defaut' => 5,
            'prix_indicatif' => 42000,
            'fournisseur_principal_id' => $fournisseur->id,
        ]);

        $reponse = $this->commander([$article->id])->assertOk();

        $bon = BonCommande::query()->findOrFail($reponse->json('data.id'));

        // Un BROUILLON : le magasin propose, l'acheteur dispose.
        $this->assertSame(BonCommande::STATUT_BROUILLON, $bon->statut);
        $this->assertNull($bon->numero, 'Un brouillon ne consomme pas de numéro.');

        $ligne = $bon->lignes()->firstOrFail();

        $this->assertSame($article->id, $ligne->article_id);
        $this->assertSame('Toner 26A', $ligne->designation);
        // seuil (5) × facteur (2, paramètre par défaut)
        $this->assertEqualsWithDelta(10, (float) $ligne->quantite, 0.01);
        $this->assertEqualsWithDelta(42000, (float) $ligne->prix_unitaire_ht, 0.01);

        // Le fournisseur préféré est repris : il est le même pour tous.
        $this->assertSame($fournisseur->id, $bon->fournisseur_id);
    }

    public function test_plusieurs_articles_donnent_plusieurs_lignes(): void
    {
        $fournisseur = Fournisseur::factory()->create();

        $articles = collect(range(1, 3))->map(fn ($i) => Article::factory()->create([
            'seuil_defaut' => $i,
            'prix_indicatif' => 1000 * $i,
            'fournisseur_principal_id' => $fournisseur->id,
        ]));

        $reponse = $this->commander($articles->pluck('id')->all())->assertOk();

        $this->assertSame(3, $reponse->json('data.nb_lignes'));
        $this->assertSame(3, BonCommande::query()->findOrFail($reponse->json('data.id'))->lignes()->count());
    }

    /**
     * LE garde-fou : des articles de fournisseurs différents ne donnent pas
     * un fournisseur choisi au hasard.
     *
     * Un bon de commande s'adresse à UN fournisseur — le schéma l'impose et
     * le métier avec lui. La demande est donc refusée, avec la liste des
     * fournisseurs concernés : l'écran peut poser la question plutôt que de
     * produire des lignes commandées au mauvais endroit, erreur silencieuse
     * qu'on ne découvre qu'à la livraison.
     */
    public function test_des_fournisseurs_differents_font_poser_la_question(): void
    {
        $premier = Fournisseur::factory()->create(['raison_sociale' => 'Alpha SARL']);
        $second = Fournisseur::factory()->create(['raison_sociale' => 'Beta SA']);

        $a = Article::factory()->create(['fournisseur_principal_id' => $premier->id]);
        $b = Article::factory()->create(['fournisseur_principal_id' => $second->id]);

        $reponse = $this->commander([$a->id, $b->id])->assertStatus(422);

        $this->assertSame('fournisseur_indetermine', $reponse->json('motif'));
        $this->assertCount(2, $reponse->json('data.fournisseurs'));
        $this->assertSame(0, BonCommande::query()->count(), 'Rien ne doit être créé.');

        // L'appelant tranche : la commande passe.
        $this->commander([$a->id, $b->id], ['fournisseur_id' => $premier->id])->assertOk();

        $this->assertSame($premier->id, BonCommande::query()->firstOrFail()->fournisseur_id);
    }

    /** Un article sans fournisseur préféré fait poser la même question. */
    public function test_un_article_sans_fournisseur_prefere_fait_poser_la_question(): void
    {
        $article = Article::factory()->create(['fournisseur_principal_id' => null]);

        $reponse = $this->commander([$article->id])->assertStatus(422);

        $this->assertSame('fournisseur_indetermine', $reponse->json('motif'));
        $this->assertStringContainsString('choisissez le fournisseur', $reponse->json('message'));
    }

    /** Un article sans seuil ne propose pas zéro : un brouillon à zéro ne sert à rien. */
    public function test_un_article_sans_seuil_propose_une_unite(): void
    {
        $article = Article::factory()->create([
            'seuil_defaut' => null,
            'prix_indicatif' => 5000,
            'fournisseur_principal_id' => Fournisseur::factory()->create()->id,
        ]);

        $reponse = $this->commander([$article->id])->assertOk();

        $ligne = BonCommande::query()->findOrFail($reponse->json('data.id'))->lignes()->firstOrFail();

        $this->assertEqualsWithDelta(1, (float) $ligne->quantite, 0.01);
    }

    /** Le facteur est un paramètre d'établissement, il doit mordre. */
    public function test_le_facteur_de_reapprovisionnement_est_parametrable(): void
    {
        \Modules\Achat\Models\Parametre::query()
            ->updateOrCreate(['cle' => 'facteur_reapprovisionnement'], ['valeur' => '4']);
        app(\Modules\Achat\Services\AchatParametres::class)->oublierTout();

        $article = Article::factory()->create([
            'seuil_defaut' => 5,
            'prix_indicatif' => 1000,
            'fournisseur_principal_id' => Fournisseur::factory()->create()->id,
        ]);

        $reponse = $this->commander([$article->id])->assertOk();

        $ligne = BonCommande::query()->findOrFail($reponse->json('data.id'))->lignes()->firstOrFail();

        $this->assertEqualsWithDelta(20, (float) $ligne->quantite, 0.01);
    }

    // ── Les refus ──────────────────────────────────────────────────────────

    /**
     * Lire l'API ne suffit pas : ce point d'entrée ÉCRIT. Un magasinier qui
     * peut consulter les commandes n'a pas pour autant le droit d'en ouvrir.
     */
    public function test_lire_l_api_ne_suffit_pas_pour_creer(): void
    {
        $lecteur = User::create([
            'name' => 'Lecteur', 'last_name' => 'Api', 'user_name' => 'lecteur_api_d21',
            'email' => 'lecteur-api-d21@example.com', 'password' => bcrypt('password'),
        ]);
        $lecteur->givePermissionTo('achat.api.view');

        $article = Article::factory()->create();

        $this->actingAs($lecteur)
            ->postJson(route('achat.api.bons-commande.brouillon-depuis-articles'), [
                'article_ids' => [$article->id],
            ])
            ->assertForbidden();

        $this->assertSame(0, BonCommande::query()->count());
    }

    public function test_sans_permission_d_api_l_acces_est_refuse(): void
    {
        $intrus = User::create([
            'name' => 'Intrus', 'last_name' => 'D21', 'user_name' => 'intrus_d21',
            'email' => 'intrus-d21@example.com', 'password' => bcrypt('password'),
        ]);

        $this->actingAs($intrus)
            ->postJson(route('achat.api.bons-commande.brouillon-depuis-articles'), ['article_ids' => [1]])
            ->assertForbidden();
    }

    public function test_une_liste_vide_est_refusee(): void
    {
        $this->commander([])
            ->assertStatus(422)
            ->assertJsonValidationErrors('article_ids');
    }

    public function test_un_article_inconnu_est_refuse(): void
    {
        $this->commander([999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('article_ids.0');
    }

    /** Un article désactivé au catalogue ne se commande plus. */
    public function test_un_article_desactive_ne_se_commande_pas(): void
    {
        $article = Article::factory()->create(['est_actif' => false]);

        $this->commander([$article->id])->assertStatus(422);

        $this->assertSame(0, BonCommande::query()->count());
    }

    // ── La trace ───────────────────────────────────────────────────────────

    /**
     * L'origine au journal : six mois plus tard, on doit pouvoir dire d'où
     * venait ce bon — et donc si la boucle rupture → commande fonctionne.
     */
    public function test_l_origine_est_journalisee(): void
    {
        $article = Article::factory()->create([
            'seuil_defaut' => 2,
            'prix_indicatif' => 1000,
            'fournisseur_principal_id' => Fournisseur::factory()->create()->id,
        ]);

        $reponse = $this->commander([$article->id], ['magasin' => 'Magasin CHU-YO'])->assertOk();

        $trace = Activity::query()
            ->where('subject_type', BonCommande::class)
            ->where('subject_id', $reponse->json('data.id'))
            ->where('description', ApiController::EVENEMENT_DEPUIS_ALERTE)
            ->first();

        $this->assertNotNull($trace, 'La provenance doit figurer au journal.');
        $this->assertSame('achat', $trace->module);
        $this->assertSame($this->magasinier->id, $trace->causer_id);
        $this->assertSame('alerte_seuil_stock', $trace->properties['origine']);
        $this->assertSame('Magasin CHU-YO', $trace->properties['magasin']);
        $this->assertContains($article->code, $trace->properties['articles']);
    }

    /** La réponse porte l'URL de reprise : l'écran d'origine ne route pas Achat. */
    public function test_la_reponse_porte_l_url_de_reprise(): void
    {
        $article = Article::factory()->create([
            'prix_indicatif' => 1000,
            'fournisseur_principal_id' => Fournisseur::factory()->create()->id,
        ]);

        $reponse = $this->commander([$article->id])->assertOk();

        $this->assertSame(
            route('achat.bons-commande.edit', $reponse->json('data.id')),
            $reponse->json('data.url')
        );
    }
}
