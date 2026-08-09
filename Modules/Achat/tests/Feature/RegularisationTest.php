<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Exceptions\RegularisationException;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\Parametre;
use Modules\Achat\Models\RegularisationRattachement;
use Modules\Achat\Services\AchatParametres;
use Modules\Achat\Services\ChronologieBonCommande;
use Modules\Achat\Services\RegularisationService;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Core\Models\Activity;
use Modules\Core\Models\User;
use Modules\Stock\Database\Factories\ParcInfoDeTest;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-15 — la régularisation de l'intérim (A15, M-09).
 *
 * IA-11, en quatre points : les BORNES de dates, le VISA obligatoire,
 * l'UNICITÉ du rattachement, et surtout l'EXTINCTION automatique — sans
 * quoi un dispositif exceptionnel devient une pratique ordinaire.
 */
class RegularisationTest extends TestCase
{
    use RefreshDatabase;

    private User $regularisateur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        $this->regularisateur = $this->utilisateur([
            'achat.bons_commande.index',
            'achat.bons_commande.store',
            'achat.bons_commande.regulariser',
        ], 'regularisateur@example.com');
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

    private function service(): RegularisationService
    {
        return app(RegularisationService::class);
    }

    private function bonRegularisation(string $statut = BonCommande::STATUT_VALIDE): BonCommande
    {
        $factory = BonCommande::factory()->regularisation();

        if ($statut === BonCommande::STATUT_VALIDE) {
            $factory = $factory->valide();
        }

        return $factory->create(['est_regularisation' => true]);
    }

    // ═══ Les bornes de dates (la porte est encadrée) ════════════════════════

