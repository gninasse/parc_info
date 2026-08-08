<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\AchatReceptionService;
use Modules\Achat\Services\ChronologieBonCommande;
use Modules\Achat\Services\CircuitSoumissionService;
use Modules\Achat\Services\FinDeVieService;
use Modules\Achat\Services\VisaService;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\Activity;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-08 — la fiche A-04 (SPEC_UX A-04).
 *
 * Deux critères y sont non négociables :
 *
 *   - IA-14 : la chronologie reflète EXACTEMENT le journal. On la vérifie en
 *     DÉROULANT le circuit réel (soumettre, renvoyer, valider, réceptionner)
 *     puis en comparant ce que la fiche raconte à ce que le journal contient ;
 *   - doctrine §0.3 : une action interdite par les DROITS est absente du HTML,
 *     une action bloquée par l'ÉTAT est grisée avec son diagnostic.
 */
class FicheBonCommandeTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS_ACHETEUR = [
        'achat.dashboard.view',
        'achat.bons_commande.index',
        'achat.bons_commande.store',
        'achat.bons_commande.update',
        'achat.bons_commande.destroy',
        'achat.bons_commande.soumettre',
    ];

    private const PERMISSIONS_VALIDATEUR = [
        'achat.dashboard.view',
        'achat.bons_commande.index',
        'achat.bons_commande.valider',
        'achat.bons_commande.annuler',
        'achat.bons_commande.cloturer',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);
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

    private function acheteur(): User
    {
        return $this->utilisateur(self::PERMISSIONS_ACHETEUR, 'acheteur-fiche@example.com');
    }

    private function validateur(): User
    {
        return $this->utilisateur(self::PERMISSIONS_VALIDATEUR, 'validateur-fiche@example.com');
    }

    private function brouillonComplet(?User $auteur = null): BonCommande
    {
        $bon = BonCommande::factory()->create([
            'created_by' => ($auteur ?? $this->acheteur())->id,
        ]);

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->create(['nature' => Article::NATURE_CONSOMMABLE])->id,
            'nature' => Article::NATURE_CONSOMMABLE,
            'quantite' => 10,
            'prix_unitaire_ht' => 100000,
            'taux_tva' => 18,
        ]);

        app(\Modules\Achat\Services\CalculMontantsService::class)->recalculer($bon);

        return $bon->refresh();
    }

    /** Un bon mené au statut VALIDE par le circuit réel (pas par factory). */
    private function bonValideParLeCircuit(): BonCommande
    {
        $auteur = $this->acheteur();
        $bon = $this->brouillonComplet($auteur);

        app(CircuitSoumissionService::class)->soumettre($bon, $auteur);

        return app(VisaService::class)->valider($bon->refresh(), $this->validateur());
    }

    // ═══ Accès et rendu ═════════════════════════════════════════════════════

    public function test_la_fiche_exige_la_permission_index(): void
    {
        $bon = $this->brouillonComplet();
        $sansDroit = $this->utilisateur(['achat.dashboard.view'], 'sans-droit-fiche@example.com');

        $this->actingAs($sansDroit)
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertForbidden()
            ->assertSee('achat.bons_commande.index');
    }

    public function test_la_fiche_d_un_bon_inconnu_renvoie_404(): void
    {
        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.show', 999999))
            ->assertNotFound();
    }

    public function test_le_bandeau_porte_le_numero_le_statut_et_le_montant(): void
    {
        $bon = $this->bonValideParLeCircuit();

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->assertSee($bon->numero)
            ->assertSee('Validé')
            ->assertSee(number_format((float) $bon->montant_ttc, 0, ',', ' '), false)
            ->assertSee('FCFA TTC');
    }

    public function test_la_fiche_d_un_brouillon_est_accessible_et_dit_brouillon(): void
    {
        $bon = $this->brouillonComplet();

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            // Le bandeau A-04 : « Brouillon #12 » en italique + pilule grise.
            ->assertSee("Brouillon #{$bon->id}")
            ->assertSeeInOrder(['fst-italic', 'badge bg-secondary'], false);
    }

    /** UX4-07 : l'auto-validation se lit sur la fiche après coup. */
    public function test_le_badge_auto_validation_apparait_quand_auteur_et_valideur_se_confondent(): void
    {
        $omnipotent = $this->utilisateur(
            array_merge(self::PERMISSIONS_ACHETEUR, ['achat.bons_commande.valider']),
            'omnipotent-fiche@example.com'
        );
        $bon = $this->brouillonComplet($omnipotent);
        app(CircuitSoumissionService::class)->soumettre($bon, $omnipotent);
        app(VisaService::class)->valider($bon->refresh(), $omnipotent);

        $this->actingAs($omnipotent)
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->assertSee('saisi et validé par la même personne');
    }

    public function test_le_badge_auto_validation_est_absent_quand_les_roles_sont_separes(): void
    {
        $bon = $this->bonValideParLeCircuit();

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->assertDontSee('saisi et validé par la même personne');
    }

    // ═══ Onglet Lignes ══════════════════════════════════════════════════════

    /** Avant validation, il n'y a rien à livrer : les colonnes n'existent pas. */
    public function test_les_colonnes_de_livraison_sont_masquees_avant_validation(): void
    {
        $bon = $this->brouillonComplet();

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->assertDontSee('Qté livrée')
            ->assertDontSee('Progression');
    }

    public function test_les_colonnes_de_livraison_apparaissent_sur_un_bon_engage(): void
    {
        $bon = $this->bonValideParLeCircuit();

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->assertSee('Qté livrée')
            ->assertSee('Reste')
            ->assertSee('progressbar');
    }

    // ═══ Doctrine §0.3 : la barre d'actions ═════════════════════════════════

    /** Sans le droit de valider, le bouton n'existe pas dans le HTML. */
    public function test_un_acheteur_ne_voit_pas_le_bouton_valider_sur_un_bon_soumis(): void
    {
        $auteur = $this->acheteur();
        $bon = $this->brouillonComplet($auteur);
        app(CircuitSoumissionService::class)->soumettre($bon, $auteur);

        $reponse = $this->actingAs($auteur)
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk();

        $this->assertStringNotContainsString('id="action-valider"', $reponse->getContent());
        // Mais il peut reprendre SA soumission.
        $this->assertStringContainsString('id="action-reprendre"', $reponse->getContent());
    }

    public function test_le_validateur_voit_valider_et_renvoyer_sur_un_bon_soumis(): void
    {
        $auteur = $this->acheteur();
        $bon = $this->brouillonComplet($auteur);
        app(CircuitSoumissionService::class)->soumettre($bon, $auteur);

        $contenu = $this->actingAs($this->validateur())
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="action-valider"', $contenu);
        $this->assertStringContainsString('id="action-renvoyer"', $contenu);
    }

    /** M-07 : l'annulation n'existe que sur VALIDE sans réception. */
    public function test_annuler_est_offert_au_validateur_sur_un_bon_valide_sans_reception(): void
    {
        $bon = $this->bonValideParLeCircuit();

        $contenu = $this->actingAs($this->validateur())
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="action-annuler"', $contenu);
        $this->assertStringNotContainsString('id="action-cloturer"', $contenu);
    }

    public function test_cloturer_remplace_annuler_sur_un_bon_partiel(): void
    {
        $bon = $this->bonValideParLeCircuit();
        $ligne = $bon->lignes()->first();

        app(AchatReceptionService::class)->integrer(
            $bon->id,
            4242,
            [['article_id' => $ligne->article_id, 'quantite' => 4]],
            'ENT-2026-0042'
        );

        $contenu = $this->actingAs($this->validateur())
            ->get(route('achat.bons-commande.show', $bon->fresh()->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="action-cloturer"', $contenu);
        $this->assertStringNotContainsString('id="action-annuler"', $contenu);
    }

    // ═══ IA-14 : la chronologie EST le journal ══════════════════════════════

    /**
     * Le parcours complet — création, soumission, renvoi, re-soumission,
     * validation, réception — raconté par la chronologie dans l'ordre du
     * journal, avec les motifs.
     */
    public function test_la_chronologie_raconte_le_circuit_reel_dans_l_ordre(): void
    {
        $auteur = $this->acheteur();
        $validateur = $this->validateur();
        $circuit = app(CircuitSoumissionService::class);

        $bon = $this->brouillonComplet($auteur);
        $circuit->soumettre($bon, $auteur);
        $circuit->renvoyer($bon->refresh(), $validateur, 'Prix du toner à renégocier avant visa.');
        $circuit->soumettre($bon->refresh(), $auteur);
        app(VisaService::class)->valider($bon->refresh(), $validateur);

        $ligne = $bon->lignes()->first();
        app(AchatReceptionService::class)->integrer(
            $bon->id,
            777,
            [['article_id' => $ligne->article_id, 'quantite' => 6]],
            'ENT-2026-0077'
        );

        $chronologie = app(ChronologieBonCommande::class)->pour($bon->fresh());
        $phrases = $chronologie->pluck('phrase')->all();

        $this->assertSame('Brouillon créé', $phrases[0]);
        $this->assertSame('Soumis au visa', $phrases[1]);
        $this->assertSame('Renvoyé en brouillon par le visa', $phrases[2]);
        $this->assertSame('Soumis au visa', $phrases[3]);
        $this->assertStringContainsString('Validé — numéro', $phrases[4]);
        $this->assertStringContainsString('Réception ENT-2026-0077 intégrée', $phrases[5]);

        // Le motif du renvoi est DANS la chronologie (rejets détaillés).
        $this->assertStringContainsString(
            'Prix du toner à renégocier',
            $chronologie[2]['details'] ?? ''
        );

        // Le renvoi est un rejet : il se peint en rouge (SPEC_UX A-04).
        $this->assertSame('danger', $chronologie[2]['couleur']);
    }

    /**
     * IA-14, l'angle dur : chaque élément affiché correspond à une ligne du
     * journal (module achat) — rien n'est inventé.
     */
    public function test_chaque_element_de_la_chronologie_correspond_a_une_ligne_du_journal(): void
    {
        $bon = $this->bonValideParLeCircuit();

        $chronologie = app(ChronologieBonCommande::class)->pour($bon->fresh());
        $idsJournal = Activity::query()->forModule('achat')->pluck('id');

        $this->assertNotEmpty($chronologie);

        foreach ($chronologie as $element) {
            $this->assertTrue(
                $idsJournal->contains($element['id']),
                "L'élément « {$element['phrase']} » ne correspond à aucune ligne du journal : la chronologie invente."
            );
        }
    }

    public function test_la_chronologie_de_la_fiche_affiche_les_evenements(): void
    {
        $bon = $this->bonValideParLeCircuit();

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->assertSee('Brouillon créé')
            ->assertSee('Soumis au visa')
            ->assertSee('Validé — numéro');
    }

    // ═══ Fin de vie : M-07 annuler, M-03 clôturer ═══════════════════════════

    public function test_annuler_exige_sa_permission_dediee(): void
    {
        $bon = $this->bonValideParLeCircuit();

        $this->actingAs($this->acheteur())
            ->postJson(route('achat.bons-commande.annuler', $bon->id), ['motif' => 'Commande passée en double.'])
            ->assertForbidden()
            ->assertSee('achat.bons_commande.annuler');

        $this->assertSame(BonCommande::STATUT_VALIDE, $bon->fresh()->statut);
    }

    public function test_annuler_exige_un_motif(): void
    {
        $bon = $this->bonValideParLeCircuit();

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.annuler', $bon->id), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['motif']);
    }

    public function test_annuler_un_bon_valide_le_marque_et_journalise_le_motif(): void
    {
        $bon = $this->bonValideParLeCircuit();

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.annuler', $bon->id), [
                'motif' => 'Commande passée en double avec le BC précédent.',
            ])
            ->assertOk()
            ->assertJsonPath('data.statut', BonCommande::STATUT_ANNULE);

        $bon->refresh();
        $this->assertSame(BonCommande::STATUT_ANNULE, $bon->statut);
        $this->assertSame('Commande passée en double avec le BC précédent.', $bon->motif_annulation);

        $activite = Activity::query()
            ->forModule('achat')
            ->where('description', FinDeVieService::EVENEMENT_ANNULATION)
            ->latest('id')
            ->first();

        $this->assertNotNull($activite, 'L\'annulation doit laisser une trace au journal.');
        $this->assertSame('Commande passée en double avec le BC précédent.', $activite->properties->get('motif'));
    }

    /** SFD §7.5 : un bon déjà réceptionné ne s'annule plus — 409 explicite. */
    public function test_annuler_un_bon_receptionne_est_refuse_en_409(): void
    {
        $bon = $this->bonValideParLeCircuit();
        $ligne = $bon->lignes()->first();

        app(AchatReceptionService::class)->integrer(
            $bon->id,
            555,
            [['article_id' => $ligne->article_id, 'quantite' => 2]],
            'ENT-2026-0055'
        );

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.annuler', $bon->fresh()->id), [
                'motif' => 'Tentative interdite.',
            ])
            ->assertStatus(409);

        $this->assertSame(BonCommande::STATUT_PARTIEL, $bon->fresh()->statut);
    }

    public function test_cloturer_un_bon_partiel_abandonne_le_reliquat_et_le_photographie(): void
    {
        $bon = $this->bonValideParLeCircuit();
        $ligne = $bon->lignes()->first();

        app(AchatReceptionService::class)->integrer(
            $bon->id,
            556,
            [['article_id' => $ligne->article_id, 'quantite' => 4]],
            'ENT-2026-0056'
        );

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.cloturer', $bon->fresh()->id), [
                'motif' => 'Le fournisseur ne livrera pas le solde : marché résilié.',
            ])
            ->assertOk()
            ->assertJsonPath('data.statut', BonCommande::STATUT_CLOTURE);

        $bon->refresh();
        $this->assertSame(BonCommande::STATUT_CLOTURE, $bon->statut);

        // Les quantités livrées n'ont pas bougé : la clôture n'invente rien.
        $this->assertSame(4.0, (float) $bon->lignes()->first()->quantite_livree);

        // Le reliquat abandonné est photographié AU journal (6 restants).
        $activite = Activity::query()
            ->forModule('achat')
            ->where('description', FinDeVieService::EVENEMENT_CLOTURE)
            ->latest('id')
            ->first();

        $this->assertNotNull($activite);
        $reliquat = $activite->properties->get('reliquat_abandonne');
        $this->assertSame(6.0, (float) $reliquat[0]['reste']);
    }

    public function test_cloturer_un_bon_non_partiel_est_refuse_en_409(): void
    {
        $bon = $this->bonValideParLeCircuit();

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.cloturer', $bon->id), [
                'motif' => 'Rien à clôturer ici.',
            ])
            ->assertStatus(409);

        $this->assertSame(BonCommande::STATUT_VALIDE, $bon->fresh()->statut);
    }

    public function test_l_annulation_et_la_cloture_se_racontent_dans_la_chronologie(): void
    {
        $bon = $this->bonValideParLeCircuit();

        app(FinDeVieService::class)->annuler(
            $bon,
            $this->validateur(),
            'Commande passée en double.'
        );

        $chronologie = app(ChronologieBonCommande::class)->pour($bon->fresh());

        $this->assertSame('Annulé', $chronologie->last()['phrase']);
        $this->assertStringContainsString('Commande passée en double.', $chronologie->last()['details']);
        $this->assertSame('danger', $chronologie->last()['couleur']);
    }

    // ═══ Onglet Réceptions (D-12 : intégrées + en cours, dégradation) ═══════

    public function test_l_onglet_receptions_affiche_l_etat_vide_pedagogique(): void
    {
        $bon = $this->bonValideParLeCircuit();

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->assertSee('Les réceptions se')
            ->assertSee('saisissent au magasin (module Stock)');
    }

    public function test_une_reception_integree_apparait_en_carte_avec_ses_lignes(): void
    {
        $bon = $this->bonValideParLeCircuit();
        $ligne = $bon->lignes()->first();

        app(AchatReceptionService::class)->integrer(
            $bon->id,
            4243,
            [['article_id' => $ligne->article_id, 'quantite' => 6]],
            'ENT-2026-0099'
        );

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.show', $bon->fresh()->id))
            ->assertOk()
            ->assertSee('ENT-2026-0099')
            ->assertSee('Intégrées')
            ->assertSee('reste 4', false);
    }

    public function test_un_brouillon_stock_lie_apparait_en_cours_non_integre(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('stock_entrees')) {
            $this->markTestSkipped('Module Stock absent.');
        }

        $bon = $this->bonValideParLeCircuit();

        \Modules\Stock\Models\Entree::factory()->create([
            'bon_commande_id' => $bon->id,
            'magasin_id' => \Modules\Stock\Models\Magasin::factory()->create()->id,
        ]);

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->assertSee('En cours côté magasin')
            ->assertSee('non intégré — sans effet sur les reliquats');
    }

    // ═══ Redirections vers la fiche (fin du repli « liste ») ════════════════

    public function test_editer_un_bon_valide_redirige_desormais_vers_sa_fiche(): void
    {
        $bon = $this->bonValideParLeCircuit();

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.edit', $bon->id))
            ->assertRedirect(route('achat.bons-commande.show', $bon->id));
    }
}
