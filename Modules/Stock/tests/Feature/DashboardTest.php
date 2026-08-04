<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Database\Factories\ParcInfoDeTest;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Services\MouvementService;
use Tests\TestCase;

/**
 * Tableau de bord (UX §1) : KPI, pastilles d'attente, alertes actionnables,
 * répartition, derniers mouvements.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Magasin $magasin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);

        $this->magasin = Magasin::factory()->create(['libelle' => 'Magasin Central']);
    }

    private function makeUser(array $permissions = [], ?string $role = null, string $email = 'u@example.com'): User
    {
        $user = User::create([
            'name' => 'Test', 'last_name' => 'User', 'user_name' => 'user_'.md5($email),
            'email' => $email, 'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }

    public function test_acces_refuse_sans_permission_et_redirection_invite(): void
    {
        $this->get(route('stock.dashboard'))->assertRedirect(route('login'));

        $this->actingAs($this->makeUser())
            ->get(route('stock.dashboard'))
            ->assertStatus(403);
    }

    public function test_les_six_kpi_sont_calcules(): void
    {
        // 3 références : une OK, une sous seuil, une en rupture
        $ok = Article::factory()->consommable()->create(['seuil_defaut' => 5, 'prix_indicatif' => 1000]);
        $sousSeuil = Article::factory()->consommable()->create(['seuil_defaut' => 10, 'prix_indicatif' => 2000]);
        $rupture = Article::factory()->consommable()->create(['seuil_defaut' => 5, 'prix_indicatif' => 500]);

        Niveau::factory()->create(['magasin_id' => $this->magasin->id, 'article_id' => $ok->id, 'quantite' => 20]);
        Niveau::factory()->create(['magasin_id' => $this->magasin->id, 'article_id' => $sousSeuil->id, 'quantite' => 4]);
        Niveau::factory()->create(['magasin_id' => $this->magasin->id, 'article_id' => $rupture->id, 'quantite' => 0]);

        EquipementMagasin::factory()->create(['magasin_id' => $this->magasin->id]);

        $reponse = $this->actingAs($this->makeUser(['stock.dashboard.view', 'stock.niveaux.index']))
            ->get(route('stock.dashboard'))
            ->assertOk();

        $kpis = $reponse->viewData('kpis');
        $this->assertSame(1, $kpis['magasins_actifs']);
        $this->assertSame(2, $kpis['references_en_stock']);   // les lignes à 0 ne comptent pas
        $this->assertSame(1, $kpis['sous_seuil']);
        $this->assertSame(1, $kpis['ruptures']);
        $this->assertSame(1, $kpis['equipements_en_stock']);
        $this->assertEqualsWithDelta(20 * 1000 + 4 * 2000, $kpis['valeur_estimee'], 0.001);
    }

    public function test_les_kpi_d_alerte_pointent_vers_l_etat_des_stocks_prefiltre(): void
    {
        $article = Article::factory()->consommable()->create(['seuil_defaut' => 10]);
        Niveau::factory()->create(['magasin_id' => $this->magasin->id, 'article_id' => $article->id, 'quantite' => 2]);

        $this->actingAs($this->makeUser(['stock.dashboard.view', 'stock.niveaux.index']))
            ->get(route('stock.dashboard'))
            ->assertOk()
            ->assertSee(route('stock.niveaux.index', ['statut' => 'SOUS_SEUIL']), false)
            ->assertSee(route('stock.niveaux.index', ['statut' => 'RUPTURE']), false);
    }

    public function test_les_alertes_listent_ruptures_puis_sous_seuil_avec_bouton_receptionner(): void
    {
        $sousSeuil = Article::factory()->consommable()->create(['nom' => 'Câble RJ45', 'seuil_defaut' => 10]);
        $rupture = Article::factory()->consommable()->create(['nom' => 'Cartouche Epson', 'seuil_defaut' => 5]);

        Niveau::factory()->create(['magasin_id' => $this->magasin->id, 'article_id' => $sousSeuil->id, 'quantite' => 3]);
        Niveau::factory()->create(['magasin_id' => $this->magasin->id, 'article_id' => $rupture->id, 'quantite' => 0]);

        $reponse = $this->actingAs($this->makeUser(['stock.dashboard.view', 'stock.entrees.store']))
            ->get(route('stock.dashboard'))
            ->assertOk()
            ->assertSee('Câble RJ45')
            ->assertSee('Cartouche Epson')
            ->assertSee('⛔ RUPTURE')
            ->assertSee('⚠ SOUS SEUIL')
            // Le bouton « ➜ Réceptionner » pré-remplit magasin + article
            // (& échappé en &amp; dans l'attribut href)
            ->assertSee("entrees/create?magasin_id={$this->magasin->id}&amp;article_id={$rupture->id}", false);

        // Les ruptures passent devant
        $this->assertSame($rupture->id, $reponse->viewData('alertes')->first()->article_id);
    }

    public function test_le_bouton_receptionner_ouvre_un_brouillon_prerempli(): void
    {
        $article = Article::factory()->consommable()->create(['nom' => 'Toner a receptionner', 'prix_indicatif' => 38000]);

        $reponse = $this->actingAs($this->makeUser(['stock.entrees.store']))
            ->get(route('stock.entrees.create', ['magasin_id' => $this->magasin->id, 'article_id' => $article->id]))
            ->assertOk()
            // La ligne est déjà posée (window.LIGNES_INITIALES) avec son coût
            ->assertSee($article->code, false)
            ->assertSee('Toner a receptionner', false)
            ->assertSee('38000', false);

        // Le magasin de l'alerte est pré-sélectionné
        $this->assertSame($this->magasin->id, $reponse->viewData('magasinPrerempli'));
        $this->assertSame($article->id, $reponse->viewData('articlePrerempli')['id']);
    }

    public function test_pastille_en_attente_par_magasin(): void
    {
        // Une entrée non validée : 12 articles + 2 équipements
        $entree = Entree::factory()->create(['magasin_id' => $this->magasin->id]);
        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => Article::factory()->consommable()->create()->id,
            'quantite' => 12,
        ]);
        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => Article::factory()->equipement()->create()->id,
            'quantite' => 2,
        ]);

        // Une sortie non validée
        Sortie::factory()->create(['magasin_id' => $this->magasin->id]);

        $reponse = $this->actingAs($this->makeUser(['stock.dashboard.view', 'stock.entrees.index']))
            ->get(route('stock.dashboard'))
            ->assertOk()
            ->assertSee('Bons enregistrés mais non validés', false);

        $attente = $reponse->viewData('enAttente')->first();
        $this->assertSame($this->magasin->id, $attente['magasin']->id);
        $this->assertSame(12.0, $attente['articles']);
        $this->assertSame(2.0, $attente['equipements']);
        $this->assertSame(1, $attente['sorties']);
    }

    public function test_pastille_des_bons_anciens_reservee_au_superviseur(): void
    {
        // Un brouillon plus vieux que le seuil de config
        Entree::factory()->create(['magasin_id' => $this->magasin->id])
            ->forceFill(['created_at' => now()->subDays(config('stock.jours_alerte_non_valides') + 5)])->save();

        $superviseur = $this->makeUser([], 'Superviseur stock', 'sup@example.com');
        $this->actingAs($superviseur)
            ->get(route('stock.dashboard'))
            ->assertOk()
            ->assertSee('non validé(s) depuis plus de');

        $magasinier = $this->makeUser([], 'Magasinier', 'mag@example.com');
        $this->actingAs($magasinier)
            ->get(route('stock.dashboard'))
            ->assertOk()
            ->assertDontSee('non validé(s) depuis plus de');
    }

    public function test_derniers_mouvements_et_repartition(): void
    {
        $article = Article::factory()->consommable()->create(['nom' => 'Ramette A4', 'prix_indicatif' => 3500]);
        $entree = Entree::factory()->validee()->create(['magasin_id' => $this->magasin->id]);

        app(MouvementService::class)->entree([
            'entree_id' => $entree->id,
            'magasin_id' => $this->magasin->id,
            'article_id' => $article->id,
            'quantite' => 7,
        ]);

        $reponse = $this->actingAs($this->makeUser(['stock.dashboard.view']))
            ->get(route('stock.dashboard'))
            ->assertOk()
            ->assertSee('Ramette A4')
            ->assertSee('⬆ ENTREE')          // badge icône + texte (S7)
            ->assertSee('+7')                 // quantité signée
            ->assertSee('Magasin Central');

        $mouvement = $reponse->viewData('derniersMouvements')->first();
        $this->assertSame($entree->numero, $mouvement['libelle_document']);
        $this->assertSame(route('stock.entrees.show', $entree->id), $mouvement['url_document']);

        $repartition = $reponse->viewData('repartition')->first();
        $this->assertSame(1, $repartition['nb_references']);
        $this->assertEqualsWithDelta(7 * 3500, $repartition['valeur'], 0.001);
    }

    public function test_tableau_de_bord_vide_reste_lisible(): void
    {
        $this->actingAs($this->makeUser(['stock.dashboard.view']))
            ->get(route('stock.dashboard'))
            ->assertOk()
            ->assertSee('Aucun article sous seuil ni en rupture')
            ->assertSee('Aucun mouvement enregistré');
    }

    public function test_les_actions_rapides_suivent_les_permissions(): void
    {
        $complet = $this->makeUser(['stock.dashboard.view', 'stock.entrees.store', 'stock.sorties.store', 'stock.transferts.store'], null, 'complet@example.com');
        $this->actingAs($complet)
            ->get(route('stock.dashboard'))
            ->assertOk()
            ->assertSee('+ Nouvelle entrée')
            ->assertSee('− Nouvelle sortie')
            ->assertSee('⇄ Nouveau transfert');

        $lecteur = $this->makeUser(['stock.dashboard.view'], null, 'lecteur@example.com');
        $this->actingAs($lecteur)
            ->get(route('stock.dashboard'))
            ->assertOk()
            ->assertDontSee('+ Nouvelle entrée')
            ->assertDontSee('⇄ Nouveau transfert');
    }
}
