<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\AchatParametres;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * A-03 — création et édition d'un brouillon (SPEC_UX A-03).
 *
 * Les deux invariants du prompt y sont démontrés explicitement :
 *
 *   IA-1 — les montants sont calculés UNE SEULE FOIS, côté serveur, et un
 *          client qui en enverrait d'autres est ignoré ;
 *   IA-2 — les valeurs copiées du Catalogue sont FIGÉES : modifier l'article
 *          après coup ne change rien au brouillon.
 */
class SaisieBrouillonTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS_SAISIE = [
        'achat.dashboard.view',
        'achat.bons_commande.index',
        'achat.bons_commande.store',
        'achat.bons_commande.update',
        'achat.bons_commande.destroy',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);
    }

    private function utilisateur(array $permissions = self::PERMISSIONS_SAISIE, string $email = 'saisie@example.com'): User
    {
        $existant = User::query()->where('email', $email)->first();

        if ($existant !== null) {
            return $existant;
        }

        $user = User::create([
            'name' => 'Test',
            'last_name' => 'Saisie',
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
     * Article de test. Par défaut un consommable : la nature « équipement »
     * exige une catégorie d'équipements du parc (règle du Catalogue), ce qui
     * n'apporte rien aux invariants vérifiés ici.
     */
    private function article(array $attributs = []): Article
    {
        return Article::factory()->create(array_merge([
            'nom' => 'Ordinateur portable Dell Latitude 3540',
            'nature' => Article::NATURE_CONSOMMABLE,
            'prix_indicatif' => 650000,
            'taux_tva' => 18,
            'est_actif' => true,
        ], $attributs));
    }

    /** Charge minimale valide d'un brouillon. */
    private function charge(array $remplacements = []): array
    {
        return array_merge([
            'fournisseur_id' => Fournisseur::factory()->create()->id,
            'date_document' => '2026-08-08',
            'lignes' => [[
                'article_id' => $this->article()->id,
                'quantite' => 10,
                'prix_unitaire_ht' => 830000,
                'taux_tva' => 18,
            ]],
        ], $remplacements);
    }

    // ── Permissions (SFD §5) ───────────────────────────────────────────────

    public function test_la_creation_exige_la_permission_store(): void
    {
        $lecteur = $this->utilisateur(['achat.dashboard.view', 'achat.bons_commande.index'], 'lecteur@example.com');

        $this->actingAs($lecteur)
            ->get(route('achat.bons-commande.create'))
            ->assertForbidden()
            ->assertSee('achat.bons_commande.store');

        $this->actingAs($lecteur)
            ->postJson(route('achat.bons-commande.store'), $this->charge())
            ->assertForbidden();
    }

    public function test_l_edition_exige_la_permission_update(): void
    {
        $sansDroit = $this->utilisateur(
            ['achat.dashboard.view', 'achat.bons_commande.index', 'achat.bons_commande.store'],
            'sansupdate@example.com'
        );
        $bon = BonCommande::factory()->create();

        $this->actingAs($sansDroit)
            ->get(route('achat.bons-commande.edit', $bon->id))
            ->assertForbidden();

        $this->actingAs($sansDroit)
            ->putJson(route('achat.bons-commande.update', $bon->id), $this->charge())
            ->assertForbidden();
    }

    public function test_la_suppression_exige_la_permission_destroy(): void
    {
        $sansDroit = $this->utilisateur(
            ['achat.dashboard.view', 'achat.bons_commande.index'],
            'sansdestroy@example.com'
        );
        $bon = BonCommande::factory()->create();

        $this->actingAs($sansDroit)
            ->deleteJson(route('achat.bons-commande.destroy', $bon->id))
            ->assertForbidden();
    }

    // ── Création ───────────────────────────────────────────────────────────

    public function test_l_ecran_de_creation_s_affiche(): void
    {
        $this->actingAs($this->utilisateur())
            ->get(route('achat.bons-commande.create'))
            ->assertOk()
            ->assertSee('Lignes &amp; montants', false)
            ->assertSee('Récapitulatif');
    }

    public function test_creer_un_brouillon_avec_ses_lignes(): void
    {
        $reponse = $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge())
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Brouillon enregistré.']);

        $bon = BonCommande::query()->latest('id')->first();

        $this->assertSame(BonCommande::STATUT_BROUILLON, $bon->statut);
        $this->assertNull($bon->numero, 'Un brouillon ne porte pas de numéro (IA-3).');
        $this->assertSame(1, $bon->lignes()->count());
        $this->assertSame($bon->id, $reponse->json('data.id'));
    }

    public function test_le_brouillon_est_attribue_a_son_createur(): void
    {
        $utilisateur = $this->utilisateur();

        $this->actingAs($utilisateur)
            ->postJson(route('achat.bons-commande.store'), $this->charge())
            ->assertOk();

        $this->assertSame($utilisateur->id, BonCommande::query()->latest('id')->first()->created_by);
    }

    // ── IA-2 : la photographie contractuelle ───────────────────────────────

    /**
     * À l'ajout, designation / nature / taux_tva sont COPIÉS du Catalogue.
     */
    public function test_les_valeurs_du_catalogue_sont_copiees_a_l_ajout(): void
    {
        $article = $this->article(['nom' => 'Toner HP 85A', 'nature' => Article::NATURE_CONSOMMABLE, 'taux_tva' => 5]);

        $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'lignes' => [['article_id' => $article->id, 'quantite' => 2, 'prix_unitaire_ht' => 45000, 'taux_tva' => 5]],
            ]))
            ->assertOk();

        $ligne = LigneCommande::query()->latest('id')->first();

        $this->assertSame('Toner HP 85A', $ligne->designation);
        $this->assertSame(Article::NATURE_CONSOMMABLE, $ligne->nature);
        $this->assertEqualsWithDelta(5, (float) $ligne->taux_tva, 0.001);
    }

    /**
     * LE test de IA-2 : après création du brouillon, on modifie l'article au
     * Catalogue. Le brouillon ne doit pas bouger — ni à la lecture, ni après
     * un nouvel enregistrement.
     */
    public function test_modifier_l_article_au_catalogue_ne_change_rien_au_brouillon(): void
    {
        $article = $this->article(['nom' => 'Désignation d\'origine', 'taux_tva' => 18]);
        $utilisateur = $this->utilisateur();

        $this->actingAs($utilisateur)
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'lignes' => [['article_id' => $article->id, 'quantite' => 10, 'prix_unitaire_ht' => 830000, 'taux_tva' => 18]],
            ]))
            ->assertOk();

        $bon = BonCommande::query()->latest('id')->first();

        // Le Catalogue évolue APRÈS l'engagement du brouillon. La nature n'y
        // est pas modifiée : elle est immuable côté Catalogue (règle C6), ce
        // qui protège déjà ce champ en amont.
        $article->update([
            'nom' => 'Désignation modifiée après coup',
            'taux_tva' => 25,
            'prix_indicatif' => 999999,
        ]);

        $ligne = $bon->lignes()->first();
        $this->assertSame('Désignation d\'origine', $ligne->designation);
        $this->assertEqualsWithDelta(18, (float) $ligne->taux_tva, 0.001);

        // Et un ré-enregistrement du brouillon ne re-synchronise pas non plus :
        // c'est là que la photographie pourrait fuir sans le vouloir.
        $this->actingAs($utilisateur)
            ->putJson(route('achat.bons-commande.update', $bon->id), [
                'fournisseur_id' => $bon->fournisseur_id,
                'date_document' => '2026-08-08',
                'lignes' => [[
                    'id' => $ligne->id,
                    'article_id' => $article->id,
                    'quantite' => 12,
                    'prix_unitaire_ht' => 830000,
                    'taux_tva' => 18,
                ]],
            ])
            ->assertOk();

        $ligne->refresh();
        $this->assertSame('Désignation d\'origine', $ligne->designation, 'La désignation figée a été réécrite : IA-2 rompu.');
        $this->assertEqualsWithDelta(12, (float) $ligne->quantite, 0.001, 'La quantité saisie doit, elle, être prise en compte.');
    }

    public function test_le_prix_est_prerempli_du_prix_indicatif_quand_il_manque(): void
    {
        $article = $this->article(['prix_indicatif' => 650000]);

        $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'lignes' => [['article_id' => $article->id, 'quantite' => 1, 'prix_unitaire_ht' => 650000, 'taux_tva' => 18]],
            ]))
            ->assertOk();

        $this->assertEqualsWithDelta(
            650000,
            (float) LigneCommande::query()->latest('id')->first()->prix_unitaire_ht,
            0.001
        );
    }

    // ── IA-1 : les montants sont calculés par le serveur ───────────────────

    public function test_les_montants_sont_calcules_par_le_serveur(): void
    {
        $article = $this->article(['taux_tva' => 18]);

        $reponse = $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'lignes' => [['article_id' => $article->id, 'quantite' => 10, 'prix_unitaire_ht' => 830000, 'taux_tva' => 18]],
            ]))
            ->assertOk();

        // 10 × 830 000 = 8 300 000 HT ; TVA 18 % = 1 494 000 ; TTC = 9 794 000
        $this->assertEqualsWithDelta(8300000, $reponse->json('data.montant_ht'), 0.01);
        $this->assertEqualsWithDelta(1494000, $reponse->json('data.montant_tva'), 0.01);
        $this->assertEqualsWithDelta(9794000, $reponse->json('data.montant_ttc'), 0.01);
    }

    /**
     * LE test de IA-1 : un client qui envoie ses propres montants ne doit pas
     * pouvoir les imposer. C'est la porte d'entrée d'une fraude simple —
     * commander pour 10 millions en déclarant 10 000.
     */
    public function test_les_montants_envoyes_par_le_client_sont_ignores(): void
    {
        $article = $this->article();

        $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'montant_ht' => 1,
                'montant_tva' => 1,
                'montant_ttc' => 3,
                'lignes' => [['article_id' => $article->id, 'quantite' => 10, 'prix_unitaire_ht' => 830000, 'taux_tva' => 18]],
            ]))
            ->assertOk();

        $bon = BonCommande::query()->latest('id')->first();

        $this->assertEqualsWithDelta(8300000, (float) $bon->montant_ht, 0.01, 'Le client a imposé son montant : IA-1 rompu.');
        $this->assertEqualsWithDelta(9794000, (float) $bon->montant_ttc, 0.01);
    }

    /**
     * Arrondi : chaque ligne est arrondie à 2 décimales AVANT sommation, et la
     * TVA est calculée par ligne puis sommée. Sommer d'abord et arrondir
     * ensuite donnerait des écarts d'un franc entre l'écran et le PDF.
     *
     * Le prix est stocké en `decimal(14,2)` : 333.335 est donc déjà arrondi à
     * 333.34 par la base avant tout calcul. C'est voulu — un prix au
     * millimètre de franc n'a pas de sens sur un bon de commande — et le test
     * vérifie la chaîne complète, arrondi de stockage compris.
     */
    public function test_l_arrondi_se_fait_ligne_a_ligne(): void
    {
        $article = $this->article();

        $reponse = $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'lignes' => [
                    ['article_id' => $article->id, 'quantite' => 3, 'prix_unitaire_ht' => 333.335, 'taux_tva' => 18],
                    ['article_id' => $article->id, 'quantite' => 3, 'prix_unitaire_ht' => 333.335, 'taux_tva' => 18],
                ],
            ]))
            ->assertOk();

        // Prix stocké : 333.34 → 3 × 333.34 = 1000.02 par ligne → 2000.04
        $this->assertEqualsWithDelta(2000.04, $reponse->json('data.montant_ht'), 0.001);
        // TVA par ligne : round(1000.02 × 0.18, 2) = 180.00 ; × 2 = 360.00
        $this->assertEqualsWithDelta(360.00, $reponse->json('data.montant_tva'), 0.001);
        $this->assertEqualsWithDelta(2360.04, $reponse->json('data.montant_ttc'), 0.001);
    }

    /**
     * La TVA est sommée APRÈS arrondi de chaque ligne. Avec 3 lignes à 33.33,
     * arrondir à la fin donnerait 17.9982 → 18.00, tandis que l'arrondi par
     * ligne donne 3 × 6.00 = 18.00. Le test fixe la règle retenue.
     */
    public function test_la_tva_est_sommee_apres_arrondi_de_chaque_ligne(): void
    {
        $article = $this->article();

        $reponse = $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'lignes' => array_fill(0, 3, [
                    'article_id' => $article->id,
                    'quantite' => 1,
                    'prix_unitaire_ht' => 33.33,
                    'taux_tva' => 18,
                ]),
            ]))
            ->assertOk();

        $this->assertEqualsWithDelta(99.99, $reponse->json('data.montant_ht'), 0.001);
        // round(33.33 × 0.18, 2) = 6.00 par ligne → 18.00
        $this->assertEqualsWithDelta(18.00, $reponse->json('data.montant_tva'), 0.001);
        $this->assertEqualsWithDelta(117.99, $reponse->json('data.montant_ttc'), 0.001);
    }

    public function test_les_taux_de_tva_mixtes_sont_traites_ligne_a_ligne(): void
    {
        $normal = $this->article(['taux_tva' => 18]);
        $reduit = $this->article(['taux_tva' => 0]);

        $reponse = $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'lignes' => [
                    ['article_id' => $normal->id, 'quantite' => 1, 'prix_unitaire_ht' => 100000, 'taux_tva' => 18],
                    ['article_id' => $reduit->id, 'quantite' => 1, 'prix_unitaire_ht' => 100000, 'taux_tva' => 0],
                ],
            ]))
            ->assertOk();

        $this->assertEqualsWithDelta(200000, $reponse->json('data.montant_ht'), 0.01);
        $this->assertEqualsWithDelta(18000, $reponse->json('data.montant_tva'), 0.01);
        $this->assertEqualsWithDelta(218000, $reponse->json('data.montant_ttc'), 0.01);
    }

    // ── Édition ────────────────────────────────────────────────────────────

    public function test_modifier_un_brouillon_ajoute_modifie_et_retire_des_lignes(): void
    {
        $utilisateur = $this->utilisateur();
        $article = $this->article();
        $autre = $this->article(['nom' => 'Second article']);

        $this->actingAs($utilisateur)
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'lignes' => [
                    ['article_id' => $article->id, 'quantite' => 1, 'prix_unitaire_ht' => 1000, 'taux_tva' => 18],
                    ['article_id' => $autre->id, 'quantite' => 1, 'prix_unitaire_ht' => 2000, 'taux_tva' => 18],
                ],
            ]))
            ->assertOk();

        $bon = BonCommande::query()->latest('id')->first();
        $conservee = $bon->lignes()->orderBy('id')->first();

        // On garde la première (quantité modifiée), on retire la seconde, on
        // en ajoute une troisième.
        $this->actingAs($utilisateur)
            ->putJson(route('achat.bons-commande.update', $bon->id), [
                'fournisseur_id' => $bon->fournisseur_id,
                'date_document' => '2026-08-08',
                'lignes' => [
                    ['id' => $conservee->id, 'article_id' => $article->id, 'quantite' => 5, 'prix_unitaire_ht' => 1000, 'taux_tva' => 18],
                    ['article_id' => $autre->id, 'quantite' => 2, 'prix_unitaire_ht' => 3000, 'taux_tva' => 18],
                ],
            ])
            ->assertOk();

        $lignes = $bon->refresh()->lignes()->orderBy('id')->get();

        $this->assertCount(2, $lignes);
        $this->assertSame($conservee->id, $lignes[0]->id, 'La ligne conservée doit garder son identité.');
        $this->assertEqualsWithDelta(5, (float) $lignes[0]->quantite, 0.001);
        // 5 × 1000 + 2 × 3000 = 11 000 HT
        $this->assertEqualsWithDelta(11000, (float) $bon->montant_ht, 0.01);
    }

    public function test_retirer_toutes_les_lignes_remet_les_montants_a_zero(): void
    {
        $utilisateur = $this->utilisateur();

        $this->actingAs($utilisateur)
            ->postJson(route('achat.bons-commande.store'), $this->charge())
            ->assertOk();

        $bon = BonCommande::query()->latest('id')->first();

        $this->actingAs($utilisateur)
            ->putJson(route('achat.bons-commande.update', $bon->id), [
                'fournisseur_id' => $bon->fournisseur_id,
                'date_document' => '2026-08-08',
                'lignes' => [],
            ])
            ->assertOk();

        $bon->refresh();
        $this->assertSame(0, $bon->lignes()->count());
        $this->assertEqualsWithDelta(0, (float) $bon->montant_ttc, 0.001);
    }

    // ── 409 : hors brouillon et conflit d'édition ──────────────────────────

    #[\PHPUnit\Framework\Attributes\DataProvider('statutsNonModifiables')]
    public function test_modifier_un_bon_hors_brouillon_renvoie_409(string $etat): void
    {
        $bon = BonCommande::factory()->{$etat}()->create();

        $this->actingAs($this->utilisateur())
            ->putJson(route('achat.bons-commande.update', $bon->id), $this->charge())
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('statutsNonModifiables')]
    public function test_supprimer_un_bon_hors_brouillon_renvoie_409(string $etat): void
    {
        $bon = BonCommande::factory()->{$etat}()->create();

        $this->actingAs($this->utilisateur())
            ->deleteJson(route('achat.bons-commande.destroy', $bon->id))
            ->assertStatus(409);

        $this->assertNotNull($bon->fresh(), 'Un bon engagé ne doit pas être supprimable.');
    }

    public static function statutsNonModifiables(): array
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

    public function test_editer_un_bon_hors_brouillon_redirige_avec_explication(): void
    {
        $bon = BonCommande::factory()->valide()->create();

        $this->actingAs($this->utilisateur())
            ->get(route('achat.bons-commande.edit', $bon->id))
            ->assertRedirect()
            ->assertSessionHas('info');
    }

    /**
     * Verrou optimiste : deux onglets ouverts sur le même brouillon. Le second
     * enregistrement doit être refusé, jamais fusionné en silence (UX3-05).
     */
    public function test_un_enregistrement_sur_une_version_perimee_renvoie_409(): void
    {
        $utilisateur = $this->utilisateur();

        $this->actingAs($utilisateur)
            ->postJson(route('achat.bons-commande.store'), $this->charge())
            ->assertOk();

        $bon = BonCommande::query()->latest('id')->first();
        $versionChargeeParLOnglet = $bon->updated_at->toIso8601String();

        // Un autre onglet enregistre entre-temps.
        $bon->forceFill(['updated_at' => now()->addMinute()])->save();

        $this->actingAs($utilisateur)
            ->putJson(route('achat.bons-commande.update', $bon->id), array_merge($this->charge(), [
                'fournisseur_id' => $bon->fournisseur_id,
                'updated_at' => $versionChargeeParLOnglet,
            ]))
            ->assertStatus(409)
            ->assertJsonPath('conflit', true);
    }

    public function test_un_enregistrement_sur_la_version_courante_passe(): void
    {
        $utilisateur = $this->utilisateur();

        $this->actingAs($utilisateur)
            ->postJson(route('achat.bons-commande.store'), $this->charge())
            ->assertOk();

        $bon = BonCommande::query()->latest('id')->first();

        $this->actingAs($utilisateur)
            ->putJson(route('achat.bons-commande.update', $bon->id), array_merge($this->charge(), [
                'fournisseur_id' => $bon->fournisseur_id,
                'updated_at' => $bon->updated_at->toIso8601String(),
            ]))
            ->assertOk();
    }

    // ── Suppression ────────────────────────────────────────────────────────

    public function test_supprimer_un_brouillon_emporte_ses_lignes(): void
    {
        $utilisateur = $this->utilisateur();

        $this->actingAs($utilisateur)
            ->postJson(route('achat.bons-commande.store'), $this->charge())
            ->assertOk();

        $bon = BonCommande::query()->latest('id')->first();

        $this->actingAs($utilisateur)
            ->deleteJson(route('achat.bons-commande.destroy', $bon->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull($bon->fresh());
        $this->assertSame(0, LigneCommande::query()->where('bon_commande_id', $bon->id)->count());
    }

    // ── 422 mappées champ par champ (SPEC_UX §15.2) ────────────────────────

    public function test_le_fournisseur_et_la_date_sont_obligatoires(): void
    {
        $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), ['lignes' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fournisseur_id', 'date_document']);
    }

    public function test_une_quantite_nulle_est_refusee_avec_le_texte_de_la_spec(): void
    {
        $reponse = $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'lignes' => [['article_id' => $this->article()->id, 'quantite' => 0, 'prix_unitaire_ht' => 1000, 'taux_tva' => 18]],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lignes.0.quantite']);

        $this->assertSame(
            'La quantité doit être supérieure à zéro.',
            $reponse->json('errors.lignes\\.0\\.quantite.0') ?? $reponse->json('errors')['lignes.0.quantite'][0]
        );
    }

    public function test_un_prix_manquant_est_refuse_au_champ_de_la_ligne(): void
    {
        $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'lignes' => [['article_id' => $this->article()->id, 'quantite' => 1, 'taux_tva' => 18]],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lignes.0.prix_unitaire_ht']);
    }

    public function test_un_article_inconnu_est_refuse_sans_creation_implicite(): void
    {
        $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'lignes' => [['article_id' => 999999, 'quantite' => 1, 'prix_unitaire_ht' => 1000, 'taux_tva' => 18]],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lignes.0.article_id']);
    }

    public function test_un_article_desactive_est_refuse(): void
    {
        $article = $this->article(['est_actif' => false]);

        $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'lignes' => [['article_id' => $article->id, 'quantite' => 1, 'prix_unitaire_ht' => 1000, 'taux_tva' => 18]],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lignes.0.article_id']);
    }

    public function test_un_fournisseur_desactive_est_refuse(): void
    {
        $fournisseur = Fournisseur::factory()->create(['est_actif' => false]);

        $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge(['fournisseur_id' => $fournisseur->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fournisseur_id']);
    }

    public function test_le_motif_autre_exige_son_texte(): void
    {
        $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge(['observation_type' => 'autre']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['observation_texte']);
    }

    public function test_un_motif_hors_parametres_est_refuse(): void
    {
        $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge(['observation_type' => 'motif_invente']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['observation_type']);
    }

    /**
     * Garde des bornes d'intérim (A15) : impossible en CHECK statique, donc
     * appliquée à la validation. Le message donne les bornes réelles.
     */
    public function test_une_regularisation_hors_bornes_d_interim_est_refusee(): void
    {
        $parametres = app(AchatParametres::class);
        $parametres->set('intermede_debut', '2026-07-27');
        $parametres->set('intermede_fin', '2026-08-31');

        $reponse = $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'est_regularisation' => true,
                'date_document' => '2026-12-01',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date_document']);

        $this->assertStringContainsString('27/07/2026', $reponse->json('errors.date_document.0'));
    }

    public function test_une_regularisation_dans_les_bornes_est_acceptee(): void
    {
        $parametres = app(AchatParametres::class);
        $parametres->set('intermede_debut', '2026-07-27');
        $parametres->set('intermede_fin', '2026-08-31');

        $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge([
                'est_regularisation' => true,
                'date_document' => '2026-08-08',
            ]))
            ->assertOk();

        $this->assertTrue((bool) BonCommande::query()->latest('id')->first()->est_regularisation);
    }

    /** Un bon ordinaire n'est pas soumis aux bornes d'intérim. */
    public function test_un_bon_ordinaire_n_est_pas_borne_par_l_interim(): void
    {
        $parametres = app(AchatParametres::class);
        $parametres->set('intermede_debut', '2026-07-27');
        $parametres->set('intermede_fin', '2026-08-31');

        $this->actingAs($this->utilisateur())
            ->postJson(route('achat.bons-commande.store'), $this->charge(['date_document' => '2026-12-01']))
            ->assertOk();
    }

    // ── PO-01 — référence de prix ──────────────────────────────────────────

    public function test_la_reference_de_prix_donne_le_dernier_paye_et_la_moyenne(): void
    {
        $article = $this->article(['prix_indicatif' => 650000]);

        foreach ([[600000, 5], [645000, 3], [700000, 1]] as [$prix, $joursAvant]) {
            $bon = BonCommande::factory()->valide()->create(['valide_le' => now()->subDays($joursAvant)]);
            LigneCommande::factory()->create([
                'bon_commande_id' => $bon->id,
                'article_id' => $article->id,
                'prix_unitaire_ht' => $prix,
                'quantite' => 1,
            ]);
        }

        $reponse = $this->actingAs($this->utilisateur())
            ->getJson(route('achat.bons-commande.reference-prix', $article->id))
            ->assertOk();

        $this->assertSame('700000.00', $reponse->json('dernier_paye.prix'));
        $this->assertSame('648333.33', $reponse->json('moyenne_3_derniers'));
        $this->assertSame('650000.00', $reponse->json('prix_indicatif'));
        // La référence NON MANIPULABLE est le dernier payé, pas l'indicatif (A14).
        $this->assertSame('700000.00', $reponse->json('reference'));
        $this->assertSame('dernier_paye', $reponse->json('origine_reference'));
    }

    /** Sans historique, la référence retombe sur le prix indicatif. */
    public function test_sans_historique_la_reference_est_le_prix_indicatif(): void
    {
        $article = $this->article(['prix_indicatif' => 650000]);

        $reponse = $this->actingAs($this->utilisateur())
            ->getJson(route('achat.bons-commande.reference-prix', $article->id))
            ->assertOk();

        $this->assertNull($reponse->json('dernier_paye'));
        $this->assertSame('650000.00', $reponse->json('reference'));
        $this->assertSame('prix_indicatif', $reponse->json('origine_reference'));
    }

    public function test_l_ecart_est_calcule_par_le_serveur_et_compare_au_seuil(): void
    {
        $article = $this->article(['prix_indicatif' => 650000]);
        app(AchatParametres::class)->set('seuil_ecart_prix_pct', 20);

        // 830 000 vs 650 000 = +27,7 % → dépasse le seuil de 20 %
        $reponse = $this->actingAs($this->utilisateur())
            ->getJson(route('achat.bons-commande.reference-prix', $article->id).'?prix=830000')
            ->assertOk();

        $this->assertEqualsWithDelta(27.7, $reponse->json('ecart.ecart_pct'), 0.1);
        $this->assertTrue($reponse->json('ecart.depasse_seuil'));
    }

    /** Payer MOINS cher que la référence n'est pas un signal de vigilance. */
    public function test_un_ecart_a_la_baisse_ne_declenche_pas_l_alerte(): void
    {
        $article = $this->article(['prix_indicatif' => 650000]);

        $reponse = $this->actingAs($this->utilisateur())
            ->getJson(route('achat.bons-commande.reference-prix', $article->id).'?prix=400000')
            ->assertOk();

        $this->assertLessThan(0, $reponse->json('ecart.ecart_pct'));
        $this->assertFalse($reponse->json('ecart.depasse_seuil'));
    }

    /** Un brouillon n'a jamais été payé : il ne fait pas référence. */
    public function test_un_brouillon_ne_compte_pas_dans_la_reference_de_prix(): void
    {
        $article = $this->article(['prix_indicatif' => 650000]);

        $brouillon = BonCommande::factory()->create();
        LigneCommande::factory()->create([
            'bon_commande_id' => $brouillon->id,
            'article_id' => $article->id,
            'prix_unitaire_ht' => 9999999,
            'quantite' => 1,
        ]);

        $reponse = $this->actingAs($this->utilisateur())
            ->getJson(route('achat.bons-commande.reference-prix', $article->id))
            ->assertOk();

        $this->assertNull($reponse->json('dernier_paye'));
        $this->assertSame(0, $reponse->json('nb_bc_references'));
    }

    public function test_la_reference_de_prix_exige_une_permission_de_saisie(): void
    {
        $lecteur = $this->utilisateur(
            ['achat.dashboard.view', 'achat.bons_commande.index'],
            'lecteurprix@example.com'
        );

        $this->actingAs($lecteur)
            ->getJson(route('achat.bons-commande.reference-prix', $this->article()->id))
            ->assertForbidden();
    }

    /**
     * Mention « réf. modifiée le … » (A14) : si le prix indicatif a bougé
     * récemment au Catalogue, l'acheteur doit le savoir en s'y référant.
     */
    public function test_une_modification_recente_du_prix_indicatif_est_signalee(): void
    {
        $article = $this->article(['prix_indicatif' => 650000]);

        $this->actingAs($this->utilisateur());
        $article->update(['prix_indicatif' => 800000]);

        $reponse = $this->actingAs($this->utilisateur())
            ->getJson(route('achat.bons-commande.reference-prix', $article->id))
            ->assertOk();

        $this->assertNotNull(
            $reponse->json('modification_recente'),
            'La modification récente du prix indicatif doit être signalée (A14).'
        );
        $this->assertSame('650000.00', $reponse->json('modification_recente.ancien'));
        $this->assertSame('800000.00', $reponse->json('modification_recente.nouveau'));
    }

    // ── Mode régularisation à l'écran (A15) ────────────────────────────────

    public function test_le_mode_regularisation_exige_la_permission_dediee(): void
    {
        // L'utilisateur peut créer un bon, mais pas régulariser : l'écran doit
        // s'ouvrir en mode ordinaire plutôt que d'afficher un mode interdit.
        $this->actingAs($this->utilisateur())
            ->get(route('achat.bons-commande.create', ['regularisation' => 1]))
            ->assertOk()
            ->assertDontSee('RÉGULARISATION —');
    }

    public function test_le_mode_regularisation_s_ouvre_avec_la_permission(): void
    {
        $acheteur = $this->utilisateur(
            [...self::PERMISSIONS_SAISIE, 'achat.bons_commande.regulariser'],
            'regularisateur@example.com'
        );

        $this->actingAs($acheteur)
            ->get(route('achat.bons-commande.create', ['regularisation' => 1]))
            ->assertOk()
            ->assertSee('RÉGULARISATION');
    }
}
