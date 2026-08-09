<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\IntegrationReception;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\ChronologieBonCommande;
use Modules\Achat\Services\ReceptionsBonCommande;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;
use Tests\TestCase;

/**
 * BR-03 — les bordereaux de livraison consultés DEPUIS Achat.
 *
 * Le but énoncé au recueil est net : « un profil Achat sans AUCUN rôle Stock
 * consulte les bordereaux de SES commandes ». L'acheteur qui conteste une
 * facture doit sortir le BL signé sans quémander un accès magasin.
 *
 * Le risque l'est tout autant, et c'est lui que cette suite traque : une route
 * qui sert des fichiers d'un autre module devient vite une porte dérobée.
 * D'où IA-16 — « un document de réception n'est jamais accessible sans la
 * permission du module consulté » — et surtout le contrôle de RATTACHEMENT :
 * la pièce doit appartenir à une entrée liée à CE bon de commande.
 */
class DocumentsReceptionTest extends TestCase
{
    use RefreshDatabase;

    private Magasin $magasin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        $this->magasin = Magasin::factory()->create();

        Storage::fake(Document::DISQUE);
    }

    private function utilisateur(array $permissions, string $email): User
    {
        $user = User::create([
            'name' => 'Utilisateur '.$email,
            'last_name' => 'Test',
            'user_name' => 'user_'.substr(md5($email), 0, 12),
            'email' => $email,
            'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    /** Un acheteur PUR : aucune permission du module Stock. */
    private function acheteur(): User
    {
        $acheteur = $this->utilisateur([
            'achat.bons_commande.index',
            'achat.documents.view',
        ], 'acheteur-pur@example.com');

        $this->assertFalse(
            $acheteur->can('stock.entrees.index'),
            'Le scénario perd son sens si l\'acheteur possède des droits Stock.'
        );

        return $acheteur;
    }

    /**
     * Un bon VALIDÉ, sa livraison intégrée, et le BL du fournisseur numérisé
     * au magasin — la situation réelle de BR-03.
     *
     * @return array{0: BonCommande, 1: Entree, 2: Document}
     */
    private function livraisonAvecBl(): array
    {
        $article = Article::factory()->create(['nom' => 'Toner 26A']);
        $bon = BonCommande::factory()->valide()->create();

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'designation' => 'Toner 26A',
            'quantite' => 10,
            'quantite_livree' => 6,
            'prix_unitaire_ht' => 42000,
        ]);

        $entree = Entree::factory()->validee()->create([
            'magasin_id' => $this->magasin->id,
            'bon_commande_id' => $bon->id,
            'fournisseur_id' => $bon->fournisseur_id,
            'reference_externe' => 'BL-FRN-889',
        ]);

        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => $article->id,
            'quantite' => 6,
            'cout_unitaire' => 42000,
        ]);

        IntegrationReception::create([
            'bon_commande_id' => $bon->id,
            'entree_id' => $entree->id,
            'sens' => IntegrationReception::SENS_RECEPTION,
            'reference' => $entree->numero,
            'detail' => [['article_id' => $article->id, 'designation' => 'Toner 26A', 'quantite' => 6, 'reste' => 4]],
        ]);

        $bl = $this->deposerBl($entree);

        return [$bon->refresh(), $entree->refresh(), $bl];
    }

    /** Le BL numérisé au comptoir, tel que le magasinier le dépose. */
    private function deposerBl(Entree $entree, string $type = Document::TYPE_BL_FOURNISSEUR): Document
    {
        $chemin = UploadedFile::fake()
            ->create('bl-fournisseur.pdf', 20, 'application/pdf')
            ->store(Document::DOSSIER.'/entrees/'.$entree->id, Document::DISQUE);

        return $entree->documents()->create([
            'type' => $type,
            'nom_original' => 'bl-fournisseur.pdf',
            'chemin' => $chemin,
            'mime' => 'application/pdf',
            'taille' => 20480,
        ]);
    }

    // ── LE BUT : l'acheteur consulte sans droits Stock ─────────────────────

    public function test_un_acheteur_sans_aucun_droit_stock_telecharge_le_bl_de_sa_commande(): void
    {
        [$bon, $entree, $bl] = $this->livraisonAvecBl();

        $reponse = $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.receptions.documents', [$bon->id, $entree->id, $bl->id]))
            ->assertOk();

        // Le fichier arrive avec son NOM D'ORIGINE : l'acheteur reçoit
        // « bl-fournisseur.pdf », pas le nom neutre du stockage.
        $this->assertStringContainsString('bl-fournisseur.pdf', $reponse->headers->get('content-disposition'));
        $this->assertStringContainsString('application/pdf', $reponse->headers->get('content-type'));
    }

    public function test_un_acheteur_sans_aucun_droit_stock_edite_le_bordereau_de_reception(): void
    {
        [$bon, $entree] = $this->livraisonAvecBl();

        $reponse = $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.receptions.bordereau', [$bon->id, $entree->id]))
            ->assertOk();

        $this->assertStringContainsString('application/pdf', $reponse->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $reponse->getContent());
    }

    // ── IA-16 : le rattachement, verrou décisif ────────────────────────────

    public function test_ia16_une_piece_d_une_autre_commande_reste_inaccessible(): void
    {
        [$bon] = $this->livraisonAvecBl();
        [, $autreEntree, $autreBl] = $this->livraisonAvecBl();

        // L'identifiant de la pièce est VRAI, celui du bon aussi : seul le
        // rattachement manque. Sans ce contrôle, la route deviendrait un
        // moyen de lire les documents de n'importe quelle entrée du magasin.
        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.receptions.documents', [$bon->id, $autreEntree->id, $autreBl->id]))
            ->assertNotFound();
    }

    public function test_ia16_une_entree_non_liee_au_bon_ne_livre_pas_son_bordereau(): void
    {
        [$bon] = $this->livraisonAvecBl();

        // Une entrée libre (sans commande) : le magasin en a des dizaines.
        $libre = Entree::factory()->validee()->create(['magasin_id' => $this->magasin->id]);

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.receptions.bordereau', [$bon->id, $libre->id]))
            ->assertNotFound();
    }

    public function test_ia16_sans_la_permission_documents_d_achat_rien_ne_sort(): void
    {
        [$bon, $entree, $bl] = $this->livraisonAvecBl();

        // Il voit la fiche du bon, mais pas ses pièces : la frontière est la
        // permission du MODULE CONSULTÉ, pas celle du module d'origine.
        $sansPieces = $this->utilisateur(['achat.bons_commande.index'], 'sans-pieces@example.com');

        $this->actingAs($sansPieces)
            ->get(route('achat.bons-commande.receptions.documents', [$bon->id, $entree->id, $bl->id]))
            ->assertForbidden();

        $this->actingAs($sansPieces)
            ->get(route('achat.bons-commande.receptions.bordereau', [$bon->id, $entree->id]))
            ->assertForbidden();
    }

    public function test_meme_un_magasinier_passe_par_les_permissions_d_achat(): void
    {
        [$bon, $entree, $bl] = $this->livraisonAvecBl();

        // Tous les droits Stock, aucun droit Achat : la route d'Achat refuse.
        // Le proxy n'est pas une seconde porte vers le magasin.
        $magasinier = User::create([
            'name' => 'Magasinier', 'last_name' => 'Test', 'user_name' => 'magasinier_br3',
            'email' => 'magasinier-br3@example.com', 'password' => bcrypt('password'),
        ]);
        $magasinier->assignRole('Magasinier');

        $this->actingAs($magasinier)
            ->get(route('achat.bons-commande.receptions.documents', [$bon->id, $entree->id, $bl->id]))
            ->assertForbidden();
    }

    // ── Pierre tombale et cas dégradés ─────────────────────────────────────

    public function test_une_piece_supprimee_repond_410_et_non_404(): void
    {
        [$bon, $entree, $bl] = $this->livraisonAvecBl();

        $bl->forceFill([
            'chemin' => null,
            'est_supprime' => true,
            'motif_suppression' => 'Numérisation illisible, remplacée',
            'supprime_le' => now(),
        ])->save();

        // 410 et non 404 : la pièce a EXISTÉ. L'acheteur doit savoir qu'elle a
        // été retirée, sinon il croit à un oubli du magasin.
        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.receptions.documents', [$bon->id, $entree->id, $bl->id]))
            ->assertStatus(410);
    }

    public function test_le_bordereau_n_existe_pas_pour_une_entree_en_brouillon(): void
    {
        $bon = BonCommande::factory()->valide()->create();

        $brouillon = Entree::factory()->create([
            'magasin_id' => $this->magasin->id,
            'bon_commande_id' => $bon->id,
        ]);

        // Un livreur ne signe pas une intention : pas de bordereau avant la
        // validation du bon d'entrée.
        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.receptions.bordereau', [$bon->id, $brouillon->id]))
            ->assertNotFound();
    }

    // ── L'onglet Réceptions : ce que la fiche montre ───────────────────────

    public function test_la_carte_de_reception_porte_le_bordereau_et_les_pieces(): void
    {
        [$bon, $entree, $bl] = $this->livraisonAvecBl();

        $this->actingAs($this->acheteur());

        $receptions = app(ReceptionsBonCommande::class)->pour($bon);
        $carte = $receptions['integrees']->first();

        $this->assertNotNull($carte['url_bordereau']);
        $this->assertCount(1, $carte['documents']);
        $this->assertSame('Bordereau du fournisseur', $carte['documents']->first()['type_label']);
        $this->assertTrue($carte['documents']->first()['peut_telecharger']);

        // Aucune URL du module Stock ne doit fuiter dans la fiche.
        $this->assertStringContainsString('/achat/', $carte['url_bordereau']);
        $this->assertStringContainsString('/achat/', $carte['documents']->first()['url_telechargement']);
        $this->assertStringNotContainsString('/stock/', $carte['documents']->first()['url_telechargement']);
    }

    public function test_sans_la_permission_les_liens_ne_sont_pas_rendus(): void
    {
        [$bon] = $this->livraisonAvecBl();

        $this->actingAs($this->utilisateur(['achat.bons_commande.index'], 'liste-seule@example.com'));

        $carte = app(ReceptionsBonCommande::class)->pour($bon)['integrees']->first();

        // Le drapeau vient du SERVEUR : l'écran n'a rien à déduire.
        $this->assertNull($carte['url_bordereau']);
        $this->assertFalse($carte['documents']->first()['peut_telecharger']);
        $this->assertNull($carte['documents']->first()['url_telechargement']);
    }

    public function test_la_fiche_affiche_les_liens_de_bordereau(): void
    {
        [$bon] = $this->livraisonAvecBl();

        $this->actingAs($this->acheteur())
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->assertSee('Bordereau de réception')
            ->assertSee('js-bordereau', false);
    }

    public function test_l_indicateur_bl_joint_signale_les_entrees_en_cours(): void
    {
        $bon = BonCommande::factory()->valide()->create();

        $sansBl = Entree::factory()->create([
            'magasin_id' => $this->magasin->id,
            'bon_commande_id' => $bon->id,
        ]);

        $avecBl = Entree::factory()->create([
            'magasin_id' => $this->magasin->id,
            'bon_commande_id' => $bon->id,
        ]);
        $this->deposerBl($avecBl);

        $this->actingAs($this->acheteur());

        $enCours = app(ReceptionsBonCommande::class)->pour($bon)['en_cours']->keyBy('id');

        $this->assertFalse($enCours[$sansBl->id]['bl_joint']);
        $this->assertTrue($enCours[$avecBl->id]['bl_joint']);
    }

    public function test_une_photo_de_livraison_ne_vaut_pas_bl_joint(): void
    {
        $bon = BonCommande::factory()->valide()->create();

        $entree = Entree::factory()->create([
            'magasin_id' => $this->magasin->id,
            'bon_commande_id' => $bon->id,
        ]);
        $this->deposerBl($entree, Document::TYPE_PHOTO_LIVRAISON);

        $this->actingAs($this->acheteur());

        // Le typage de BR-01 sert précisément à cela : une photo de colis
        // n'est pas le bordereau du fournisseur.
        $this->assertFalse(
            app(ReceptionsBonCommande::class)->pour($bon)['en_cours']->first()['bl_joint']
        );
    }

    // ── Chronologie et dégradation ─────────────────────────────────────────

    public function test_la_chronologie_relaie_le_depot_du_bl_au_magasin(): void
    {
        [$bon, $entree] = $this->livraisonAvecBl();

        $phrases = app(ChronologieBonCommande::class)->pour($bon)->pluck('phrase');

        $this->assertTrue(
            $phrases->contains(fn ($phrase) => str_contains($phrase, 'Bordereau du fournisseur joint au magasin')),
            'La chronologie doit relayer le dépôt du BL : '.$phrases->implode(' | ')
        );
        $this->assertTrue(
            $phrases->contains(fn ($phrase) => str_contains($phrase, (string) $entree->numero)),
            'La phrase doit nommer le bon d\'entrée concerné.'
        );
    }

    public function test_la_chronologie_reste_chronologique_apres_la_fusion(): void
    {
        [$bon] = $this->livraisonAvecBl();

        $quand = app(ChronologieBonCommande::class)->pour($bon)->pluck('quand');

        $this->assertSame(
            $quand->map(fn ($date) => $date->timestamp)->sort()->values()->all(),
            $quand->map(fn ($date) => $date->timestamp)->values()->all(),
            'Fusionner deux journaux ne doit pas désordonner l\'histoire du bon.'
        );
    }

    public function test_degradation_si_les_documents_stock_sont_illisibles(): void
    {
        [$bon] = $this->livraisonAvecBl();

        \Illuminate\Support\Facades\Schema::drop('stock_documents');

        $this->actingAs($this->acheteur());

        // La fiche vit, l'onglet perd ses pièces : jamais de 500 sur un bon
        // parce que le magasin est en maintenance.
        $carte = app(ReceptionsBonCommande::class)->pour($bon)['integrees']->first();

        $this->assertTrue($carte['documents']->isEmpty());
        $this->assertCount(0, app(ChronologieBonCommande::class)->pour($bon)->where('icone', 'bi-paperclip'));

        $this->get(route('achat.bons-commande.show', $bon->id))->assertOk();
    }

    public function test_une_contre_passation_n_a_pas_de_bordereau(): void
    {
        [$bon] = $this->livraisonAvecBl();

        // Le schéma lui-même l'impose (CHECK chk_integrations_cle_selon_sens) :
        // une contre-passation porte un MOUVEMENT, jamais une entrée. Elle ne
        // peut donc pas désigner un bordereau — ce qui est cohérent : elle
        // défait une livraison, elle n'en atteste pas.
        IntegrationReception::create([
            'bon_commande_id' => $bon->id,
            'mouvement_id' => 4242,
            'sens' => IntegrationReception::SENS_CONTRE_PASSATION,
            'reference' => 'MVT-4242',
            'detail' => [],
        ]);

        $this->actingAs($this->acheteur());

        $cartes = app(ReceptionsBonCommande::class)->pour($bon)['integrees'];
        $contrePassation = $cartes->firstWhere('est_contre_passation', true);

        $this->assertNotNull($contrePassation, 'La contre-passation doit figurer dans les cartes.');
        $this->assertNull($contrePassation['url_bordereau']);
        $this->assertTrue($contrePassation['documents']->isEmpty());
    }
}
