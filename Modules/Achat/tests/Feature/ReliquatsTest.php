<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\AchatParametres;
use Modules\Achat\Services\AchatReceptionService;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-14 — l'écran des reliquats (A-06).
 *
 * Le critère du recueil : des TOTAUX EXACTS par filtre. Le chiffre du pied
 * (« engagé non livré ») est celui qu'un gestionnaire va lire pour arbitrer :
 * s'il décrit la page affichée au lieu du filtre entier, il ment.
 */
class ReliquatsTest extends TestCase
{
    use RefreshDatabase;

    private User $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        $this->gestionnaire = $this->utilisateur([
            'achat.bons_commande.index',
            'achat.reliquats.index',
        ], 'gestionnaire-reliquats@example.com');
    }

    private function utilisateur(array $permissions, string $email): User
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

    /**
     * Un BC engagé avec une ligne non soldée.
     *
     * @return array{0: BonCommande, 1: LigneCommande}
     */
    private function bonAvecReliquat(
        float $quantite = 10,
        float $livree = 0,
        int $ageJours = 5,
        ?Fournisseur $fournisseur = null,
        float $prix = 100000,
        string $statut = BonCommande::STATUT_VALIDE,
    ): array {
        $bon = BonCommande::factory()->valide()->create([
            'fournisseur_id' => ($fournisseur ?? Fournisseur::factory()->create())->id,
            'valide_le' => now()->subDays($ageJours),
            'statut' => $statut,
        ]);

        $ligne = LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->consommable()->create()->id,
            'nature' => 'consommable',
            'quantite' => $quantite,
            'quantite_livree' => $livree,
            'prix_unitaire_ht' => $prix,
            'taux_tva' => 18,
        ]);

        return [$bon->refresh(), $ligne->refresh()];
    }

    private function data(array $params = []): array
    {
        return $this->actingAs($this->gestionnaire)
            ->getJson(route('achat.reliquats.data', $params))
            ->assertOk()
            ->json();
    }

    // ═══ Périmètre : ce qui est un reliquat, et ce qui n'en est pas ═════════

    public function test_l_ecran_exige_sa_permission(): void
    {
        $sansDroit = $this->utilisateur(['achat.dashboard.view'], 'sans-droit-reliquats@example.com');

        $this->actingAs($sansDroit)
            ->get(route('achat.reliquats.index'))
            ->assertForbidden()
            ->assertSee('achat.reliquats.index');

        $this->actingAs($sansDroit)
            ->getJson(route('achat.reliquats.data'))
            ->assertForbidden();
    }

    public function test_une_ligne_non_soldee_d_un_bon_valide_est_un_reliquat(): void
    {
        [$bon, $ligne] = $this->bonAvecReliquat(10, 4);

        $donnees = $this->data();

        $this->assertSame(1, $donnees['total']);
        $this->assertSame($ligne->id, $donnees['rows'][0]['ligne_id']);
        $this->assertEqualsWithDelta(6, $donnees['rows'][0]['reste'], 0.001);
        $this->assertSame($bon->numero, $donnees['rows'][0]['numero']);
    }

    public function test_une_ligne_soldee_n_est_pas_un_reliquat(): void
    {
        $this->bonAvecReliquat(10, 10);

        $this->assertSame(0, $this->data()['total']);
    }

    public function test_un_brouillon_n_engage_rien_donc_n_apparait_pas(): void
    {
        $bon = BonCommande::factory()->create(); // BROUILLON
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->consommable()->create()->id,
            'quantite' => 10,
            'quantite_livree' => 0,
        ]);

        $this->assertSame(0, $this->data()['total']);
    }

    public function test_un_bon_cloture_ou_annule_n_attend_plus_rien(): void
    {
        $this->bonAvecReliquat(10, 3, statut: BonCommande::STATUT_CLOTURE);
        $this->bonAvecReliquat(10, 0, statut: BonCommande::STATUT_ANNULE);

        $this->assertSame(0, $this->data()['total']);
    }

    public function test_un_bon_partiel_apparait_avec_son_statut(): void
    {
        $this->bonAvecReliquat(10, 6, statut: BonCommande::STATUT_PARTIEL);

        $donnees = $this->data();

        $this->assertSame(1, $donnees['total']);
        $this->assertSame(BonCommande::STATUT_PARTIEL, $donnees['rows'][0]['statut']);
    }

    // ═══ Le chiffre de gestion : engagé non livré ═══════════════════════════

    /**
     * Le total porte sur le RESTE (pas le commandé), en TTC, sur le jeu
     * FILTRÉ entier — jamais sur la page affichée.
     */
    public function test_l_engage_non_livre_compte_le_reste_en_ttc(): void
    {
        // 6 restants × 100 000 × 1,18 = 708 000
        $this->bonAvecReliquat(10, 4, prix: 100000);

        $this->assertEqualsWithDelta(708000, $this->data()['engage_non_livre_ttc'], 0.01);
    }

    public function test_le_total_decrit_le_filtre_entier_pas_la_page(): void
    {
        $fournisseur = Fournisseur::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->bonAvecReliquat(10, 0, fournisseur: $fournisseur, prix: 10000);
        }

        // Une seule ligne par page, mais le total couvre les cinq.
        $donnees = $this->data(['limit' => 1]);

        $this->assertCount(1, $donnees['rows']);
        $this->assertSame(5, $donnees['total']);
        // 5 × 10 × 10 000 × 1,18 = 590 000
        $this->assertEqualsWithDelta(590000, $donnees['engage_non_livre_ttc'], 0.01);
    }

    public function test_le_total_suit_le_filtre_fournisseur(): void
    {
        $sonabel = Fournisseur::factory()->create(['raison_sociale' => 'Sonabel-Info']);
        $autre = Fournisseur::factory()->create(['raison_sociale' => 'Autre fournisseur']);

        $this->bonAvecReliquat(10, 0, fournisseur: $sonabel, prix: 100000);
        $this->bonAvecReliquat(10, 0, fournisseur: $autre, prix: 50000);

        $donnees = $this->data(['fournisseur_id' => $sonabel->id]);

        $this->assertSame(1, $donnees['total']);
        $this->assertEqualsWithDelta(1180000, $donnees['engage_non_livre_ttc'], 0.01);
    }

    // ═══ Les pilules d'âge ══════════════════════════════════════════════════

    public function test_le_filtre_d_age_ne_retient_que_les_bons_anciens(): void
    {
        $this->bonAvecReliquat(10, 0, ageJours: 5);
        $this->bonAvecReliquat(10, 0, ageJours: 45);
        $this->bonAvecReliquat(10, 0, ageJours: 90);

        $this->assertSame(3, $this->data()['total']);
        $this->assertSame(2, $this->data(['age_min' => 30])['total']);
        $this->assertSame(1, $this->data(['age_min' => 60])['total']);
    }

    /** La couleur du badge suit le SEUIL PARAMÉTRÉ, pas une constante. */
    public function test_la_couleur_du_badge_suit_le_parametre_de_l_etablissement(): void
    {
        $this->bonAvecReliquat(10, 0, ageJours: 40);

        // Seuil à 30 j : 40 jours, c'est rouge.
        app(AchatParametres::class)->set('delai_alerte_reliquat_jours', 30);
        $this->assertSame('danger', $this->data()['rows'][0]['age_couleur']);

        // Seuil à 60 j : la vigilance commence à la moitié (30 j), donc
        // 40 jours passent en orange sans être encore alarmants.
        app(AchatParametres::class)->set('delai_alerte_reliquat_jours', 60);
        $this->assertSame('warning', $this->data()['rows'][0]['age_couleur']);

        // Seuil à 200 j : c'est vert, tout va bien.
        app(AchatParametres::class)->set('delai_alerte_reliquat_jours', 200);
        $this->assertSame('success', $this->data()['rows'][0]['age_couleur']);
    }

    public function test_l_age_se_compte_depuis_la_validation(): void
    {
        $this->bonAvecReliquat(10, 0, ageJours: 12);

        $this->assertSame(12, $this->data()['rows'][0]['age_jours']);
    }

    // ═══ Dernière réception et clôture ══════════════════════════════════════

    public function test_la_derniere_reception_est_datee(): void
    {
        [$bon, $ligne] = $this->bonAvecReliquat(10, 0);

        app(AchatReceptionService::class)->integrer(
            $bon->id,
            5150,
            [['article_id' => $ligne->article_id, 'quantite' => 3]],
            'ENT-2026-0150'
        );

        $donnees = $this->data();

        $this->assertSame(now()->format('d/m/Y'), $donnees['rows'][0]['derniere_reception']);
        $this->assertEqualsWithDelta(7, $donnees['rows'][0]['reste'], 0.001);
    }

    public function test_la_cloture_n_est_offerte_que_sur_un_bon_partiel_et_avec_le_droit(): void
    {
        $this->bonAvecReliquat(10, 0, statut: BonCommande::STATUT_VALIDE);
        $this->bonAvecReliquat(10, 6, statut: BonCommande::STATUT_PARTIEL);

        // Le gestionnaire n'a PAS la permission de clôturer.
        foreach ($this->data()['rows'] as $ligne) {
            $this->assertFalse($ligne['peut_cloturer']);
        }

        $cloturateur = $this->utilisateur([
            'achat.bons_commande.index',
            'achat.reliquats.index',
            'achat.bons_commande.cloturer',
        ], 'cloturateur@example.com');

        $lignes = collect(
            $this->actingAs($cloturateur)
                ->getJson(route('achat.reliquats.data'))
                ->assertOk()
                ->json('rows')
        );

        // Seul le PARTIEL est clôturable : un VALIDÉ sans réception s'annule.
        $this->assertTrue($lignes->firstWhere('statut', BonCommande::STATUT_PARTIEL)['peut_cloturer']);
        $this->assertFalse($lignes->firstWhere('statut', BonCommande::STATUT_VALIDE)['peut_cloturer']);
    }

    /** Après clôture, la ligne DISPARAÎT des reliquats (le bon n'attend plus). */
    public function test_la_cloture_retire_la_ligne_des_reliquats(): void
    {
        [$bon, $ligne] = $this->bonAvecReliquat(10, 0);

        app(AchatReceptionService::class)->integrer(
            $bon->id,
            5151,
            [['article_id' => $ligne->article_id, 'quantite' => 4]],
            'ENT-2026-0151'
        );

        $this->assertSame(1, $this->data()['total']);

        app(\Modules\Achat\Services\FinDeVieService::class)->cloturer(
            $bon->fresh(),
            $this->gestionnaire,
            'Le fournisseur ne livrera pas le solde.'
        );

        $this->assertSame(0, $this->data()['total']);
    }

    // ═══ Recherche et export ════════════════════════════════════════════════

    public function test_la_recherche_couvre_le_numero_l_article_et_le_fournisseur(): void
    {
        $sonabel = Fournisseur::factory()->create(['raison_sociale' => 'Sonabel-Info']);
        [$bon, $ligne] = $this->bonAvecReliquat(10, 0, fournisseur: $sonabel);
        $ligne->update(['designation' => 'Toner HP 26A']);

        $this->bonAvecReliquat(5, 0); // un autre, sans rapport

        $this->assertSame(1, $this->data(['search' => 'sonabel'])['total']);
        $this->assertSame(1, $this->data(['search' => 'toner'])['total']);
        $this->assertSame(1, $this->data(['search' => $bon->numero])['total']);
    }

    public function test_l_export_csv_suit_le_filtre_et_porte_le_total(): void
    {
        $sonabel = Fournisseur::factory()->create(['raison_sociale' => 'Sonabel-Info']);
        $this->bonAvecReliquat(10, 0, fournisseur: $sonabel, prix: 100000);
        $this->bonAvecReliquat(10, 0, prix: 50000); // autre fournisseur

        $reponse = $this->actingAs($this->gestionnaire)
            ->get(route('achat.reliquats.export', ['fournisseur_id' => $sonabel->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $contenu = $reponse->streamedContent();

        // Les filtres sont imprimés en tête : le fichier se relit hors contexte.
        $this->assertStringContainsString('Fournisseur : Sonabel-Info', $contenu);
        $this->assertStringContainsString('Sonabel-Info', $contenu);
        // Et le total du FILTRE, pas de tout : 10 × 100 000 × 1,18.
        $this->assertStringContainsString('1180000', $contenu);
    }

    // ═══ L'écran ════════════════════════════════════════════════════════════

    public function test_l_ecran_porte_les_pilules_et_le_pied(): void
    {
        $contenu = $this->actingAs($this->gestionnaire)
            ->get(route('achat.reliquats.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('filter-age', $contenu);
        $this->assertStringContainsString('pied-engage', $contenu);
        // EV-04 : l'état vide est une bonne nouvelle, il est dans la page.
        $this->assertStringContainsString('toutes les commandes validées sont soldées', $contenu);
    }

    /** La pilule du paramètre n'apparaît que si elle n'existe pas déjà. */
    public function test_la_pilule_du_parametre_porte_la_valeur_de_l_etablissement(): void
    {
        app(AchatParametres::class)->set('delai_alerte_reliquat_jours', 45);

        $this->actingAs($this->gestionnaire)
            ->get(route('achat.reliquats.index'))
            ->assertOk()
            ->assertSee('&gt; 45 j', false);
    }
}
