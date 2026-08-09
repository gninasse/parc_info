<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Exceptions\ReceptionLicencesException;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Models\ReceptionLicences;
use Modules\Achat\Services\ReceptionLicencesService;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Core\Models\Activity;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\Licence;
use Modules\ParcInfo\Models\Logiciel;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-13 — réception des natures non stockables (A-05 licences, M-04 service
 * fait).
 *
 * Les trois invariants du recueil, testés frontalement :
 *
 *   - IA-7 ATOMICITÉ : une erreur à la Ne licence ⇒ ZÉRO écriture (ni
 *     licence, ni incrément, ni purge du tampon) ;
 *   - IA-8 TAMPON : persistant entre deux sessions, purgé à la finalisation
 *     ET à l'abandon ;
 *   - IA-9 GARDE : l'article sans logiciel rattaché bloque À L'OUVERTURE.
 */
class ReceptionLicencesTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionnaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        $this->receptionnaire = $this->utilisateur([
            'achat.bons_commande.index',
            'achat.licences.receptionner',
        ], 'receptionnaire@example.com');
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
     * Un logiciel du parc, avec ses dépendances obligatoires (éditeur et
     * type de licence) : la fiche ParcInfo n'accepte pas moins.
     */
    private function logiciel(): Logiciel
    {
        $suffixe = strtoupper(substr(md5(uniqid()), 0, 6));

        return Logiciel::create([
            'code' => 'LOG-'.$suffixe,
            'nom' => 'Kaspersky Endpoint',
            'type_licence_id' => \Modules\ParcInfo\Models\TypeLicence::firstOrCreate(
                ['code' => 'PERP'],
                ['libelle' => 'Perpétuelle']
            )->id,
            'editeur_id' => \Modules\ParcInfo\Models\Editeur::firstOrCreate(
                ['code' => 'KASP'],
                ['nom' => 'Kaspersky', 'est_actif' => true]
            )->id,
            'est_actif' => true,
        ]);
    }

    /** Une licence DÉJÀ dans le parc, avec ses FK obligatoires. */
    private function licenceExistante(string $cle): Licence
    {
        return Licence::create([
            'logiciel_id' => $this->logiciel()->id,
            'cle_licence' => $cle,
            'date_acquisition' => now()->toDateString(),
            'fournisseur_id' => \Modules\Catalogue\Models\Fournisseur::factory()->create()->id,
            'statut' => 'actif',
            'actif' => true,
        ]);
    }

    /**
     * Tente de dénouer le logiciel d'un article licence, pour reproduire une
     * fiche INCOMPLÈTE (donnée héritée d'une reprise).
     *
     * PostgreSQL PORTE un CHECK (chk_articles_logiciel) qui rend cet état
     * impossible : c'est la meilleure des gardes, et le test le constate au
     * lieu de le contourner. SQLite ne peut pas porter ce CHECK (recréation
     * de table interdite — piège documenté) : là, c'est le service qui doit
     * rattraper, et IA-9 se vérifie pleinement.
     */
    private function denouerLogiciel(Article $article): bool
    {
        try {
            DB::table('catalogue_articles')->where('id', $article->id)->update(['logiciel_id' => null]);
            $article->refresh();

            return true;
        } catch (\Illuminate\Database\QueryException) {
            return false; // la base a refusé : l'invariant est tenu plus bas encore
        }
    }

    /** Un BC VALIDÉ portant une ligne de 5 licences rattachées à un logiciel. */
    private function bonAvecLicences(int $quantite = 5, bool $avecLogiciel = true): array
    {
        $categorie = Categorie::firstOrCreate(['libelle' => 'Logiciels']);

        $logiciel = $avecLogiciel ? $this->logiciel() : null;

        /*
         * Une licence SANS logiciel est refusée par le modèle (C11) : pour
         * reproduire une fiche INCOMPLÈTE — exactement ce que la garde IA-9
         * doit rattraper — on crée l'article valide puis on dénoue la
         * colonne en base, comme une donnée héritée d'une reprise.
         */
        $article = Article::create([
            'nom' => 'Kaspersky Endpoint Security',
            'nature' => Article::NATURE_LICENCE,
            'categorie_id' => $categorie->id,
            'logiciel_id' => ($logiciel ?? $this->logiciel())->id,
        ]);

        if (! $avecLogiciel && ! $this->denouerLogiciel($article)) {
            $this->markTestSkipped(
                'La base porte le CHECK chk_articles_logiciel : une licence sans '
                .'logiciel est structurellement impossible (garde plus forte encore que IA-9).'
            );
        }

        $bon = BonCommande::factory()->valide()->create();

        $ligne = LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'designation' => $article->nom,
            'nature' => Article::NATURE_LICENCE,
            'quantite' => $quantite,
            'quantite_livree' => 0,
            'prix_unitaire_ht' => 8500,
        ]);

        return [$bon->refresh(), $ligne->refresh(), $article];
    }

    private function service(): ReceptionLicencesService
    {
        return app(ReceptionLicencesService::class);
    }

    /** Une session ouverte avec N clés déjà saisies. */
    private function sessionAvecCles(int $quantite, int $cles): array
    {
        [$bon, $ligne] = $this->bonAvecLicences($quantite);
        $reception = $this->service()->ouvrir($ligne, $this->receptionnaire, $quantite);

        for ($i = 1; $i <= $cles; $i++) {
            $this->service()->saisirCle($reception, sprintf('KEY-%04d', $i), '2026-01-01');
        }

        return [$bon, $ligne, $reception->refresh()];
    }

    // ═══ IA-9 — la garde d'ouverture ════════════════════════════════════════

    public function test_un_article_sans_logiciel_bloque_a_l_ouverture(): void
    {
        [, $ligne] = $this->bonAvecLicences(5, avecLogiciel: false);

        try {
            $this->service()->ouvrir($ligne, $this->receptionnaire, 5);
            $this->fail('L\'ouverture aurait dû être refusée : IA-9.');
        } catch (ReceptionLicencesException $e) {
            $this->assertStringContainsString('logiciel rattaché', $e->getMessage());
        }

        // Aucune session n'a été créée : le blocage est AVANT, pas pendant.
        $this->assertSame(0, ReceptionLicences::query()->count());
    }

    public function test_le_pre_ecran_annonce_le_blocage_avec_son_lien_de_correction(): void
    {
        [$bon, $ligne] = $this->bonAvecLicences(5, avecLogiciel: false);

        $this->actingAs($this->receptionnaire)
            ->getJson(route('achat.licences.preparer', [$bon->id, $ligne->id]))
            ->assertOk()
            ->assertJsonPath('peut_ouvrir', false)
            ->assertJsonPath('blocage.message', "Impossible de réceptionner : l'article {$ligne->designation} n'a pas de logiciel rattaché.");
    }

    public function test_le_pre_ecran_ouvre_sur_une_ligne_saine(): void
    {
        [$bon, $ligne] = $this->bonAvecLicences(5);

        $this->actingAs($this->receptionnaire)
            ->getJson(route('achat.licences.preparer', [$bon->id, $ligne->id]))
            ->assertOk()
            ->assertJsonPath('peut_ouvrir', true)
            ->assertJsonPath('ligne.reste', 5);
    }

    public function test_la_reception_exige_sa_permission(): void
    {
        [$bon, $ligne] = $this->bonAvecLicences();
        $lecteur = $this->utilisateur(['achat.bons_commande.index'], 'lecteur-licences@example.com');

        $this->actingAs($lecteur)
            ->getJson(route('achat.licences.preparer', [$bon->id, $ligne->id]))
            ->assertForbidden()
            ->assertSee('achat.licences.receptionner');
    }

    public function test_la_quantite_ouverte_ne_depasse_pas_le_reste(): void
    {
        [, $ligne] = $this->bonAvecLicences(5);

        $this->expectException(ReceptionLicencesException::class);
        $this->service()->ouvrir($ligne, $this->receptionnaire, 6);
    }

    // ═══ IA-8 — le tampon ═══════════════════════════════════════════════════

    public function test_chaque_cle_est_persistee_des_la_saisie(): void
    {
        [, , $reception] = $this->sessionAvecCles(5, 3);

        $this->assertDatabaseCount('achat_tampon_licences', 3);
        $this->assertSame(2, $reception->manquantes);
    }

    public function test_rouvrir_reprend_la_session_en_cours_sans_perdre_le_tampon(): void
    {
        [, $ligne, $reception] = $this->sessionAvecCles(5, 3);

        $reprise = $this->service()->ouvrir($ligne, $this->receptionnaire, 5);

        $this->assertSame($reception->id, $reprise->id, 'Une seconde session créerait deux tampons concurrents.');
        $this->assertSame(3, $reprise->tampon()->count());
    }

    public function test_une_cle_en_doublon_dans_la_session_est_refusee(): void
    {
        [, , $reception] = $this->sessionAvecCles(5, 2);

        $this->expectException(ReceptionLicencesException::class);
        $this->expectExceptionMessageMatches('/déjà saisie/');

        $this->service()->saisirCle($reception, 'KEY-0001');
    }

    public function test_une_cle_deja_dans_le_parc_est_refusee(): void
    {
        [, , $reception] = $this->sessionAvecCles(5, 1);

        Licence::query()->delete();
        $this->licenceExistante('DEJA-DANS-LE-PARC');

        $this->expectException(ReceptionLicencesException::class);
        $this->expectExceptionMessageMatches('/Parc Informatique/');

        $this->service()->saisirCle($reception, 'DEJA-DANS-LE-PARC');
    }

    public function test_le_tampon_ne_depasse_pas_la_quantite_annoncee(): void
    {
        [, , $reception] = $this->sessionAvecCles(2, 2);

        $this->expectException(ReceptionLicencesException::class);
        $this->service()->saisirCle($reception, 'KEY-DE-TROP');
    }

    /** Import en masse : rapport chiffré, doublons et vides ignorés. */
    public function test_l_import_en_masse_rapporte_ce_qui_a_ete_retenu(): void
    {
        [, , $reception] = $this->sessionAvecCles(5, 1); // KEY-0001 déjà saisie

        $rapport = $this->service()->importerEnMasse($reception, implode("\n", [
            'KEY-0002',
            '',
            'KEY-0001',   // doublon de session
            'KEY-0003',
            '   ',
            'KEY-0004',
            'KEY-0005',   // dépasse la quantité (5 annoncées, 4 déjà + celle-ci)
            'KEY-0006',   // hors quantité
        ]));

        $this->assertSame(4, $rapport['acceptees']);
        $this->assertSame(1, $rapport['doublons']);
        $this->assertSame(2, $rapport['vides']);
        $this->assertSame(1, $rapport['hors_quantite']);
        $this->assertSame(5, $reception->tampon()->count());
    }

    // ═══ IA-7 — l'atomicité de la finalisation ══════════════════════════════

    public function test_la_finalisation_cree_les_licences_et_solde_la_ligne(): void
    {
        [$bon, $ligne, $reception] = $this->sessionAvecCles(3, 3);

        $resultat = $this->service()->finaliser($reception, $this->receptionnaire);

        $this->assertSame(3, $resultat['licences']);
        $this->assertSame(3, Licence::query()->count());

        // Le coût est le PRIX FIGÉ de la ligne, le fournisseur celui du BC.
        $licence = Licence::query()->first();
        $this->assertSame('8500.00', $licence->cout_unitaire);
        $this->assertSame($bon->fournisseur_id, $licence->fournisseur_id);

        // Le livré a bougé et le BC est passé LIVRE (une seule ligne, soldée).
        $this->assertSame(3.0, (float) $ligne->fresh()->quantite_livree);
        $this->assertSame(BonCommande::STATUT_LIVRE, $bon->fresh()->statut);

        // Le tampon est purgé, la session FINALISEE.
        $this->assertSame(0, $reception->tampon()->count());
        $this->assertSame(ReceptionLicences::STATUT_FINALISEE, $reception->fresh()->statut);
    }

    public function test_une_reception_partielle_laisse_le_bc_partiel(): void
    {
        [$bon, $ligne] = $this->bonAvecLicences(10);
        $reception = $this->service()->ouvrir($ligne, $this->receptionnaire, 4);

        for ($i = 1; $i <= 4; $i++) {
            $this->service()->saisirCle($reception, "PARTIEL-{$i}");
        }

        $this->service()->finaliser($reception, $this->receptionnaire);

        $this->assertSame(4.0, (float) $ligne->fresh()->quantite_livree);
        $this->assertSame(BonCommande::STATUT_PARTIEL, $bon->fresh()->statut);
    }

    public function test_finaliser_un_tampon_incomplet_est_refuse(): void
    {
        [, , $reception] = $this->sessionAvecCles(5, 3);

        try {
            $this->service()->finaliser($reception, $this->receptionnaire);
            $this->fail('Un tampon incomplet ne doit pas se finaliser.');
        } catch (ReceptionLicencesException $e) {
            $this->assertStringContainsString('2 clé(s) manquante(s)', $e->getMessage());
        }

        $this->assertSame(0, Licence::query()->count());
    }

    /**
     * IA-7 — LE test d'atomicité : une clé apparaît dans le parc pendant la
     * saisie (concurrence réelle). La finalisation échoue, et AUCUNE des
     * autres licences n'existe.
     */
    public function test_une_erreur_a_la_derniere_licence_n_ecrit_rien_du_tout(): void
    {
        [$bon, $ligne, $reception] = $this->sessionAvecCles(20, 20);

        // Un tiers crée la 20e clé entre la saisie et la finalisation.
        $this->licenceExistante('KEY-0020');
        $licencesAvant = Licence::query()->count();

        try {
            $this->service()->finaliser($reception, $this->receptionnaire);
            $this->fail('La finalisation aurait dû échouer sur le doublon.');
        } catch (ReceptionLicencesException $e) {
            $this->assertStringContainsString('KEY-0020', $e->getMessage());
        }

        // ZÉRO écriture : pas 19 licences sur 20.
        $this->assertSame($licencesAvant, Licence::query()->count());
        $this->assertSame(0.0, (float) $ligne->fresh()->quantite_livree);
        $this->assertSame(BonCommande::STATUT_VALIDE, $bon->fresh()->statut);

        // Le tampon est INTACT : la saisie n'est pas perdue, elle est corrigible.
        $this->assertSame(20, $reception->tampon()->count());
        $this->assertSame(ReceptionLicences::STATUT_EN_COURS, $reception->fresh()->statut);
    }

    public function test_finaliser_deux_fois_ne_cree_pas_de_doubles(): void
    {
        [, $ligne, $reception] = $this->sessionAvecCles(3, 3);

        $this->service()->finaliser($reception, $this->receptionnaire);

        try {
            $this->service()->finaliser($reception->fresh(), $this->receptionnaire);
            $this->fail('Une session finalisée ne se rejoue pas.');
        } catch (ReceptionLicencesException $e) {
            $this->assertStringContainsString('close', $e->getMessage());
        }

        $this->assertSame(3, Licence::query()->count());
        $this->assertSame(3.0, (float) $ligne->fresh()->quantite_livree);
    }

    // ═══ SW-05 — l'abandon ══════════════════════════════════════════════════

    public function test_l_abandon_vide_le_tampon_sans_rien_creer(): void
    {
        [$bon, $ligne, $reception] = $this->sessionAvecCles(5, 3);

        $this->service()->abandonner($reception, $this->receptionnaire);

        $this->assertSame(0, $reception->tampon()->count());
        $this->assertSame(ReceptionLicences::STATUT_ABANDONNEE, $reception->fresh()->statut);
        $this->assertSame(0, Licence::query()->count());
        $this->assertSame(0.0, (float) $ligne->fresh()->quantite_livree);
        $this->assertSame(BonCommande::STATUT_VALIDE, $bon->fresh()->statut);

        // L'acte est tracé : 3 clés perdues, c'est écrit.
        $activite = Activity::query()
            ->forModule('achat')
            ->where('description', ReceptionLicencesService::EVENEMENT_ABANDON)
            ->latest('id')
            ->first();

        $this->assertNotNull($activite);
        $this->assertSame(3, $activite->properties->get('cles_perdues'));
    }

    // ═══ M-04 — le service fait (prestations) ═══════════════════════════════

    /** @return array{0: BonCommande, 1: LigneCommande} */
    private function bonAvecPrestation(): array
    {
        $categorie = Categorie::firstOrCreate(['libelle' => 'Services']);

        $article = Article::create([
            'nom' => 'Maintenance annuelle onduleurs',
            'nature' => Article::NATURE_PRESTATION,
            'categorie_id' => $categorie->id,
        ]);

        $bon = BonCommande::factory()->valide()->create();

        $ligne = LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'designation' => $article->nom,
            'nature' => Article::NATURE_PRESTATION,
            'quantite' => 1,
            'quantite_livree' => 0,
            'prix_unitaire_ht' => 1500000,
        ]);

        return [$bon->refresh(), $ligne->refresh()];
    }

    public function test_le_service_fait_solde_la_ligne_et_le_bon(): void
    {
        [$bon, $ligne] = $this->bonAvecPrestation();

        $this->actingAs($this->receptionnaire)
            ->postJson(route('achat.licences.service-fait', [$bon->id, $ligne->id]), [
                'date' => '2026-08-01',
                'commentaire' => 'Intervention réalisée, PV signé.',
            ])
            ->assertOk();

        $ligne->refresh();
        $this->assertNotNull($ligne->service_fait_le);
        $this->assertSame($this->receptionnaire->id, $ligne->service_fait_par);
        $this->assertSame('Intervention réalisée, PV signé.', $ligne->service_fait_commentaire);
        $this->assertTrue($ligne->estSoldee());
        $this->assertSame(BonCommande::STATUT_LIVRE, $bon->fresh()->statut);
    }

    public function test_le_service_fait_exige_une_date(): void
    {
        [$bon, $ligne] = $this->bonAvecPrestation();

        $this->actingAs($this->receptionnaire)
            ->postJson(route('achat.licences.service-fait', [$bon->id, $ligne->id]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date']);
    }

    public function test_le_service_fait_ne_se_constate_pas_deux_fois(): void
    {
        [$bon, $ligne] = $this->bonAvecPrestation();

        $this->actingAs($this->receptionnaire)
            ->postJson(route('achat.licences.service-fait', [$bon->id, $ligne->id]), ['date' => '2026-08-01'])
            ->assertOk();

        $this->actingAs($this->receptionnaire)
            ->postJson(route('achat.licences.service-fait', [$bon->id, $ligne->id]), ['date' => '2026-08-02'])
            ->assertUnprocessable();
    }

    public function test_le_service_fait_ne_s_applique_pas_a_une_licence(): void
    {
        [$bon, $ligne] = $this->bonAvecLicences();

        $this->actingAs($this->receptionnaire)
            ->postJson(route('achat.licences.service-fait', [$bon->id, $ligne->id]), ['date' => '2026-08-01'])
            ->assertUnprocessable();
    }

    // ═══ L'onglet Licences de la fiche A-04 ═════════════════════════════════

    public function test_l_onglet_licences_apparait_et_offre_la_reception(): void
    {
        [$bon, $ligne] = $this->bonAvecLicences(5);

        $contenu = $this->actingAs($this->receptionnaire)
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('onglet-licences', $contenu);
        $this->assertStringContainsString('btn-receptionner-licences', $contenu);
        $this->assertStringContainsString($ligne->designation, $contenu);
    }

    public function test_le_bouton_est_grise_avec_son_diagnostic_sans_logiciel(): void
    {
        [$bon] = $this->bonAvecLicences(5, avecLogiciel: false);

        $this->actingAs($this->receptionnaire)
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->assertSee('Article sans logiciel rattaché : corrigez la fiche au Catalogue.');
    }

    public function test_l_onglet_est_absent_sans_ligne_immaterielle(): void
    {
        $bon = BonCommande::factory()->valide()->create();
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->create(['nature' => Article::NATURE_CONSOMMABLE])->id,
            'nature' => Article::NATURE_CONSOMMABLE,
        ]);

        $this->actingAs($this->receptionnaire)
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->assertDontSee('onglet-licences');
    }

    public function test_le_wizard_rend_les_cles_deja_saisies(): void
    {
        [$bon, $ligne, $reception] = $this->sessionAvecCles(5, 2);

        $this->actingAs($this->receptionnaire)
            ->get(route('achat.licences.wizard', [$bon->id, $ligne->id, $reception->id]))
            ->assertOk()
            ->assertSee('KEY-0001')
            ->assertSee('KEY-0002')
            ->assertSee('Réception de licences');
    }

    /** Une session d'une AUTRE ligne n'est pas accessible : 404. */
    public function test_une_session_empruntee_est_introuvable(): void
    {
        [$bon, $ligne, $reception] = $this->sessionAvecCles(5, 1);
        [$autreBon, $autreLigne] = $this->bonAvecLicences(3);

        $this->actingAs($this->receptionnaire)
            ->get(route('achat.licences.wizard', [$autreBon->id, $autreLigne->id, $reception->id]))
            ->assertNotFound();
    }

    public function test_la_finalisation_est_racontee_dans_la_chronologie(): void
    {
        [$bon, , $reception] = $this->sessionAvecCles(2, 2);

        $this->service()->finaliser($reception, $this->receptionnaire);

        $chronologie = app(\Modules\Achat\Services\ChronologieBonCommande::class)->pour($bon->fresh());

        $this->assertTrue(
            $chronologie->contains(fn ($element) => str_contains($element['phrase'], 'Réception')),
            'La finalisation doit apparaître dans la chronologie du bon.'
        );
    }
}
