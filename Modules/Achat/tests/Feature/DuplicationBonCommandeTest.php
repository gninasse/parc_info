<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Services\ChronologieBonCommande;
use Modules\Achat\Services\DuplicationBonCommande;
use Modules\Achat\Services\LignesBonCommandeService;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-23 — dupliquer un bon de commande.
 *
 * Le besoin est quotidien : le trimestre de consommables ressemble au
 * précédent. Mais une duplication naïve serait pire que la saisie manuelle,
 * parce qu'elle produirait un bon qui A L'AIR juste.
 *
 * D'où la règle que cette suite protège, extension d'IA-2 :
 *
 *     LES VALEURS SONT RE-FIGÉES AU JOUR DE LA DUPLICATION.
 *
 * Le prix du catalogue a pu changer, le taux de TVA aussi, l'article être
 * désactivé, le fournisseur fermer. Recopier un prix de l'an dernier dans un
 * bon qu'on s'apprête à engager, c'est commander à un prix qui n'existe plus.
 */
class DuplicationBonCommandeTest extends TestCase
{
    use RefreshDatabase;

    private User $acheteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        $this->acheteur = User::create([
            'name' => 'Awa', 'last_name' => 'Acheteuse', 'user_name' => 'awa_d23',
            'email' => 'awa-d23@example.com', 'password' => bcrypt('password'),
        ]);

