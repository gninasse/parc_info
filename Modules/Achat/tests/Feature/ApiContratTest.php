<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\AchatReceptionService;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * Snapshots de l'API inter-modules d'Achat (API_Inter_Modules.md §4 et §7.1).
 *
 * « Les snapshots figés en CI SONT le contrat exécutable » : casser l'un de
 * ces tests signifie qu'un module consommateur (Stock) va casser aussi, et
 * impose donc un amendement délibéré du document, pas un ajustement discret.
 */
class ApiContratTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
    }

    /** @var array<string, User> */
    private array $consommateurs = [];

    /** Mémorisé : plusieurs appels dans un même test rendent le même compte. */
    private function consommateur(bool $habilite = true, string $email = 'stock@example.com'): User
    {
        if (isset($this->consommateurs[$email])) {
            return $this->consommateurs[$email];
        }

        $user = User::create([
            'name' => 'Magasinier', 'last_name' => 'Test', 'user_name' => 'user_'.md5($email),
            'email' => $email, 'password' => bcrypt('password'),
        ]);

        if ($habilite) {
            $user->givePermissionTo('achat.api.view');
        }

        return $this->consommateurs[$email] = $user;
    }

    /** @return array{0: BonCommande, 1: LigneCommande, 2: Article} */
    private function bonLivrable(): array
    {
        $fournisseur = Fournisseur::factory()->create(['raison_sociale' => 'Sonabel-Info']);
        $article = Article::factory()->create(['nom' => 'Latitude 3540']);

        $bon = BonCommande::factory()->valide()->create([
            'fournisseur_id' => $fournisseur->id,
            'fournisseur_libelle' => 'Sonabel-Info',
        ]);

        $ligne = LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'designation' => 'Latitude 3540',
            'nature' => Article::NATURE_EQUIPEMENT,
            'quantite' => 10,
            'quantite_livree' => 6,
            'prix_unitaire_ht' => 830000,
            'taux_tva' => 18,
        ]);

        app(\Modules\Achat\Services\CalculMontantsService::class)->recalculer($bon);

        return [$bon, $ligne, $article];
    }

    // ── §1.2 : permission serveur sur TOUS les endpoints ───────────────────

    public function test_tous_les_endpoints_exigent_la_permission_api(): void
    {
        [$bon, , $article] = $this->bonLivrable();
        $sansDroit = $this->consommateur(habilite: false, email: 'sans@example.com');

        $routes = [
            route('achat.api.bons-commande.a-livrer'),
            route('achat.api.bons-commande.resoudre', ['numero' => 'BC-2026-0001']),
            route('achat.api.bons-commande.lignes-a-livrer', $bon->id),
            route('achat.api.bons-commande.receptions', $bon->id),
            route('achat.api.articles.historique-prix', $article->id),
            route('achat.api.fournisseurs.cumul-mois', $bon->fournisseur_id),
        ];

        foreach ($routes as $url) {
            $this->actingAs($sansDroit)
                ->getJson($url)
                ->assertStatus(403)
                // 403 nominative, y compris en API (§1.2)
                ->assertJsonPath('permissions_requises.0', 'achat.api.view');
        }
    }

    // ── §4.1 — bons à livrer ───────────────────────────────────────────────

    public function test_snapshot_bons_commande_a_livrer(): void
    {
        [$bon] = $this->bonLivrable();

        $reponse = $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.a-livrer'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'numero', 'statut', 'fournisseur' => ['id', 'nom'],
                    'valide_le', 'lignes_restantes', 'unites_restantes', 'montant_ttc']],
            ]);

        $premier = $reponse->json('data.0');

        $this->assertSame($bon->numero, $premier['numero']);
        $this->assertSame('Sonabel-Info', $premier['fournisseur']['nom']);
        $this->assertSame(1, $premier['lignes_restantes']);
        $this->assertEqualsWithDelta(4, $premier['unites_restantes'], 0.001);
        // Montants en chaîne décimale (§1.2.7)
        $this->assertIsString($premier['montant_ttc']);
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $premier['montant_ttc']);
        // Date au format YYYY-MM-DD
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $premier['valide_le']);
    }

    public function test_seuls_les_bons_livrables_sont_listes(): void
    {
        $this->bonLivrable();
        BonCommande::factory()->create();            // brouillon
        BonCommande::factory()->soumis()->create();  // soumis
        BonCommande::factory()->livre()->create();   // soldé
        BonCommande::factory()->cloture()->create(); // clôturé

        $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.a-livrer'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_la_liste_est_filtrable_et_bornee(): void
    {
        [$bon] = $this->bonLivrable();

        // Filtre fournisseur
        $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.a-livrer', ['fournisseur_id' => $bon->fournisseur_id]))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.a-livrer', ['fournisseur_id' => 999999]))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Recherche par numéro, puis par désignation d'article
        $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.a-livrer', ['q' => $bon->numero]))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.a-livrer', ['q' => 'latitude']))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ── §4.2 — lignes à livrer (le contrat de pré-remplissage) ─────────────

    public function test_snapshot_lignes_a_livrer(): void
    {
        [$bon, $ligne, $article] = $this->bonLivrable();

        $reponse = $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.lignes-a-livrer', $bon->id))
            ->assertOk()
            ->assertJsonStructure([
                'bon_commande' => ['id', 'numero', 'statut', 'fournisseur_id'],
                'lignes' => [['ligne_id', 'article_id', 'code', 'designation', 'nature',
                    'quantite_commandee', 'quantite_livree', 'reste_a_livrer',
                    'prix_unitaire_ht', 'taux_tva']],
            ]);

        $premiere = $reponse->json('lignes.0');

        $this->assertSame($ligne->id, $premiere['ligne_id']);
        $this->assertSame($article->id, $premiere['article_id']);
        $this->assertSame('4.00', $premiere['reste_a_livrer']);
        // Le PRIX FIGÉ de la commande, jamais le prix indicatif courant (A10)
        $this->assertSame('830000.00', $premiere['prix_unitaire_ht']);
        $this->assertSame('18.00', $premiere['taux_tva']);
    }

    public function test_les_lignes_soldees_ne_sont_pas_proposees_au_prereemplissage(): void
    {
        $bon = BonCommande::factory()->partiel()->create();
        $article = Article::factory()->create();

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id, 'article_id' => $article->id,
            'quantite' => 5, 'quantite_livree' => 5, // soldée
        ]);

        $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.lignes-a-livrer', $bon->id))
            ->assertOk()
            ->assertJsonCount(0, 'lignes');
    }

    public function test_un_bon_non_livrable_renvoie_422_explicite(): void
    {
        $brouillon = BonCommande::factory()->create();

        $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.lignes-a-livrer', $brouillon->id))
            ->assertStatus(422)
            ->assertJsonPath('message', "Ce bon n'est pas livrable (statut : Brouillon).");
    }

    public function test_un_bon_inexistant_renvoie_404(): void
    {
        $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.lignes-a-livrer', 999999))
            ->assertStatus(404);
    }

    // ── §4.3 — résolution du QR ────────────────────────────────────────────

    public function test_snapshot_resolution_d_un_numero_scanne(): void
    {
        [$bon] = $this->bonLivrable();

        $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.resoudre', ['numero' => $bon->numero]))
            ->assertOk()
            ->assertJson(['id' => $bon->id, 'numero' => $bon->numero, 'statut' => $bon->statut]);
    }

    public function test_un_numero_inconnu_renvoie_404_nominatif(): void
    {
        $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.resoudre', ['numero' => 'BC-2026-0099']))
            ->assertStatus(404)
            ->assertJsonPath('message', 'Aucun bon de commande BC-2026-0099.');
    }

    // ── §4.4 — la référence de prix non manipulable (A14) ──────────────────

    public function test_snapshot_historique_prix(): void
    {
        $article = Article::factory()->create();

        // Trois bons engagés à des prix différents
        foreach ([[600000, 5], [645000, 3], [700000, 1]] as $index => [$prix, $joursAvant]) {
            $bon = BonCommande::factory()->valide()->create([
                'valide_le' => now()->subDays($joursAvant),
            ]);
            LigneCommande::factory()->create([
                'bon_commande_id' => $bon->id, 'article_id' => $article->id,
                'prix_unitaire_ht' => $prix, 'quantite' => 1,
            ]);
        }

        // Un brouillon ne compte pas : il n'a jamais été payé
        $brouillon = BonCommande::factory()->create();
        LigneCommande::factory()->create([
            'bon_commande_id' => $brouillon->id, 'article_id' => $article->id,
            'prix_unitaire_ht' => 9999999, 'quantite' => 1,
        ]);

        $reponse = $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.articles.historique-prix', $article->id))
            ->assertOk()
            ->assertJsonStructure([
                'article_id',
                'dernier_paye' => ['prix', 'numero', 'date'],
                'moyenne_3_derniers', 'nb_bc_references',
            ]);

        // Le plus r��cent des bons engagés
        $this->assertSame('700000.00', $reponse->json('dernier_paye.prix'));
        $this->assertSame(3, $reponse->json('nb_bc_references'));
        $this->assertSame('648333.33', $reponse->json('moyenne_3_derniers'));
    }

    public function test_un_article_jamais_commande_a_une_reference_nulle(): void
    {
        $article = Article::factory()->create();

        $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.articles.historique-prix', $article->id))
            ->assertOk()
            ->assertJson(['dernier_paye' => null, 'nb_bc_references' => 0]);
    }

    // ── §4.5 — cumul fournisseur du mois ───────────────────────────────────

    public function test_snapshot_cumul_mois_fournisseur(): void
    {
        [$bon] = $this->bonLivrable();

        $reponse = $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.fournisseurs.cumul-mois', $bon->fournisseur_id))
            ->assertOk()
            ->assertJsonStructure([
                'fournisseur_id', 'mois', 'nb_bc_valides', 'cumul_ttc',
                'premier_bc', 'fournisseur_cree_le',
            ]);

        $this->assertSame(1, $reponse->json('nb_bc_valides'));
        $this->assertSame(now()->format('Y-m'), $reponse->json('mois'));
        $this->assertFalse($reponse->json('premier_bc'));
    }

    /** Les régularisations documentent le passé : hors cumul du mois (§7.6). */
    public function test_le_cumul_exclut_les_regularisations(): void
    {
        $fournisseur = Fournisseur::factory()->create();

        BonCommande::factory()->valide()->create(['fournisseur_id' => $fournisseur->id]);
        BonCommande::factory()->valide()->regularisation()->create(['fournisseur_id' => $fournisseur->id]);

        $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.fournisseurs.cumul-mois', $fournisseur->id))
            ->assertOk()
            ->assertJsonPath('nb_bc_valides', 1);
    }

    // ── §4.6 — réceptions consolidées ──────────────────────────────────────

    public function test_snapshot_receptions_consolidees(): void
    {
        [$bon, , $article] = $this->bonLivrable();

        app(AchatReceptionService::class)->integrer(
            $bon->id, 55, [['article_id' => $article->id, 'quantite' => 2]], 'ENT-2026-0055'
        );

        $reponse = $this->actingAs($this->consommateur())
            ->getJson(route('achat.api.bons-commande.receptions', $bon->id))
            ->assertOk()
            ->assertJsonStructure([
                'bon_commande' => ['id', 'numero', 'statut'],
                'data' => [['sens', 'entree_id', 'mouvement_id', 'reference', 'integre_le', 'lignes']],
            ]);

        $this->assertSame('RECEPTION', $reponse->json('data.0.sens'));
        $this->assertSame(55, $reponse->json('data.0.entree_id'));
        $this->assertSame('ENT-2026-0055', $reponse->json('data.0.reference'));
    }

    // ── §1.2.6 : neutralité des GET ────────────────────────────────────────

    public function test_les_endpoints_de_lecture_n_ont_aucun_effet_de_bord(): void
    {
        [$bon, $ligne] = $this->bonLivrable();
        $avant = [$bon->statut, (float) $ligne->quantite_livree];

        $consommateur = $this->consommateur();
        foreach ([
            route('achat.api.bons-commande.a-livrer'),
            route('achat.api.bons-commande.lignes-a-livrer', $bon->id),
            route('achat.api.bons-commande.receptions', $bon->id),
        ] as $url) {
            $this->actingAs($consommateur)->getJson($url)->assertOk();
        }

        $this->assertSame($avant, [$bon->fresh()->statut, (float) $ligne->fresh()->quantite_livree]);
    }
}
