<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\CircuitSoumissionService;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\Activity;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Le circuit BROUILLON ⇄ SOUMIS (SFD §7.1).
 *
 * Le critère central, testé sous plusieurs angles : **un Acheteur seul ne peut
 * ni valider ni renvoyer**. C'est la séparation commande/visa (RGC-11), et
 * elle ne tient que si le serveur la fait respecter — l'absence du bouton à
 * l'écran ne prouve rien.
 */
class CircuitSoumissionTest extends TestCase
{
    use RefreshDatabase;

    /** Les droits de l'Acheteur : il saisit et soumet, il ne vise pas. */
    private const PERMISSIONS_ACHETEUR = [
        'achat.dashboard.view',
        'achat.bons_commande.index',
        'achat.bons_commande.store',
        'achat.bons_commande.update',
        'achat.bons_commande.destroy',
        'achat.bons_commande.soumettre',
    ];

    /** Le validateur vise, mais ne soumet pas. */
    private const PERMISSIONS_VALIDATEUR = [
        'achat.dashboard.view',
        'achat.bons_commande.index',
        'achat.bons_commande.valider',
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

    private function acheteur(string $email = 'acheteur@example.com'): User
    {
        return $this->utilisateur(self::PERMISSIONS_ACHETEUR, $email);
    }

    private function validateur(string $email = 'validateur@example.com'): User
    {
        return $this->utilisateur(self::PERMISSIONS_VALIDATEUR, $email);
    }

    /** Un brouillon complet, prêt à partir au visa. */
    private function brouillonComplet(?User $auteur = null, array $attributs = []): BonCommande
    {
        $bon = BonCommande::factory()->create(array_merge([
            'created_by' => ($auteur ?? $this->acheteur())->id,
        ], $attributs));

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

    private function bonSoumis(?User $auteur = null): BonCommande
    {
        $auteur ??= $this->acheteur();
        $bon = $this->brouillonComplet($auteur);

        return app(CircuitSoumissionService::class)->soumettre($bon, $auteur);
    }

    // ═══ Le critère central : séparation commande / visa (RGC-11) ═══════════

    /**
     * L'Acheteur a tous les droits de saisie, y compris `soumettre`. Il ne
     * doit pouvoir NI valider NI renvoyer : sans cela, il pourrait engager
     * seul la dépense qu'il a lui-même saisie.
     */
    public function test_un_acheteur_seul_ne_peut_pas_renvoyer(): void
    {
        $acheteur = $this->acheteur();
        $bon = $this->bonSoumis($acheteur);

        $this->actingAs($acheteur)
            ->postJson(route('achat.bons-commande.renvoyer', $bon->id), ['motif' => 'Prix à revoir'])
            ->assertForbidden()
            ->assertSee('achat.bons_commande.valider');

        $this->assertSame(
            BonCommande::STATUT_SOUMIS,
            $bon->fresh()->statut,
            'Le bon a changé d\'état malgré le refus : la séparation du visa est rompue.'
        );
    }

    /** Symétriquement, un validateur pur ne soumet pas. */
    public function test_un_validateur_seul_ne_peut_pas_soumettre(): void
    {
        $bon = $this->brouillonComplet();

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.soumettre', $bon->id))
            ->assertForbidden()
            ->assertSee('achat.bons_commande.soumettre');

        $this->assertSame(BonCommande::STATUT_BROUILLON, $bon->fresh()->statut);
    }

    public function test_un_lecteur_ne_peut_declencher_aucune_transition(): void
    {
        $lecteur = $this->utilisateur(
            ['achat.dashboard.view', 'achat.bons_commande.index'],
            'lecteur-circuit@example.com'
        );
        $brouillon = $this->brouillonComplet();
        $soumis = $this->bonSoumis();

        $this->actingAs($lecteur)
            ->postJson(route('achat.bons-commande.soumettre', $brouillon->id))
            ->assertForbidden();

        $this->actingAs($lecteur)
            ->postJson(route('achat.bons-commande.renvoyer', $soumis->id), ['motif' => 'Peu importe'])
            ->assertForbidden();

        $this->actingAs($lecteur)
            ->postJson(route('achat.bons-commande.reprendre', $soumis->id))
            ->assertForbidden();
    }

    /**
     * La grille d'actions de la liste ne doit pas non plus proposer le visa à
     * un acheteur : une action interdite est ABSENTE (SPEC_UX §0.3).
     */
    public function test_la_liste_n_offre_pas_le_visa_a_un_acheteur(): void
    {
        $acheteur = $this->acheteur();
        $bon = $this->bonSoumis($acheteur);

        $ligne = collect(
            $this->actingAs($acheteur)
                ->getJson(route('achat.bons-commande.data'))
                ->assertOk()
                ->json('rows')
        )->firstWhere('id', $bon->id);

        $cles = collect($ligne['actions'])->pluck('cle')->all();

        $this->assertNotContains('valider', $cles);
        $this->assertNotContains('renvoyer', $cles);
        // En revanche il peut reprendre SA soumission.
        $this->assertContains('reprendre', $cles);
    }

    public function test_la_liste_offre_le_visa_au_validateur(): void
    {
        $bon = $this->bonSoumis();

        $ligne = collect(
            $this->actingAs($this->validateur())
                ->getJson(route('achat.bons-commande.data'))
                ->assertOk()
                ->json('rows')
        )->firstWhere('id', $bon->id);

        $cles = collect($ligne['actions'])->pluck('cle')->all();

        $this->assertContains('valider', $cles);
        $this->assertContains('renvoyer', $cles);
    }

    // ═══ Matrice des transitions ═══════════════════════════════════════════

    public function test_soumettre_un_brouillon_complet_le_verrouille(): void
    {
        $acheteur = $this->acheteur();
        $bon = $this->brouillonComplet($acheteur);

        $this->actingAs($acheteur)
            ->postJson(route('achat.bons-commande.soumettre', $bon->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.statut', BonCommande::STATUT_SOUMIS);

        $bon->refresh();

        $this->assertSame(BonCommande::STATUT_SOUMIS, $bon->statut);
        $this->assertSame($acheteur->id, $bon->soumis_par);
        $this->assertNotNull($bon->soumis_le);
        // Le numéro reste pour la validation : un bon soumis n'en a pas (IA-3).
        $this->assertNull($bon->numero);
    }

    /** Un bon soumis n'est plus modifiable : c'est le sens du verrouillage. */
    public function test_un_bon_soumis_n_est_plus_modifiable(): void
    {
        $acheteur = $this->acheteur();
        $bon = $this->bonSoumis($acheteur);

        $this->actingAs($acheteur)
            ->putJson(route('achat.bons-commande.update', $bon->id), [
                'fournisseur_id' => $bon->fournisseur_id,
                'date_document' => '2026-08-08',
                'lignes' => [],
            ])
            ->assertStatus(409);

        $this->actingAs($acheteur)
            ->deleteJson(route('achat.bons-commande.destroy', $bon->id))
            ->assertStatus(409);
    }

    /**
     * Toute soumission depuis un statut autre que BROUILLON est refusée en
     * 409 : la machine à états n'a qu'une seule porte d'entrée.
     */
    #[DataProvider('statutsNonSoumettables')]
    public function test_soumettre_hors_brouillon_renvoie_409(string $etat): void
    {
        $bon = BonCommande::factory()->{$etat}()->create(['created_by' => $this->acheteur()->id]);

        $this->actingAs($this->acheteur())
            ->postJson(route('achat.bons-commande.soumettre', $bon->id))
            ->assertStatus(409);
    }

    public static function statutsNonSoumettables(): array
    {
        return [
            'soumis' => ['soumis'],
            'validé' => ['valide'],
            'partiel' => ['partiel'],
            'livré' => ['livre'],
            'clôturé' => ['cloture'],
            'annulé' => ['annule'],
        ];
    }

    /** Le renvoi n'a de sens que depuis SOUMIS. */
    #[DataProvider('statutsNonRenvoyables')]
    public function test_renvoyer_hors_soumis_renvoie_409(string $etat): void
    {
        $bon = BonCommande::factory()->{$etat}()->create();

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.renvoyer', $bon->id), ['motif' => 'Motif valable'])
            ->assertStatus(409);
    }

    public static function statutsNonRenvoyables(): array
    {
        return [
            'brouillon' => ['brouillon'],
            'validé' => ['valide'],
            'partiel' => ['partiel'],
            'livré' => ['livre'],
            'clôturé' => ['cloture'],
            'annulé' => ['annule'],
        ];
    }

    // ═══ Contrôles de complétude à la soumission (SFD §7.1) ════════════════

    public function test_un_brouillon_sans_ligne_ne_peut_pas_etre_soumis(): void
    {
        $acheteur = $this->acheteur();
        $bon = BonCommande::factory()->create(['created_by' => $acheteur->id]);

        $reponse = $this->actingAs($acheteur)
            ->postJson(route('achat.bons-commande.soumettre', $bon->id))
            ->assertStatus(422);

        $this->assertSame('aucune_ligne', $reponse->json('blocages.0.code'));
        $this->assertSame(BonCommande::STATUT_BROUILLON, $bon->fresh()->statut);
    }

    public function test_une_ligne_sans_prix_bloque_la_soumission(): void
    {
        $acheteur = $this->acheteur();
        $bon = $this->brouillonComplet($acheteur);
        $bon->lignes()->update(['prix_unitaire_ht' => 0]);

        $reponse = $this->actingAs($acheteur)
            ->postJson(route('achat.bons-commande.soumettre', $bon->id))
            ->assertStatus(422);

        $this->assertSame('prix_nul', $reponse->json('blocages.0.code'));
    }

    /**
     * Garde C11 — licence sans logiciel rattaché.
     *
     * ⚠ Ce contrôle est aujourd'hui REDONDANT : le Catalogue garantit déjà par
     * un CHECK en base qu'un article de nature « licence » porte un
     * `logiciel_id`, et la nature d'un article est immuable (règle C6). Le cas
     * est donc inatteignable par les chemins applicatifs normaux — vérifié :
     * une mise à NULL directe est rejetée par PostgreSQL.
     *
     * Le SFD §2.2 l'exige néanmoins « à la saisie ET à l'ouverture du wizard »,
     * et il vaut d'être gardé en défense de profondeur : la nature
     * `prestation` (PRQ-02) ou une reprise de données pourraient rouvrir la
     * brèche. Il est donc testé au niveau du SERVICE, sur une ligne
     * volontairement forgée, faute de pouvoir l'atteindre par HTTP.
     */
    public function test_une_licence_sans_logiciel_bloque_la_soumission(): void
    {
        $bon = BonCommande::factory()->create(['created_by' => $this->acheteur()->id]);

        // Article consommable (donc sans logiciel, ce que le Catalogue admet),
        // puis ligne forgée en « licence » : l'état dégradé que la garde vise.
        $article = Article::factory()->create(['nature' => Article::NATURE_CONSOMMABLE]);

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'nature' => Article::NATURE_CONSOMMABLE,
            'quantite' => 5,
            'prix_unitaire_ht' => 8500,
        ]);

        \Illuminate\Support\Facades\DB::table('achat_lignes_commande')
            ->where('bon_commande_id', $bon->id)
            ->update(['nature' => Article::NATURE_LICENCE]);

        $diagnostic = app(\Modules\Achat\Services\ControlesSoumissionService::class)
            ->diagnostiquer($bon->refresh());

        $this->assertFalse($diagnostic['soumettable']);
        $this->assertSame('licence_sans_logiciel', $diagnostic['blocages'][0]['code']);
    }

    /** Une licence correctement rattachée passe le contrôle. */
    public function test_une_licence_avec_logiciel_passe(): void
    {
        $acheteur = $this->acheteur();
        $bon = BonCommande::factory()->create(['created_by' => $acheteur->id]);

        // La factory rattache elle-même un logiciel aux licences : c'est le
        // CHECK du Catalogue qui l'y oblige.
        $article = Article::factory()->licence()->create();

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'nature' => Article::NATURE_LICENCE,
            'quantite' => 5,
            'prix_unitaire_ht' => 8500,
            'taux_tva' => 18,
        ]);

        $this->actingAs($acheteur)
            ->postJson(route('achat.bons-commande.soumettre', $bon->id))
            ->assertOk();
    }

