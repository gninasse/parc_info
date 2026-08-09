<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Niveau;
use Tests\TestCase;

/**
 * D-21 côté STOCK — le bouton « Commander » sur l'état des stocks.
 *
 * Le contrat serveur est testé par `Achat\CommanderDepuisAlerteTest` ; ici on
 * vérifie ce que le magasinier VOIT, et surtout ce qu'il ne voit pas :
 *
 *   - le bouton n'apparaît que pour qui peut réellement créer un bon. Un
 *     bouton visible mais refusé au clic est pire que pas de bouton : il
 *     apprend à l'utilisateur que l'application ment ;
 *   - la charge de la table porte `article_id`, sans lequel la commande ne
 *     peut pas partir.
 */
class CommanderDepuisNiveauxTest extends TestCase
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

    private function utilisateur(array $permissions, string $cle): User
    {
        $user = User::create([
            'name' => 'Utilisateur', 'last_name' => $cle, 'user_name' => 'u_'.$cle,
            'email' => $cle.'@example.com', 'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    private function niveauSousSeuil(): Niveau
    {
        $article = Article::factory()->create([
            'nom' => 'Toner 26A',
            'seuil_defaut' => 10,
            'prix_indicatif' => 42000,
            'fournisseur_principal_id' => Fournisseur::factory()->create()->id,
        ]);

        return Niveau::create([
            'magasin_id' => Magasin::factory()->create()->id,
            'article_id' => $article->id,
            'quantite' => 2,
            'seuil' => 10,
        ]);
    }

    // ── Ce que l'écran montre ──────────────────────────────────────────────

    public function test_le_bouton_apparait_pour_qui_peut_creer_un_bon(): void
    {
        $this->niveauSousSeuil();

        $magasinierAcheteur = $this->utilisateur([
            'stock.niveaux.index', 'achat.api.view', 'achat.bons_commande.store',
        ], 'magasinier_acheteur');

        $this->actingAs($magasinierAcheteur)
            ->get(route('stock.niveaux.index'))
            ->assertOk()
            ->assertSee('btn-commander', false)
            ->assertSee('Commander');
    }

    /**
     * Un bouton visible mais refusé au clic apprend à l'utilisateur que
     * l'application ment. Sans la permission d'Achat, il n'existe pas.
     */
    public function test_le_bouton_est_absent_sans_la_permission_d_achat(): void
    {
        $this->niveauSousSeuil();

        $magasinierSeul = $this->utilisateur(['stock.niveaux.index'], 'magasinier_seul');

        $this->actingAs($magasinierSeul)
            ->get(route('stock.niveaux.index'))
            ->assertOk()
            ->assertDontSee('btn-commander', false);
    }

    /** Lire l'API sans pouvoir créer ne suffit pas non plus. */
    public function test_le_bouton_est_absent_sans_le_droit_de_creer(): void
    {
        $this->niveauSousSeuil();

        $lecteur = $this->utilisateur(['stock.niveaux.index', 'achat.api.view'], 'lecteur_seul');

        $this->actingAs($lecteur)
            ->get(route('stock.niveaux.index'))
            ->assertOk()
            ->assertDontSee('btn-commander', false);
    }

    // ── Ce que la table sert ───────────────────────────────────────────────

    /** Sans `article_id`, la commande ne peut pas partir. */
    public function test_la_charge_porte_l_identifiant_de_l_article(): void
    {
        $niveau = $this->niveauSousSeuil();

        $magasinier = $this->utilisateur(['stock.niveaux.index'], 'magasinier_data');

        $this->actingAs($magasinier)
            ->getJson(route('stock.niveaux.data'))
            ->assertOk()
            ->assertJsonPath('rows.0.article_id', $niveau->article_id);
    }

    // ── Le parcours complet ────────────────────────────────────────────────

    /**
     * De l'alerte au brouillon : le geste que D-21 rend possible, joué de
     * bout en bout avec un seul compte.
     */
    public function test_de_l_alerte_au_brouillon(): void
    {
        $niveau = $this->niveauSousSeuil();

        $magasinier = $this->utilisateur([
            'stock.niveaux.index', 'achat.api.view', 'achat.bons_commande.store',
        ], 'parcours_complet');

        // 1. Le magasinier voit son alerte…
        $lignes = $this->actingAs($magasinier)
            ->getJson(route('stock.niveaux.data'))
            ->assertOk()
            ->json('rows');

        $enAlerte = collect($lignes)->firstWhere('article_id', $niveau->article_id);

        $this->assertNotNull($enAlerte);
        // 2 en stock pour un seuil de 10 : sous seuil, pas encore en rupture.
        $this->assertSame('SOUS_SEUIL', $enAlerte['statut']);

        // 2. …et commande directement.
        $reponse = $this->actingAs($magasinier)
            ->postJson(route('achat.api.bons-commande.brouillon-depuis-articles'), [
                'article_ids' => [$enAlerte['article_id']],
                'magasin' => $enAlerte['magasin'],
            ])
            ->assertOk();

        $bon = BonCommande::query()->findOrFail($reponse->json('data.id'));

        $this->assertSame(BonCommande::STATUT_BROUILLON, $bon->statut);
        $this->assertSame('Toner 26A', $bon->lignes()->firstOrFail()->designation);
        // seuil 10 × facteur 2
        $this->assertEqualsWithDelta(20, (float) $bon->lignes()->firstOrFail()->quantite, 0.01);
    }
}
