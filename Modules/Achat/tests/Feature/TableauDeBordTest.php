<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\AchatParametres;
use Modules\Achat\Services\StatistiquesAchatService;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-18 — le tableau de bord final (A-01).
 *
 * Le critère du recueil : CHAQUE CHIFFRE du dashboard est celui de sa liste
 * ou de son rapport. Un tableau de bord dont les nombres ne se retrouvent
 * nulle part ailleurs n'est pas un outil de pilotage, c'est une décoration.
 */
class TableauDeBordTest extends TestCase
{
    use RefreshDatabase;

    private User $utilisateur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        $this->utilisateur = $this->creerUtilisateur([
            'achat.dashboard.view',
            'achat.bons_commande.index',
            'achat.reliquats.index',
            'achat.rapports.view',
        ], 'pilote@example.com');
    }

    private function creerUtilisateur(array $permissions, string $email): User
    {
        $existant = User::query()->where('email', $email)->first();

        if ($existant !== null) {
            return $existant;
        }

        $user = User::create([
            'name' => 'Utilisateur '.$email,
            'last_name' => 'Test',
            'user_name' => 'user_'.md5($email),
            'email' => $email,
            'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    private function bon(
        string $statut = BonCommande::STATUT_VALIDE,
        float $prix = 100000,
        float $quantite = 10,
        float $livree = 0,
        ?string $date = null,
        ?int $ageJours = null,
    ): BonCommande {
        /*
         * Un bon NON ENGAGÉ ne peut pas porter de numéro : le CHECK
         * chk_bc_numero_si_engage l'interdit en base. On part donc de la
         * factory qui correspond au statut voulu, plutôt que de forcer un
         * statut sur un bon déjà numéroté.
         */
        $engage = in_array($statut, [
            BonCommande::STATUT_VALIDE,
            BonCommande::STATUT_PARTIEL,
            BonCommande::STATUT_LIVRE,
            BonCommande::STATUT_CLOTURE,
        ], true);

        $attributs = [
            'statut' => $statut,
            'date_document' => $date ?? now()->toDateString(),
        ];

        if ($engage) {
            $attributs['valide_le'] = $ageJours !== null ? now()->subDays($ageJours) : now();
        }

        $bon = $engage
            ? BonCommande::factory()->valide()->create($attributs)
            : BonCommande::factory()->create($attributs);

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->consommable()->create()->id,
            'quantite' => $quantite,
            'quantite_livree' => $livree,
            'prix_unitaire_ht' => $prix,
            'taux_tva' => 18,
        ]);

        app(\Modules\Achat\Services\CalculMontantsService::class)->recalculer($bon);

        return $bon->refresh();
    }

    private function kpis(?User $user = null): array
    {
        return $this->actingAs($user ?? $this->utilisateur)
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->viewData('kpis');
    }

    // ═══ LE critère : chaque chiffre égale celui de sa liste ════════════════

    public function test_le_kpi_bons_ouverts_egale_le_total_de_la_liste_filtree(): void
    {
        $this->bon(BonCommande::STATUT_VALIDE);
        $this->bon(BonCommande::STATUT_PARTIEL, livree: 3);
        $this->bon(BonCommande::STATUT_LIVRE, livree: 10);
        $this->bon(BonCommande::STATUT_BROUILLON);

        $kpi = $this->kpis()['bons_ouverts'];

        $liste = $this->actingAs($this->utilisateur)
            ->getJson(route('achat.bons-commande.data', ['statut' => ['VALIDE', 'PARTIEL']]))
            ->assertOk()
            ->json('total');

        $this->assertSame(2, $kpi);
        $this->assertSame($kpi, $liste, 'Le KPI doit être exactement le total de sa liste.');
    }

    public function test_le_kpi_a_valider_egale_le_total_de_la_liste_soumis(): void
    {
        $validateur = $this->creerUtilisateur([
            'achat.dashboard.view',
            'achat.bons_commande.index',
            'achat.bons_commande.valider',
        ], 'valideur-dashboard@example.com');

        $this->bon(BonCommande::STATUT_SOUMIS);
        $this->bon(BonCommande::STATUT_SOUMIS);
        $this->bon(BonCommande::STATUT_VALIDE);

        $kpi = $this->kpis($validateur)['a_valider'];

        $liste = $this->actingAs($validateur)
            ->getJson(route('achat.bons-commande.data', ['statut' => 'SOUMIS']))
            ->assertOk()
            ->json('total');

        $this->assertSame(2, $kpi);
        $this->assertSame($kpi, $liste);
    }

    public function test_le_kpi_reliquats_anciens_egale_le_total_de_l_ecran_a_06(): void
    {
        app(AchatParametres::class)->set('delai_alerte_reliquat_jours', 30);

        $this->bon(ageJours: 60, livree: 2);  // ancien
        $this->bon(ageJours: 45, livree: 0);  // ancien
        $this->bon(ageJours: 5, livree: 0);   // récent

        $kpi = $this->kpis()['reliquats_anciens'];

        $liste = $this->actingAs($this->utilisateur)
            ->getJson(route('achat.reliquats.data', ['age_min' => 30]))
            ->assertOk()
            ->json('total');

        $this->assertSame(2, $kpi);
        $this->assertSame($kpi, $liste, 'Le KPI et l\'écran A-06 comptent la même chose.');
    }

    public function test_l_engage_du_mois_egale_le_service_et_le_rapport(): void
    {
        $this->bon(prix: 100000, quantite: 10); // 1 000 000 HT
        $this->bon(prix: 50000, quantite: 2);   //   100 000 HT

        $kpi = $this->kpis()['engage_du_mois'];
        $service = app(StatistiquesAchatService::class)->engageDuMois()['ht'];

        $this->assertEqualsWithDelta(1100000, $kpi, 0.01);
        $this->assertEqualsWithDelta($service, $kpi, 0.01);
    }

    /** Le graphique Z2 est LE MÊME que celui de la carte de rapport. */
    public function test_le_graphique_est_celui_du_service_partage(): void
    {
        $this->bon(prix: 100000, quantite: 5);

        $evolution = $this->actingAs($this->utilisateur)
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->viewData('evolution');

        $this->assertSame(
            app(StatistiquesAchatService::class)->evolutionDouzeMois(),
            $evolution
        );
        $this->assertCount(12, $evolution);
    }

    // ═══ Les KPI par permission ════════════════════════════════════════════

    public function test_la_carte_a_valider_est_masquee_sans_le_visa(): void
    {
        $this->bon(BonCommande::STATUT_SOUMIS);

        // Le pilote n'a PAS la permission de valider : la carte n'a rien à
        // lui apprendre d'actionnable, elle est masquée (null).
        $this->assertNull($this->kpis()['a_valider']);

        $validateur = $this->creerUtilisateur([
            'achat.dashboard.view',
            'achat.bons_commande.valider',
        ], 'valideur-dashboard@example.com');

        $this->assertSame(1, $this->kpis($validateur)['a_valider']);
    }

    public function test_le_tableau_de_bord_exige_sa_permission(): void
    {
        $sansDroit = $this->creerUtilisateur(['achat.bons_commande.index'], 'sans-dashboard@example.com');

        $this->actingAs($sansDroit)
            ->get(route('achat.dashboard'))
            ->assertForbidden()
            ->assertSee('achat.dashboard.view');
    }

    // ═══ Dégradation partielle de la lecture Stock ═════════════════════════

    public function test_le_kpi_de_reception_compte_les_bons_d_entree_lies_non_valides(): void
    {
        $bon = $this->bon();

        \Modules\Stock\Models\Entree::factory()->create([
            'magasin_id' => \Modules\Stock\Models\Magasin::factory()->create()->id,
            'bon_commande_id' => $bon->id,
        ]);

        $this->assertSame(1, $this->kpis()['en_cours_reception']);
    }

    /**
     * Une entrée VALIDÉE n'est plus « en cours » : elle a été intégrée, elle
     * compte désormais dans les livraisons.
     */
    public function test_une_entree_validee_ne_compte_plus_comme_en_cours(): void
    {
        $bon = $this->bon();

        \Modules\Stock\Models\Entree::factory()->create([
            'magasin_id' => \Modules\Stock\Models\Magasin::factory()->create()->id,
            'bon_commande_id' => $bon->id,
            'statut' => \Modules\Stock\Models\Entree::STATUT_VALIDE,
        ]);

        $this->assertSame(0, $this->kpis()['en_cours_reception']);
    }

    /** Les entrées LIBRES (sans commande) ne concernent pas ce module. */
    public function test_une_entree_sans_commande_n_est_pas_comptee(): void
    {
        \Modules\Stock\Models\Entree::factory()->create([
            'magasin_id' => \Modules\Stock\Models\Magasin::factory()->create()->id,
        ]);

        $this->assertSame(0, $this->kpis()['en_cours_reception']);
    }

    // ═══ Z4 — le fil d'événements vient du JOURNAL ═════════════════════════

    public function test_le_fil_raconte_les_evenements_reels_du_journal(): void
    {
        $auteur = $this->creerUtilisateur([
            'achat.bons_commande.index',
            'achat.bons_commande.store',
            'achat.bons_commande.soumettre',
        ], 'auteur-fil@example.com');

        $validateur = $this->creerUtilisateur([
            'achat.bons_commande.index',
            'achat.bons_commande.valider',
        ], 'valideur-fil@example.com');

        $bon = BonCommande::factory()->create(['created_by' => $auteur->id]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->consommable()->create()->id,
            'quantite' => 1,
            'prix_unitaire_ht' => 5000,
        ]);
        app(\Modules\Achat\Services\CalculMontantsService::class)->recalculer($bon);

        app(\Modules\Achat\Services\CircuitSoumissionService::class)->soumettre($bon->refresh(), $auteur);
        app(\Modules\Achat\Services\VisaService::class)->valider($bon->refresh(), $validateur);

        $evenements = $this->actingAs($this->utilisateur)
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->viewData('evenements');

        // Le plus récent en tête : la validation.
        $this->assertStringContainsString('validé', $evenements->first()['phrase']);
        $this->assertSame($validateur->name, $evenements->first()['auteur']);

        // Et la soumission juste derrière.
        $this->assertTrue(
            $evenements->contains(fn ($e) => str_contains($e['phrase'], 'soumis au visa')),
            'Le fil doit raconter la soumission comme la validation.'
        );
    }

    public function test_le_fil_est_borne_a_dix_evenements(): void
    {
        $validateur = $this->creerUtilisateur(['achat.bons_commande.valider'], 'valideur-borne@example.com');

        for ($i = 0; $i < 12; $i++) {
            $bon = BonCommande::factory()->create(['statut' => BonCommande::STATUT_SOUMIS]);
            LigneCommande::factory()->create([
                'bon_commande_id' => $bon->id,
                'article_id' => Article::factory()->consommable()->create()->id,
                'quantite' => 1,
                'prix_unitaire_ht' => 1000,
            ]);
            app(\Modules\Achat\Services\VisaService::class)->valider($bon->refresh(), $validateur);
        }

        $evenements = $this->actingAs($this->utilisateur)
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->viewData('evenements');

        $this->assertCount(10, $evenements);
    }

    // ═══ Les états de l'écran ══════════════════════════════════════════════

    public function test_le_module_vide_enseigne_le_circuit(): void
    {
        $this->actingAs($this->utilisateur)
            ->get(route('achat.dashboard'))
            ->assertOk()
            // EV-01 : plutôt que d'afficher des zéros, on explique le chemin.
            // `false` : l'apostrophe est échappée par Blade dans le HTML.
            ->assertSee('Aucun bon de commande pour l', false)
            ->assertSee('Catalogue → bon de commande → visa → réception au magasin → parc', false);
    }

    public function test_le_dashboard_peuple_affiche_le_graphique_et_le_fil(): void
    {
        $this->bon();

        $contenu = $this->actingAs($this->utilisateur)
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('graphique-evolution', $contenu);
        $this->assertStringContainsString('Derniers événements', $contenu);
        // Équivalent textuel du graphique : un canvas seul n'est pas accessible.
        $this->assertStringContainsString('Voir les chiffres du graphique', $contenu);
    }

    public function test_la_carte_de_dette_disparait_a_zero(): void
    {
        $this->bon();

        // Aucun équipement au parc : pas de dette, pas de carte.
        $this->assertSame(0, $this->kpis()['dette_interim']);

        $this->actingAs($this->utilisateur)
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->assertDontSee('sans commande d', false);

        \Modules\Stock\Database\Factories\ParcInfoDeTest::equipement();

        $this->assertSame(1, $this->kpis()['dette_interim']);

        $this->actingAs($this->utilisateur)
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->assertSee('sans commande d', false);
    }
}