    /** Un écart de prix est un AVERTISSEMENT : il ne bloque jamais. */
    public function test_un_ecart_de_prix_n_empeche_pas_la_soumission(): void
    {
        $acheteur = $this->acheteur();
        $article = Article::factory()->create([
            'nature' => Article::NATURE_CONSOMMABLE,
            'prix_indicatif' => 1000,
        ]);

        $bon = BonCommande::factory()->create(['created_by' => $acheteur->id]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'nature' => Article::NATURE_CONSOMMABLE,
            'quantite' => 1,
            // 10 000 contre une référence de 1 000 : +900 %
            'prix_unitaire_ht' => 10000,
            'taux_tva' => 18,
        ]);

        $this->actingAs($acheteur)
            ->postJson(route('achat.bons-commande.soumettre', $bon->id))
            ->assertOk();
    }

    // ═══ Renvoi motivé (M-06) ══════════════════════════════════════════════

    public function test_le_validateur_renvoie_un_bon_avec_son_motif(): void
    {
        $acheteur = $this->acheteur();
        $validateur = $this->validateur();
        $bon = $this->bonSoumis($acheteur);

        $this->actingAs($validateur)
            ->postJson(route('achat.bons-commande.renvoyer', $bon->id), [
                'motif' => 'Le prix du toner dépasse le marché en cours.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $bon->refresh();

        $this->assertSame(BonCommande::STATUT_BROUILLON, $bon->statut);
        $this->assertSame('Le prix du toner dépasse le marché en cours.', $bon->renvoi_motif);
        $this->assertSame($validateur->id, $bon->renvoi_par);
        $this->assertNotNull($bon->renvoi_le);
        // La soumission est défaite : le bon repart de zéro côté visa.
        $this->assertNull($bon->soumis_par);
        $this->assertNull($bon->soumis_le);
    }

    /** Le motif est obligatoire : sans lui, l'auteur devrait deviner. */
    public function test_le_renvoi_sans_motif_est_refuse(): void
    {
        $bon = $this->bonSoumis();

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.renvoyer', $bon->id), ['motif' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['motif']);

        $this->assertSame(BonCommande::STATUT_SOUMIS, $bon->fresh()->statut);
    }

    public function test_un_motif_trop_court_est_refuse(): void
    {
        $bon = $this->bonSoumis();

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.renvoyer', $bon->id), ['motif' => 'non'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['motif']);
    }

    /** Le motif doit être lisible à la réouverture du brouillon (UX2-07). */
    public function test_le_brouillon_renvoye_affiche_le_motif_a_son_auteur(): void
    {
        $acheteur = $this->acheteur();
        $validateur = $this->validateur();
        $bon = $this->bonSoumis($acheteur);

        $this->actingAs($validateur)
            ->postJson(route('achat.bons-commande.renvoyer', $bon->id), [
                'motif' => 'Ajoutez la référence de la demande papier.',
            ])
            ->assertOk();

        $this->actingAs($acheteur)
            ->get(route('achat.bons-commande.edit', $bon->id))
            ->assertOk()
            ->assertSee('Ajoutez la référence de la demande papier.')
            ->assertSee($validateur->name);
    }

    /**
     * Une nouvelle soumission solde le renvoi : l'encart jaune ne doit pas
     * ressurgir après correction.
     */
    public function test_une_nouvelle_soumission_efface_l_encart_de_renvoi(): void
    {
        $acheteur = $this->acheteur();
        $bon = $this->bonSoumis($acheteur);

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.renvoyer', $bon->id), ['motif' => 'À corriger.'])
            ->assertOk();

        $this->actingAs($acheteur)
            ->postJson(route('achat.bons-commande.soumettre', $bon->id))
            ->assertOk();

        $bon->refresh();

        $this->assertNull($bon->renvoi_motif);
        $this->assertNull($bon->renvoi_par);
        $this->assertNull($bon->renvoi_le);
        $this->assertFalse($bon->estRenvoye());
    }

    // ═══ Reprise par l'auteur ══════════════════════════════════════════════

    public function test_l_auteur_reprend_sa_propre_soumission(): void
    {
        $acheteur = $this->acheteur();
        $bon = $this->bonSoumis($acheteur);

        $this->actingAs($acheteur)
            ->postJson(route('achat.bons-commande.reprendre', $bon->id))
            ->assertOk()
            ->assertJsonPath('data.statut', BonCommande::STATUT_BROUILLON);

        $bon->refresh();

        $this->assertSame(BonCommande::STATUT_BROUILLON, $bon->statut);
        // Reprise ≠ renvoi : aucun motif, donc aucun encart jaune.
        $this->assertNull($bon->renvoi_motif);
    }

    /**
     * Un tiers ne peut pas « reprendre » le bon d'un autre : pour le faire
     * revenir, il doit passer par le renvoi motivé, qui laisse une trace
     * nominative.
     */
    public function test_un_tiers_ne_peut_pas_reprendre_le_bon_d_un_autre(): void
    {
        $auteur = $this->acheteur();
        $autre = $this->utilisateur(self::PERMISSIONS_ACHETEUR, 'autre-acheteur@example.com');
        $bon = $this->bonSoumis($auteur);

        $this->actingAs($autre)
            ->postJson(route('achat.bons-commande.reprendre', $bon->id))
            ->assertStatus(422);

        $this->assertSame(BonCommande::STATUT_SOUMIS, $bon->fresh()->statut);
    }

    // ═══ Journal (module = achat) ══════════════════════════════════════════

    public function test_la_soumission_est_journalisee(): void
    {
        $acheteur = $this->acheteur();
        $bon = $this->brouillonComplet($acheteur);

        $this->actingAs($acheteur)
            ->postJson(route('achat.bons-commande.soumettre', $bon->id))
            ->assertOk();

        $activite = Activity::query()
            ->where('description', CircuitSoumissionService::EVENEMENT_SOUMISSION)
            ->latest('id')
            ->first();

        $this->assertNotNull($activite, 'La soumission doit laisser une trace au journal.');
        $this->assertSame('achat', $activite->module);
        $this->assertSame($bon->id, $activite->subject_id);
        $this->assertSame($acheteur->id, $activite->causer_id);
    }

    /** Le motif du renvoi doit se retrouver DANS le journal (SFD §7.1). */
    public function test_le_renvoi_est_journalise_avec_son_motif(): void
    {
        $validateur = $this->validateur();
        $bon = $this->bonSoumis();

        $this->actingAs($validateur)
            ->postJson(route('achat.bons-commande.renvoyer', $bon->id), [
                'motif' => 'Fournisseur non référencé au marché.',
            ])
            ->assertOk();

        $activite = Activity::query()
            ->where('description', CircuitSoumissionService::EVENEMENT_RENVOI)
            ->latest('id')
            ->first();

        $this->assertNotNull($activite);
        $this->assertSame('achat', $activite->module);
        $this->assertSame($validateur->id, $activite->causer_id);
        $this->assertSame(
            'Fournisseur non référencé au marché.',
            $activite->properties['motif'] ?? null
        );
    }

    public function test_la_reprise_est_journalisee(): void
    {
        $acheteur = $this->acheteur();
        $bon = $this->bonSoumis($acheteur);

        $this->actingAs($acheteur)
            ->postJson(route('achat.bons-commande.reprendre', $bon->id))
            ->assertOk();

        $activite = Activity::query()
            ->where('description', CircuitSoumissionService::EVENEMENT_REPRISE)
            ->latest('id')
            ->first();

        $this->assertNotNull($activite);
        $this->assertSame('achat', $activite->module);
    }

    /**
     * Chronologie exacte : un aller-retour complet doit se relire dans
     * l'ordre, sans événement manquant ni inventé.
     */
    public function test_la_chronologie_d_un_aller_retour_est_exacte(): void
    {
        $acheteur = $this->acheteur();
        $validateur = $this->validateur();
        $bon = $this->brouillonComplet($acheteur);

        $this->actingAs($acheteur)->postJson(route('achat.bons-commande.soumettre', $bon->id))->assertOk();
        $this->actingAs($validateur)->postJson(route('achat.bons-commande.renvoyer', $bon->id), ['motif' => 'À corriger svp.'])->assertOk();
        $this->actingAs($acheteur)->postJson(route('achat.bons-commande.soumettre', $bon->id))->assertOk();
        $this->actingAs($acheteur)->postJson(route('achat.bons-commande.reprendre', $bon->id))->assertOk();

        $evenements = Activity::query()
            ->where('subject_id', $bon->id)
            ->whereIn('description', [
                CircuitSoumissionService::EVENEMENT_SOUMISSION,
                CircuitSoumissionService::EVENEMENT_RENVOI,
                CircuitSoumissionService::EVENEMENT_REPRISE,
            ])
            ->orderBy('id')
            ->pluck('description')
            ->all();

        $this->assertSame([
            CircuitSoumissionService::EVENEMENT_SOUMISSION,
            CircuitSoumissionService::EVENEMENT_RENVOI,
            CircuitSoumissionService::EVENEMENT_SOUMISSION,
            CircuitSoumissionService::EVENEMENT_REPRISE,
        ], $evenements);
    }

    // ═══ Étape ② — récapitulatif ═══════════════════════════════════════════

    public function test_le_recapitulatif_s_affiche_avec_les_totaux(): void
    {
        $acheteur = $this->acheteur();
        $bon = $this->brouillonComplet($acheteur);

        $this->actingAs($acheteur)
            ->get(route('achat.bons-commande.recapitulatif', $bon->id))
            ->assertOk()
            ->assertSee('Récapitulatif')
            // 10 × 100 000 = 1 000 000 HT, TTC 1 180 000
            ->assertSee('1 180 000')
            ->assertSee('FCFA TTC');
    }

    public function test_le_recapitulatif_exige_la_permission_soumettre(): void
    {
        $bon = $this->brouillonComplet();

        $this->actingAs($this->validateur())
            ->get(route('achat.bons-commande.recapitulatif', $bon->id))
            ->assertForbidden();
    }

    public function test_le_recapitulatif_affiche_le_blocage_bloquant(): void
    {
        $acheteur = $this->acheteur();
        $bon = BonCommande::factory()->create(['created_by' => $acheteur->id]);

        $this->actingAs($acheteur)
            ->get(route('achat.bons-commande.recapitulatif', $bon->id))
            ->assertOk()
            ->assertSee('Ajoutez au moins une ligne avant de soumettre.');
    }

    public function test_le_recapitulatif_d_un_bon_soumis_redirige(): void
    {
        $bon = $this->bonSoumis();

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.recapitulatif', $bon->id))
            ->assertRedirect();
    }

    // ═══ Badge « à valider » ═══════════════════════════════════════════════

    public function test_le_badge_a_valider_compte_les_bons_soumis_pour_le_validateur(): void
    {
        $this->bonSoumis();
        $this->bonSoumis($this->utilisateur(self::PERMISSIONS_ACHETEUR, 'acheteur2@example.com'));

        $this->actingAs($this->validateur())
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->assertSee('bon(s) à valider');
    }

    /** Le badge ne dit rien d'actionnable à qui n'a pas le visa : il disparaît. */
    public function test_le_badge_est_absent_sans_la_permission_de_visa(): void
    {
        $this->bonSoumis();

        $this->actingAs($this->acheteur())
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->assertDontSee('bon(s) à valider');
    }

    public function test_le_badge_disparait_quand_il_n_y_a_rien_a_viser(): void
    {
        $this->brouillonComplet();

        $this->actingAs($this->validateur())
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->assertDontSee('bon(s) à valider');
    }
}