    public function test_une_date_hors_interim_est_refusee_a_la_saisie(): void
    {
        $parametres = app(AchatParametres::class);
        $parametres->set(Parametre::INTERMEDE_DEBUT, '2026-01-01');
        $parametres->set(Parametre::INTERMEDE_FIN, '2026-06-30');

        $reponse = $this->actingAs($this->regularisateur)
            ->postJson(route('achat.bons-commande.store'), [
                'fournisseur_id' => \Modules\Catalogue\Models\Fournisseur::factory()->create()->id,
                'date_document' => '2026-08-15', // hors bornes
                'est_regularisation' => true,
                'lignes' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_document']);

        $this->assertStringContainsString(
            '01/01/2026',
            $reponse->json('errors')['date_document'][0]
        );
    }

    public function test_une_date_dans_l_interim_est_acceptee(): void
    {
        $parametres = app(AchatParametres::class);
        $parametres->set(Parametre::INTERMEDE_DEBUT, '2026-01-01');
        $parametres->set(Parametre::INTERMEDE_FIN, '2026-06-30');

        $this->actingAs($this->regularisateur)
            ->postJson(route('achat.bons-commande.store'), [
                'fournisseur_id' => \Modules\Catalogue\Models\Fournisseur::factory()->create()->id,
                'date_document' => '2026-03-15',
                'est_regularisation' => true,
                'lignes' => [],
            ])
            ->assertOk();

        $this->assertTrue(BonCommande::query()->latest('id')->first()->est_regularisation);
    }

    // ═══ La dette : qui est candidat, qui ne l'est pas ══════════════════════

    public function test_un_equipement_sans_origine_est_candidat(): void
    {
        ParcInfoDeTest::equipement();

        $this->assertSame(1, $this->service()->detteRestante());
        $this->assertCount(1, $this->service()->equipementsCandidats());
    }

    /** Un équipement déjà traçable par la chaîne Stock n'est PAS une dette. */
    public function test_un_equipement_venu_d_un_bon_de_commande_n_est_pas_candidat(): void
    {
        $equipement = ParcInfoDeTest::equipement();
        $bon = BonCommande::factory()->valide()->create();

        $entree = \Modules\Stock\Models\Entree::factory()->create([
            'magasin_id' => \Modules\Stock\Models\Magasin::factory()->create()->id,
            'bon_commande_id' => $bon->id,
        ]);

        \Modules\Stock\Models\Mouvement::create([
            'entree_id' => $entree->id,
            'magasin_id' => $entree->magasin_id,
            'equipement_id' => $equipement->id,
            'type' => \Modules\Stock\Models\Mouvement::TYPE_ENTREE,
            'sens' => 1,
            'quantite' => 1,
        ]);

        $this->assertSame(0, $this->service()->detteRestante());
    }

    public function test_un_equipement_deja_rattache_n_est_plus_candidat(): void
    {
        $equipement = ParcInfoDeTest::equipement();
        $bon = $this->bonRegularisation();

        $this->service()->rattacher($bon, [$equipement->id], $this->regularisateur);

        $this->assertSame(0, $this->service()->detteRestante());
        $this->assertCount(0, $this->service()->equipementsCandidats());
    }

    public function test_la_recherche_filtre_les_candidats(): void
    {
        ParcInfoDeTest::equipement(['code_inventaire' => 'INV-ALPHA', 'numero_serie' => 'SN-A']);
        ParcInfoDeTest::equipement(['code_inventaire' => 'INV-BETA', 'numero_serie' => 'SN-B']);

        $this->assertCount(1, $this->service()->equipementsCandidats('alpha'));
        $this->assertCount(2, $this->service()->equipementsCandidats());
    }

    // ═══ M-09 : le rattachement et ses gardes (IA-11) ═══════════════════════

    public function test_le_rattachement_exige_sa_permission(): void
    {
        $bon = $this->bonRegularisation();
        $equipement = ParcInfoDeTest::equipement();
        $sansDroit = $this->utilisateur(['achat.bons_commande.index'], 'sans-regul@example.com');

        $this->actingAs($sansDroit)
            ->postJson(route('achat.regularisation.rattacher', $bon->id), ['equipements' => [$equipement->id]])
            ->assertForbidden()
            ->assertSee('achat.bons_commande.regulariser');
    }

    public function test_le_rattachement_cree_le_lien_et_decremente_la_dette(): void
    {
        $bon = $this->bonRegularisation();
        $a = ParcInfoDeTest::equipement();
        $b = ParcInfoDeTest::equipement();
        ParcInfoDeTest::equipement(); // reste en dette

        $reponse = $this->actingAs($this->regularisateur)
            ->postJson(route('achat.regularisation.rattacher', $bon->id), [
                'equipements' => [$a->id, $b->id],
            ])
            ->assertOk()
            ->json();

        $this->assertSame(2, $reponse['data']['rattaches']);
        $this->assertSame(1, $reponse['data']['dette_restante']);
        $this->assertFalse($reponse['data']['eteinte']);
        $this->assertSame(2, RegularisationRattachement::query()->where('bon_commande_id', $bon->id)->count());
    }

    /** IA-11 — un équipement n'a qu'UNE commande d'origine. */
    public function test_un_equipement_ne_se_rattache_pas_deux_fois(): void
    {
        $equipement = ParcInfoDeTest::equipement();
        $premier = $this->bonRegularisation();
        $second = $this->bonRegularisation();

        $this->service()->rattacher($premier, [$equipement->id], $this->regularisateur);

        try {
            $this->service()->rattacher($second, [$equipement->id], $this->regularisateur);
            $this->fail('Un équipement ne doit pas avoir deux commandes d\'origine.');
        } catch (RegularisationException $e) {
            $this->assertStringContainsString($equipement->code_inventaire, $e->getMessage());
            $this->assertStringContainsString($premier->numero, $e->getMessage());
        }

        $this->assertSame(1, RegularisationRattachement::query()->count());
    }

    /** Le VISA est obligatoire : on ne documente pas avec un brouillon. */
    public function test_un_bon_non_valide_ne_recoit_pas_de_rattachement(): void
    {
        $brouillon = $this->bonRegularisation(BonCommande::STATUT_BROUILLON);
        $equipement = ParcInfoDeTest::equipement();

        $this->expectException(RegularisationException::class);
        $this->expectExceptionMessageMatches('/doit être validé/');

        $this->service()->rattacher($brouillon, [$equipement->id], $this->regularisateur);
    }

    public function test_un_bon_ordinaire_ne_recoit_pas_de_rattachement(): void
    {
        $ordinaire = BonCommande::factory()->valide()->create(['est_regularisation' => false]);
        $equipement = ParcInfoDeTest::equipement();

        $this->expectException(RegularisationException::class);
        $this->expectExceptionMessageMatches("/n'est pas un bon de régularisation/");

        $this->service()->rattacher($ordinaire, [$equipement->id], $this->regularisateur);
    }

    public function test_le_rattachement_est_raconte_dans_la_chronologie(): void
    {
        $bon = $this->bonRegularisation();
        $equipement = ParcInfoDeTest::equipement();

        $this->service()->rattacher($bon, [$equipement->id], $this->regularisateur);

        $chronologie = app(ChronologieBonCommande::class)->pour($bon->fresh());
        $derniere = $chronologie->last();

        $this->assertStringContainsString('rattaché(s)', $derniere['phrase']);
        $this->assertStringContainsString($equipement->code_inventaire, $derniere['details']);
    }

    public function test_le_detachement_remet_l_equipement_en_dette(): void
    {
        $bon = $this->bonRegularisation();
        $equipement = ParcInfoDeTest::equipement();

        $this->service()->rattacher($bon, [$equipement->id], $this->regularisateur);
        $this->assertSame(0, $this->service()->detteRestante());

        $dette = $this->service()->detacher($bon, $equipement->id, $this->regularisateur);

        $this->assertSame(1, $dette);
        $this->assertSame(0, RegularisationRattachement::query()->count());
    }

    // ═══ L'EXTINCTION automatique : le cœur du dispositif (A15) ═════════════

    public function test_la_porte_s_eteint_quand_la_dette_tombe_a_zero(): void
    {
        app(AchatParametres::class)->set(Parametre::REGULARISATION_ACTIVE, true);

        $bon = $this->bonRegularisation();
        $equipement = ParcInfoDeTest::equipement();

        $reponse = $this->actingAs($this->regularisateur)
            ->postJson(route('achat.regularisation.rattacher', $bon->id), [
                'equipements' => [$equipement->id],
            ])
            ->assertOk()
            ->json();

        $this->assertTrue($reponse['data']['eteinte']);
        $this->assertSame(0, $reponse['data']['dette_restante']);
        // La porte est refermée : le paramètre a basculé tout seul.
        $this->assertFalse(app(AchatParametres::class)->regularisationActive());
        // Et le message le DIT (c'est un événement, pas un détail).
        $this->assertStringContainsString('la dette de l\'intérim est soldée', $reponse['message']);
    }

    public function test_l_extinction_est_journalisee(): void
    {
        app(AchatParametres::class)->set(Parametre::REGULARISATION_ACTIVE, true);

        $bon = $this->bonRegularisation();
        $equipement = ParcInfoDeTest::equipement();

        $this->service()->rattacher($bon, [$equipement->id], $this->regularisateur);

        $activite = Activity::query()
            ->forModule('achat')
            ->where('description', RegularisationService::EVENEMENT_EXTINCTION)
            ->first();

        $this->assertNotNull($activite, 'La fermeture de la porte doit laisser une trace.');
    }

    public function test_la_porte_ne_s_eteint_pas_s_il_reste_de_la_dette(): void
    {
        app(AchatParametres::class)->set(Parametre::REGULARISATION_ACTIVE, true);

        $bon = $this->bonRegularisation();
        $a = ParcInfoDeTest::equipement();
        ParcInfoDeTest::equipement(); // il en reste un

        $this->service()->rattacher($bon, [$a->id], $this->regularisateur);

        $this->assertTrue(app(AchatParametres::class)->regularisationActive());
    }

    // ═══ SW-06 : la réactivation, délibérée et motivée ══════════════════════

    public function test_la_reactivation_exige_la_permission_d_administration(): void
    {
        $this->actingAs($this->regularisateur)
            ->postJson(route('achat.regularisation.reactiver'), ['motif' => 'Un oubli découvert.'])
            ->assertForbidden()
            ->assertSee('achat.administration.manage');
    }

    public function test_la_reactivation_exige_un_motif(): void
    {
        $admin = $this->utilisateur(['achat.administration.manage'], 'admin-regul@example.com');

        $this->actingAs($admin)
            ->postJson(route('achat.regularisation.reactiver'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['motif']);
    }

    public function test_la_reactivation_rouvre_la_porte_et_se_trace(): void
    {
        app(AchatParametres::class)->set(Parametre::REGULARISATION_ACTIVE, false);
        $admin = $this->utilisateur(['achat.administration.manage'], 'admin-regul@example.com');

        $this->actingAs($admin)
            ->postJson(route('achat.regularisation.reactiver'), [
                'motif' => 'Trois serveurs oubliés découverts à l\'inventaire annuel.',
            ])
            ->assertOk();

        $this->assertTrue(app(AchatParametres::class)->regularisationActive());

        $activite = Activity::query()
            ->forModule('achat')
            ->where('description', RegularisationService::EVENEMENT_REACTIVATION)
            ->first();

        $this->assertNotNull($activite);
        $this->assertStringContainsString('inventaire annuel', $activite->properties->get('motif'));
    }

    // ═══ Exclusion des statistiques ═════════════════════════════════════════

    /**
     * Une régularisation est une écriture de RATTRAPAGE : la mélanger à
     * l'activité d'achat fausserait toute lecture de tendance.
     */
    public function test_les_regularisations_sont_exclues_des_statistiques(): void
    {
        BonCommande::factory()->valide()->create(['est_regularisation' => false]);
        $this->bonRegularisation();

        $this->assertSame(2, BonCommande::query()->engages()->count());
        $this->assertSame(1, BonCommande::query()->engages()->horsRegularisation()->count());
    }

    // ═══ L'écran ════════════════════════════════════════════════════════════

    public function test_la_fiche_d_un_bon_de_regularisation_offre_l_onglet_et_la_modale(): void
    {
        $bon = $this->bonRegularisation();

        $contenu = $this->actingAs($this->regularisateur)
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('onglet-rattachements', $contenu);
        $this->assertStringContainsString('modal-rattachement', $contenu);
        $this->assertStringContainsString('RÉGULARISATION', $contenu);
    }

    public function test_un_bon_ordinaire_n_a_ni_onglet_ni_modale(): void
    {
        $bon = BonCommande::factory()->valide()->create(['est_regularisation' => false]);

        $contenu = $this->actingAs($this->regularisateur)
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('onglet-rattachements', $contenu);
        $this->assertStringNotContainsString('modal-rattachement', $contenu);
    }

    public function test_l_endpoint_des_candidats_sert_la_dette(): void
    {
        ParcInfoDeTest::equipement(['code_inventaire' => 'INV-CANDIDAT']);

        $this->actingAs($this->regularisateur)
            ->getJson(route('achat.regularisation.candidats'))
            ->assertOk()
            ->assertJsonPath('dette_restante', 1)
            ->assertJsonPath('data.0.code_inventaire', 'INV-CANDIDAT');
    }

    public function test_l_etat_de_la_porte_est_lisible(): void
    {
        app(AchatParametres::class)->set(Parametre::REGULARISATION_ACTIVE, true);
        ParcInfoDeTest::equipement();

        $this->actingAs($this->regularisateur)
            ->getJson(route('achat.regularisation.etat'))
            ->assertOk()
            ->assertJsonPath('active', true)
            ->assertJsonPath('dette_restante', 1);
    }
}
