<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\DocumentsBonCommande;
use Modules\Achat\Services\ReferencePrixService;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-19 — les invariants IA-10, IA-12 et IA-15, nommément.
 *
 * Les treize autres invariants du SFD §9.4 portent déjà leur étiquette dans
 * une suite dédiée. Ces trois-là étaient couverts « en passant », par des
 * tests qui vérifiaient autre chose : le jour où l'un d'eux tombera, personne
 * ne saura qu'un invariant vient de céder. Ils ont donc leur test nommé.
 *
 *   IA-10 — séparation des rôles : la matrice 403 vaut aussi pour les routes
 *           qu'on oublie (`.data`, PDF, exports, API) ;
 *   IA-12 — la référence de prix est le dernier prix PAYÉ, non manipulable ;
 *   IA-15 — un document engagé est figé : 409 sur toute tentative d'ajout.
 */
class InvariantsComplementairesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        Storage::fake(DocumentsBonCommande::DISQUE);
    }

    private function utilisateur(array $permissions, string $email): User
    {
        $user = User::create([
            'name' => 'Utilisateur', 'last_name' => 'Test',
            'user_name' => 'u_'.substr(md5($email), 0, 12),
            'email' => $email, 'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    // ══ IA-10 — séparation des rôles ════════════════════════════════════════

    /**
     * La matrice 403 ne s'arrête pas aux écrans : les routes « discrètes »
     * (.data, PDF, exports, API) servent les mêmes données et s'oublient
     * précisément parce qu'elles ne figurent pas dans un menu.
     */
    public function test_ia10_les_routes_de_donnees_pdf_et_export_exigent_leur_permission(): void
    {
        $bon = BonCommande::factory()->valide()->create();
        $intrus = $this->utilisateur([], 'ia10-intrus@example.com');

        $routes = [
            'liste (.data)' => route('achat.bons-commande.data'),
            'fiche' => route('achat.bons-commande.show', $bon->id),
            'PDF du bon' => route('achat.bons-commande.pdf', $bon->id),
            'reliquats' => route('achat.reliquats.index'),
            'rapports' => route('achat.rapports.index'),
            'signaux' => route('achat.rapports.signaux'),
            'API bons à livrer' => route('achat.api.bons-commande.a-livrer'),
            'tableau de bord' => route('achat.dashboard'),
            'administration' => route('achat.administration'),
        ];

        foreach ($routes as $libelle => $url) {
            $this->actingAs($intrus)
                ->get($url)
                ->assertForbidden("La route « {$libelle} » doit refuser un utilisateur sans permission.");
        }
    }

    /**
     * La séparation commande / visa du SFD §5 : celui qui saisit ne valide
     * pas. C'est la raison d'être de deux permissions distinctes.
     */
    public function test_ia10_l_acheteur_ne_valide_pas_et_le_validateur_ne_saisit_pas(): void
    {
        $acheteur = $this->utilisateur([
            'achat.bons_commande.index', 'achat.bons_commande.store', 'achat.bons_commande.soumettre',
        ], 'ia10-acheteur@example.com');

        $validateur = $this->utilisateur([
            'achat.bons_commande.index', 'achat.bons_commande.valider',
        ], 'ia10-validateur@example.com');

        $bon = BonCommande::factory()->soumis()->create();

        // L'acheteur soumet mais ne vise pas.
        $this->actingAs($acheteur)
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertForbidden();

        // Le validateur vise mais ne saisit pas.
        $this->actingAs($validateur)
            ->postJson(route('achat.bons-commande.store'), [])
            ->assertForbidden();
    }

    // ══ IA-12 — la référence de prix ═══════════════════════════════════════

    /**
     * LE point de l'invariant : la référence affichée est le dernier prix
     * RÉELLEMENT PAYÉ, pas le prix indicatif du Catalogue.
     *
     * Sans cela, la pilule d'écart se désamorce toute seule : il suffit de
     * relever le prix indicatif juste avant de commander pour que l'écart
     * disparaisse. Le prix indicatif est modifiable par l'utilisateur, le
     * prix payé ne l'est pas.
     */
    public function test_ia12_la_reference_est_le_dernier_prix_paye_et_non_l_indicatif(): void
    {
        $article = Article::factory()->create(['prix_indicatif' => 900000]);

        $bon = BonCommande::factory()->valide()->create();
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'prix_unitaire_ht' => 500000,
        ]);

        $decomposition = app(ReferencePrixService::class)->pour($article->id);

        $this->assertSame('500000.00', $decomposition['reference']);
        $this->assertSame('dernier_paye', $decomposition['origine_reference']);

        // Relever le prix indicatif ne déplace PAS la référence : c'est
        // exactement la manipulation que l'invariant rend inopérante.
        $article->forceFill(['prix_indicatif' => 2000000])->save();

        $this->assertSame(
            '500000.00',
            app(ReferencePrixService::class)->pour($article->id)['reference'],
            'Le prix indicatif ne doit pas pouvoir déplacer la référence.'
        );
    }

    /** Un prix jamais validé n'a jamais été payé : il ne fait pas référence. */
    public function test_ia12_un_bon_non_engage_ne_fait_pas_reference(): void
    {
        $article = Article::factory()->create(['prix_indicatif' => 100000]);

        $brouillon = BonCommande::factory()->create(['statut' => BonCommande::STATUT_BROUILLON]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $brouillon->id,
            'article_id' => $article->id,
            'prix_unitaire_ht' => 999999,
        ]);

        $decomposition = app(ReferencePrixService::class)->pour($article->id);

        $this->assertNull($decomposition['dernier_paye']);
        $this->assertSame('prix_indicatif', $decomposition['origine_reference']);
        $this->assertSame('100000.00', $decomposition['reference']);
    }

    /**
     * L'écart n'alerte qu'à la HAUSSE : payer moins cher que la dernière fois
     * n'est pas un signal de vigilance, et le signaler apprendrait aux
     * utilisateurs à ignorer la pilule.
     */
    public function test_ia12_seul_un_ecart_a_la_hausse_declenche_l_alerte(): void
    {
        $article = Article::factory()->create(['prix_indicatif' => null]);

        $bon = BonCommande::factory()->valide()->create();
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'prix_unitaire_ht' => 100000,
        ]);

        $service = app(ReferencePrixService::class);

        $hausse = $service->ecart($article->id, 150000);
        $this->assertEqualsWithDelta(50.0, $hausse['ecart_pct'], 0.01);
        $this->assertTrue($hausse['depasse_seuil']);

        $baisse = $service->ecart($article->id, 50000);
        $this->assertEqualsWithDelta(-50.0, $baisse['ecart_pct'], 0.01);
        $this->assertFalse($baisse['depasse_seuil'], 'Une baisse de prix ne doit pas alerter.');
    }

    /** Sans référence, on n'invente pas un écart de 100 %. */
    public function test_ia12_sans_reference_aucun_ecart_n_est_pretendu(): void
    {
        $article = Article::factory()->create(['prix_indicatif' => null]);

        $ecart = app(ReferencePrixService::class)->ecart($article->id, 250000);

        $this->assertNull($ecart['ecart_pct']);
        $this->assertFalse($ecart['depasse_seuil']);
    }

    // ══ IA-15 — le dossier d'un bon engagé est figé ════════════════════════

    /**
     * Un bon validé est un document qui a pu circuler : son dossier ne
     * s'enrichit plus après coup. Le 409 dit « conflit d'état », et non
     * « vous n'avez pas le droit » — la nuance compte pour l'utilisateur,
     * qui a bien la permission.
     */
    public function test_ia15_un_bon_annule_refuse_tout_nouveau_document(): void
    {
        $auteur = $this->utilisateur(['achat.documents.store'], 'ia15-auteur@example.com');

        $bon = BonCommande::factory()->valide()->create();
        $bon->forceFill(['statut' => BonCommande::STATUT_ANNULE])->save();

        $this->actingAs($auteur)
            ->post(route('achat.bons-commande.documents.store', $bon->id), [
                'type' => array_key_first(config('achat.types_documents')),
                'fichier' => UploadedFile::fake()->create('tardif.pdf', 10, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(409);

        $this->assertSame(0, $bon->documents()->count());
    }

    /**
     * Le pendant côté Stock : un bon d'entrée validé ne reçoit plus de
     * pièces. La même règle, écrite deux fois parce qu'elle protège deux
     * dossiers distincts.
     */
    public function test_ia15_une_entree_validee_refuse_toute_nouvelle_piece(): void
    {
        $magasinier = User::create([
            'name' => 'Magasinier', 'last_name' => 'Test', 'user_name' => 'magasinier_ia15',
            'email' => 'magasinier-ia15@example.com', 'password' => bcrypt('password'),
        ]);
        $magasinier->assignRole('Magasinier');

        $entree = \Modules\Stock\Models\Entree::factory()->validee()->create([
            'magasin_id' => \Modules\Stock\Models\Magasin::factory()->create()->id,
        ]);

        $this->actingAs($magasinier)
            ->post(route('stock.documents.store', ['entrees', $entree->id]), [
                'type' => \Modules\Stock\Models\Document::TYPE_BL_FOURNISSEUR,
                'fichiers' => [UploadedFile::fake()->create('tardif.pdf', 10, 'application/pdf')],
            ], ['Accept' => 'application/json'])
            ->assertStatus(409);

        $this->assertSame(0, $entree->documents()->count());
    }

    /**
     * Un bon engagé reste modifiable dans un seul sens : on peut RETIRER une
     * pièce, à condition de dire pourquoi (pierre tombale, IA-13). Le dossier
     * est figé, la traçabilité prime.
     */
    public function test_ia15_le_gel_n_empeche_pas_le_retrait_motive(): void
    {
        $utilisateur = $this->utilisateur([
            'achat.documents.store', 'achat.documents.delete',
        ], 'ia15-retrait@example.com');

        $bon = BonCommande::factory()->create();

        $this->actingAs($utilisateur)->post(route('achat.bons-commande.documents.store', $bon->id), [
            'type' => array_key_first(config('achat.types_documents')),
            'fichier' => UploadedFile::fake()->create('avant.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();

        $document = $bon->documents()->firstOrFail();
        $bon->forceFill(['statut' => BonCommande::STATUT_VALIDE, 'numero' => 'BC-2026-IA15'])->save();

        // Sans motif : refus.
        $this->actingAs($utilisateur)
            ->deleteJson(route('achat.bons-commande.documents.destroy', [$bon->id, $document->id]))
            ->assertStatus(422);

        // Avec motif : pierre tombale, la ligne demeure.
        $this->actingAs($utilisateur)
            ->deleteJson(route('achat.bons-commande.documents.destroy', [$bon->id, $document->id]), [
                'motif' => 'Document erroné, remplacé par la version signée',
            ])
            ->assertOk();

        $this->assertTrue($document->fresh()->est_supprime);
        $this->assertNotNull($document->fresh()->motif_suppression);
    }
}