        foreach (['achat.bons_commande.index', 'achat.bons_commande.store'] as $permission) {
            $this->acheteur->givePermissionTo($permission);
        }
    }

    /**
     * Un bon engagé, dont une ligne a été NÉGOCIÉE sous le prix du catalogue
     * — la situation courante, et celle qui rend le re-figeage visible.
     */
    private function bonEngage(array $surchargesArticle = [], float $prixNegocie = 80000): array
    {
        $article = Article::factory()->create(array_merge([
            'nom' => 'Toner 26A',
            'prix_indicatif' => 100000,
            'taux_tva' => 18,
            'est_actif' => true,
        ], $surchargesArticle));

        $bon = BonCommande::factory()->valide()->create();

        app(LignesBonCommandeService::class)->synchroniser($bon, [
            ['article_id' => $article->id, 'quantite' => 4, 'prix_unitaire_ht' => $prixNegocie, 'taux_tva' => 18],
        ]);

        return [$bon->refresh(), $article];
    }

    private function dupliquer(BonCommande $bon)
    {
        return $this->actingAs($this->acheteur)
            ->postJson(route('achat.bons-commande.dupliquer', $bon->id));
    }

    // ── Le re-figeage (extension d'IA-2) ───────────────────────────────────

    /**
     * LE point de D-23 : le prix proposé est celui du catalogue AUJOURD'HUI,
     * pas celui négocié l'an dernier.
     */
    public function test_les_prix_sont_refiges_au_jour_de_la_duplication(): void
    {
        [$origine, $article] = $this->bonEngage(prixNegocie: 80000);

        // Le catalogue a augmenté depuis.
        $article->forceFill(['prix_indicatif' => 120000])->save();

        $reponse = $this->dupliquer($origine)->assertOk();

        $copie = BonCommande::query()->findOrFail($reponse->json('data.id'));
        $ligne = $copie->lignes()->firstOrFail();

        $this->assertEqualsWithDelta(120000, (float) $ligne->prix_unitaire_ht, 0.01);
        // La quantité, elle, se reprend : c'est le besoin qui se répète.
        $this->assertEqualsWithDelta(4, (float) $ligne->quantite, 0.01);
    }

    /** Le taux de TVA aussi : il a pu changer entre deux exercices. */
    public function test_le_taux_de_tva_est_refige(): void
    {
        [$origine, $article] = $this->bonEngage();

        $article->forceFill(['taux_tva' => 5])->save();

        $reponse = $this->dupliquer($origine)->assertOk();

        $ligne = BonCommande::query()->findOrFail($reponse->json('data.id'))->lignes()->firstOrFail();

        $this->assertEqualsWithDelta(5, (float) $ligne->taux_tva, 0.01);
    }

    /**
     * Le prix négocié n'est pas perdu : il est rendu comme point de
     * comparaison. C'est la meilleure référence de négociation de l'acheteur,
     * et il doit la voir au moment où il refait le bon.
     */
    public function test_l_ancien_prix_negocie_est_rendu_en_comparaison(): void
    {
        [$origine, $article] = $this->bonEngage(prixNegocie: 80000);
        $article->forceFill(['prix_indicatif' => 120000])->save();

        $comparaisons = $this->dupliquer($origine)->assertOk()->json('data.comparaisons');

        $this->assertCount(1, $comparaisons);
        // Comparaison numérique : un montant rond ressort du JSON en entier.
        $this->assertEqualsWithDelta(80000, $comparaisons[0]['prix_precedent'], 0.01);
        $this->assertEqualsWithDelta(120000, $comparaisons[0]['prix_propose'], 0.01);
        $this->assertEqualsWithDelta(50.0, $comparaisons[0]['ecart_pct'], 0.1);
    }

    /** Le bon d'ORIGINE n'est jamais modifié. */
    public function test_le_bon_d_origine_reste_intact(): void
    {
        [$origine, $article] = $this->bonEngage(prixNegocie: 80000);
        $article->forceFill(['prix_indicatif' => 120000])->save();

        $avant = $origine->lignes()->firstOrFail()->only(['prix_unitaire_ht', 'quantite', 'designation']);

        $this->dupliquer($origine)->assertOk();

        $this->assertSame($avant, $origine->fresh()->lignes()->firstOrFail()
            ->only(['prix_unitaire_ht', 'quantite', 'designation']));
        $this->assertSame(BonCommande::STATUT_VALIDE, $origine->fresh()->statut);
    }

    // ── Ce que la copie est, et n'est pas ──────────────────────────────────

    public function test_la_copie_est_un_brouillon_sans_numero_ni_livraison(): void
    {
        [$origine] = $this->bonEngage();

        $copie = BonCommande::query()->findOrFail($this->dupliquer($origine)->assertOk()->json('data.id'));

        $this->assertSame(BonCommande::STATUT_BROUILLON, $copie->statut);
        $this->assertNull($copie->numero, 'Un brouillon ne consomme pas de numéro.');
        $this->assertNull($copie->valide_le);
        $this->assertNull($copie->valide_par);
        // Aucune donnée de livraison n'est transportée.
        $this->assertEqualsWithDelta(0, (float) $copie->lignes()->firstOrFail()->quantite_livree, 0.01);
        // La date est celle du jour : un bon dupliqué est un nouvel engagement.
        $this->assertSame(now()->toDateString(), $copie->date_document->toDateString());
        $this->assertSame($this->acheteur->id, $copie->created_by);
    }

    // ── Les avertissements ─────────────────────────────────────────────────

    /**
     * Un article désactivé est repris (le retirer obligerait à tout
     * ressaisir) mais SIGNALÉ : c'est à l'acheteur de trancher.
     */
    public function test_un_article_desactive_est_repris_mais_signale(): void
    {
        [$origine, $article] = $this->bonEngage();

        $article->forceFill(['est_actif' => false])->save();

        $reponse = $this->dupliquer($origine)->assertOk();

        $this->assertNotEmpty($reponse->json('data.avertissements'));
        $this->assertStringContainsString('désactivé', $reponse->json('data.avertissements.0'));
        $this->assertSame(1, BonCommande::query()->findOrFail($reponse->json('data.id'))->lignes()->count());
    }

    /** Un fournisseur désactivé est signalé : le bon ne partirait nulle part. */
    public function test_un_fournisseur_desactive_est_signale(): void
    {
        $fournisseur = Fournisseur::factory()->create(['raison_sociale' => 'Ancien Fournisseur']);
        $article = Article::factory()->create(['prix_indicatif' => 50000, 'taux_tva' => 18]);

        $origine = BonCommande::factory()->valide()->create(['fournisseur_id' => $fournisseur->id]);
        app(LignesBonCommandeService::class)->synchroniser($origine, [
            ['article_id' => $article->id, 'quantite' => 2, 'prix_unitaire_ht' => 50000, 'taux_tva' => 18],
        ]);

        $fournisseur->forceFill(['est_actif' => false])->save();

        $avertissements = $this->dupliquer($origine->refresh())->assertOk()->json('data.avertissements');

        $this->assertNotEmpty($avertissements);
        $this->assertStringContainsString('Ancien Fournisseur', implode(' ', $avertissements));
    }

    /**
     * Le cas « article disparu du catalogue » n'existe pas, et ce test le
     * documente plutôt que de le traiter : `article_id` est NOT NULL et sa
     * clé étrangère est en `restrict`. Un article référencé par une ligne de
     * commande ne peut donc pas être supprimé — il se désactive.
     *
     * C'est ce constat qui a fait retirer un garde-fou du service : un code
     * mort finit par être lu comme une garantie réelle.
     */
    public function test_un_article_reference_ne_peut_pas_etre_supprime(): void
    {
        [$origine, $article] = $this->bonEngage();

        $this->expectException(\Illuminate\Database\QueryException::class);

        $article->delete();
    }

    // ── Les refus ──────────────────────────────────────────────────────────

    /**
     * Un bon ANNULÉ a été écarté pour une raison : le reproduire d'un clic
     * reviendrait à contourner cette décision.
     */
    public function test_un_bon_annule_ne_se_duplique_pas(): void
    {
        [$origine] = $this->bonEngage();
        $origine->forceFill(['statut' => BonCommande::STATUT_ANNULE])->save();

        $this->dupliquer($origine)->assertStatus(409);

        $this->assertSame(1, BonCommande::query()->count(), 'Aucune copie ne doit exister.');
    }

    public function test_un_bon_sans_ligne_ne_se_duplique_pas(): void
    {
        $vide = BonCommande::factory()->create();

        $this->dupliquer($vide)->assertStatus(422);
    }

    /** Dupliquer, c'est CRÉER : la permission de lecture ne suffit pas. */
    public function test_la_duplication_exige_la_permission_de_creation(): void
    {
        [$origine] = $this->bonEngage();

        $lecteur = User::create([
            'name' => 'Lecteur', 'last_name' => 'D23', 'user_name' => 'lecteur_d23',
            'email' => 'lecteur-d23@example.com', 'password' => bcrypt('password'),
        ]);
        $lecteur->givePermissionTo('achat.bons_commande.index');

        $this->actingAs($lecteur)
            ->postJson(route('achat.bons-commande.dupliquer', $origine->id))
            ->assertForbidden();
    }

    // ── La filiation ───────────────────────────────────────────────────────

    public function test_la_chronologie_porte_la_filiation(): void
    {
        [$origine] = $this->bonEngage();

        $copie = BonCommande::query()->findOrFail($this->dupliquer($origine)->assertOk()->json('data.id'));

        $phrases = app(ChronologieBonCommande::class)->pour($copie)->pluck('phrase');

        $this->assertTrue(
            $phrases->contains(fn ($p) => str_contains($p, 'Dupliqué depuis '.$origine->numero)),
            'La copie doit dire d\'où elle vient : '.$phrases->implode(' | ')
        );
    }

    public function test_la_trace_porte_le_module_et_l_auteur(): void
    {
        [$origine] = $this->bonEngage();

        $copie = BonCommande::query()->findOrFail($this->dupliquer($origine)->assertOk()->json('data.id'));

        $trace = \Modules\Core\Models\Activity::query()
            ->where('subject_type', BonCommande::class)
            ->where('subject_id', $copie->id)
            ->where('description', DuplicationBonCommande::EVENEMENT)
            ->firstOrFail();

        $this->assertSame('achat', $trace->module);
        $this->assertSame($this->acheteur->id, $trace->causer_id);
        $this->assertSame($origine->id, $trace->properties['origine_id']);
    }

    // ── La grille d'actions ────────────────────────────────────────────────

    /**
     * L'action est offerte sur tous les statuts sauf ANNULÉ — y compris
     * LIVRÉ et CLÔTURÉ, qui sont même les cas les plus fréquents (le
     * trimestre suivant se recommande à l'identique).
     */
    public function test_l_action_est_offerte_sur_tous_les_statuts_sauf_annule(): void
    {
        $service = app(\Modules\Achat\Services\ActionsBonCommande::class);

        /*
         * Chaque statut est construit dans un état COHÉRENT : un `CHECK`
         * (`chk_bc_numero_si_engage`) interdit un bon engagé sans numéro, et
         * réciproquement. Forcer le statut seul violerait le schéma — ce qui
         * est en soi la preuve que ces contraintes travaillent.
         */
        foreach ([
            BonCommande::STATUT_BROUILLON,
            BonCommande::STATUT_SOUMIS,
            BonCommande::STATUT_VALIDE,
            BonCommande::STATUT_PARTIEL,
            BonCommande::STATUT_LIVRE,
            BonCommande::STATUT_CLOTURE,
        ] as $statut) {
            $engage = ! in_array($statut, [BonCommande::STATUT_BROUILLON, BonCommande::STATUT_SOUMIS], true);

            [$bon] = $this->bonEngage();

            $bon->forceFill([
                'statut' => $statut,
                'numero' => $engage ? $bon->numero : null,
                'valide_le' => $engage ? now() : null,
            ])->save();

            $cles = collect($service->pour($bon->refresh(), $this->acheteur))->pluck('cle');

            $this->assertContains('dupliquer', $cles->all(), "Statut {$statut} : l'action doit être offerte.");
        }

        [$annule] = $this->bonEngage();
        $annule->forceFill(['statut' => BonCommande::STATUT_ANNULE])->save();

        $this->assertNotContains(
            'dupliquer',
            collect($service->pour($annule->refresh(), $this->acheteur))->pluck('cle')->all()
        );
    }

    public function test_le_bouton_figure_dans_la_toolbar_de_la_liste(): void
    {
        [$bon] = $this->bonEngage();

        $contenu = $this->actingAs($this->acheteur)
            ->get(route('achat.bons-commande.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('btn-dupliquer', $contenu);

        // Et le drapeau serveur suit dans la charge de la table.
        $this->actingAs($this->acheteur)
            ->getJson(route('achat.bons-commande.data'))
            ->assertOk()
            ->assertJsonPath('rows.0.peut_dupliquer', true);
    }
}
