<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Achat\Models\BonCommande;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Services\TamponService;
use Tests\TestCase;

/**
 * BR-01 et BR-02 — les bordereaux de réception.
 *
 * BR-01 : le BL papier du livreur, numérisé et TYPÉ (il ne se confond plus
 * avec une photo de colis), avec pierre tombale après validation.
 * BR-02 : le bordereau PDF signable — la pièce qu'on fait contresigner AU
 * LIVREUR, sans laquelle une réclamation ne pèse rien.
 */
class BordereauxReceptionTest extends TestCase
{
    use RefreshDatabase;

    private User $magasinier;

    private Magasin $magasin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);
        $this->seed(\Modules\Achat\Database\Seeders\AchatPermissionsSeeder::class);
        $this->seed(\Modules\Achat\Database\Seeders\AchatParametresSeeder::class);

        $this->magasinier = User::create([
            'name' => 'Salamata Magasinier', 'last_name' => 'Test', 'user_name' => 'magasinier',
            'email' => 'magasinier@example.com', 'password' => bcrypt('password'),
        ]);
        $this->magasinier->assignRole('Magasinier');

        $this->magasin = Magasin::factory()->create();

        Storage::fake(Document::DISQUE);
    }

    private function entree(array $surcharges = []): Entree
    {
        $entree = Entree::factory()->create(array_merge([
            'magasin_id' => $this->magasin->id,
            'reference_externe' => 'BL-2026-7788',
        ], $surcharges));

        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => Article::factory()->consommable()->create(['nom' => 'Toner 26A'])->id,
            'quantite' => 6,
            'cout_unitaire' => 42000,
        ]);

        return $entree->refresh();
    }

    /**
     * Un bon de commande VALIDÉ portant l'article d'une entrée : sans cette
     * correspondance, l'intégration Achat refuserait la validation (« cet
     * article n'est pas sur la commande ») — et le test mesurerait autre
     * chose que la garde du bordereau.
     */
    private function entreeLiee(): array
    {
        $article = Article::factory()->consommable()->create(['nom' => 'Toner 26A']);

        $bon = BonCommande::factory()->valide()->create();

        \Modules\Achat\Models\LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'designation' => $article->nom,
            'nature' => 'consommable',
            'quantite' => 10,
            'quantite_livree' => 0,
            'prix_unitaire_ht' => 42000,
        ]);

        $entree = Entree::factory()->create([
            'magasin_id' => $this->magasin->id,
            'reference_externe' => 'BL-2026-7788',
            'bon_commande_id' => $bon->id,
            'fournisseur_id' => $bon->fournisseur_id,
        ]);

        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => $article->id,
            'quantite' => 6,
            'cout_unitaire' => 42000,
        ]);

        return [$bon->refresh(), $entree->refresh()];
    }

    private function deposer(Entree $entree, string $type = Document::TYPE_BL_FOURNISSEUR)
    {
        return $this->actingAs($this->magasinier)
            ->post(route('stock.documents.store', ['entrees', $entree->id]), [
                'type' => $type,
                'fichiers' => [UploadedFile::fake()->create('bl-livreur.pdf', 120, 'application/pdf')],
            ]);
    }

    private function valider(Entree $entree, string $jeton = 'jeton-br')
    {
        return $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.valider', $entree->id), ['jeton' => $jeton]);
    }

    // ═══ BR-01 — le BL fournisseur, typé ════════════════════════════════════

    public function test_une_piece_se_depose_avec_sa_nature(): void
    {
        $entree = $this->entree();

        $this->deposer($entree)->assertOk();

        $document = Document::query()->firstOrFail();
        $this->assertSame(Document::TYPE_BL_FOURNISSEUR, $document->type);
        $this->assertTrue($document->estBlFournisseur());
        $this->assertSame('Bordereau du fournisseur', $document->type_label);
    }

    public function test_une_nature_inconnue_est_refusee(): void
    {
        $entree = $this->entree();

        $this->actingAs($this->magasinier)
            ->postJson(route('stock.documents.store', ['entrees', $entree->id]), [
                'type' => 'selfie_du_livreur',
                'fichiers' => [UploadedFile::fake()->create('piece.pdf', 10, 'application/pdf')],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    /** Sans type, la pièce reste « autre » : les dépôts existants survivent. */
    public function test_un_depot_sans_type_reste_valide(): void
    {
        $entree = $this->entree();

        $this->actingAs($this->magasinier)
            ->post(route('stock.documents.store', ['entrees', $entree->id]), [
                'fichiers' => [UploadedFile::fake()->create('photo.jpg', 50, 'image/jpeg')],
            ])
            ->assertOk();

        $this->assertSame(Document::TYPE_AUTRE, Document::query()->firstOrFail()->type);
    }

    public function test_la_liste_expose_la_nature_et_le_drapeau_bl(): void
    {
        $entree = $this->entree();
        $this->deposer($entree);

        $donnees = $this->actingAs($this->magasinier)
            ->getJson(route('stock.documents.index', ['entrees', $entree->id]))
            ->assertOk()
            ->json('data');

        $this->assertTrue($donnees[0]['est_bl']);
        $this->assertSame('Bordereau du fournisseur', $donnees[0]['type_label']);
    }

    // ═══ BR-01 — la pierre tombale après validation ════════════════════════

    public function test_avant_validation_la_suppression_est_reelle(): void
    {
        $entree = $this->entree();
        $this->deposer($entree);
        $document = Document::query()->firstOrFail();
        $chemin = $document->chemin;

        $this->actingAs($this->magasinier)
            ->deleteJson(route('stock.documents.destroy', ['entrees', $entree->id, $document->id]))
            ->assertOk();

        $this->assertDatabaseMissing('stock_documents', ['id' => $document->id]);
        Storage::disk(Document::DISQUE)->assertMissing($chemin);
    }

    public function test_apres_validation_la_suppression_sans_motif_est_refusee(): void
    {
        $entree = $this->entree();
        $this->deposer($entree);
        $this->valider($entree)->assertOk();

        $document = Document::query()->firstOrFail();

        $this->actingAs($this->magasinier)
            ->deleteJson(route('stock.documents.destroy', ['entrees', $entree->id, $document->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['motif']);

        $this->assertFalse($document->fresh()->est_supprime);
        Storage::disk(Document::DISQUE)->assertExists($document->chemin);
    }

    public function test_apres_validation_la_suppression_motivee_pose_une_pierre_tombale(): void
    {
        $entree = $this->entree();
        $this->deposer($entree);
        $this->valider($entree)->assertOk();

        $document = Document::query()->firstOrFail();
        $chemin = $document->chemin;

        $this->actingAs($this->magasinier)
            ->deleteJson(route('stock.documents.destroy', ['entrees', $entree->id, $document->id]), [
                'motif' => 'Scan illisible, remplacé par une nouvelle numérisation.',
            ])
            ->assertOk()
            ->assertJsonPath('data.est_supprime', true);

        $document->refresh();

        // La LIGNE demeure — c'est tout l'objet de la pierre tombale.
        $this->assertTrue($document->est_supprime);
        $this->assertSame($this->magasinier->id, $document->supprime_par);
        $this->assertNotNull($document->supprime_le);
        $this->assertStringContainsString('Scan illisible', $document->libelle_pierre_tombale);

        // Le FICHIER, lui, a disparu.
        $this->assertNull($document->chemin);
        Storage::disk(Document::DISQUE)->assertMissing($chemin);
    }

    public function test_une_pierre_tombale_ne_se_telecharge_plus(): void
    {
        $entree = $this->entree();
        $this->deposer($entree);
        $this->valider($entree)->assertOk();

        $document = Document::query()->firstOrFail();

        $this->actingAs($this->magasinier)
            ->deleteJson(route('stock.documents.destroy', ['entrees', $entree->id, $document->id]), [
                'motif' => 'Pièce erronée.',
            ])->assertOk();

        $this->actingAs($this->magasinier)
            ->get(route('stock.documents.download', ['entrees', $entree->id, $document->id]))
            ->assertStatus(410);
    }

    // ═══ BR-01 — la garde PARAMÉTRABLE ═════════════════════════════════════

    /** Par défaut : le quai passe avant la paperasse, rien ne bloque. */
    public function test_par_defaut_une_entree_liee_se_valide_sans_bordereau(): void
    {
        config(['stock.bl_obligatoire_si_commande' => false]);

        [, $entree] = $this->entreeLiee();

        $this->valider($entree)->assertOk();
    }

    public function test_le_parametre_actif_exige_le_bordereau_sur_une_entree_liee(): void
    {
        config(['stock.bl_obligatoire_si_commande' => true]);

        [, $entree] = $this->entreeLiee();

        $reponse = $this->valider($entree)->assertUnprocessable();
        $this->assertStringContainsString('Joignez le bordereau du fournisseur', $reponse->json('message'));

        // Rien n'a été écrit : la validation a échoué en entier.
        $this->assertSame(Entree::STATUT_BROUILLON, $entree->fresh()->statut);

        // Avec le BL, elle passe.
        $this->deposer($entree)->assertOk();
        $this->valider($entree, 'jeton-avec-bl')->assertOk();
    }

    /** La garde ne concerne QUE les entrées liées à une commande. */
    public function test_le_parametre_actif_ne_bloque_pas_une_entree_libre(): void
    {
        config(['stock.bl_obligatoire_si_commande' => true]);

        $this->valider($this->entree())->assertOk();
    }

    /** Une pierre tombale ne satisfait pas la garde : le fichier n'existe plus. */
    public function test_un_bl_supprime_ne_satisfait_plus_la_garde(): void
    {
        [, $entree] = $this->entreeLiee();
        $this->deposer($entree);

        Document::query()->firstOrFail()->forceFill([
            'est_supprime' => true,
            'supprime_le' => now(),
            'motif_suppression' => 'Retiré.',
            'chemin' => null,
        ])->save();

        config(['stock.bl_obligatoire_si_commande' => true]);

        $this->valider($entree)->assertUnprocessable();
    }

    // ═══ BR-02 — le bordereau PDF ══════════════════════════════════════════

    public function test_le_bordereau_n_existe_pas_avant_validation(): void
    {
        $entree = $this->entree();

        $this->actingAs($this->magasinier)
            ->get(route('stock.entrees.bordereau-reception', $entree->id))
            ->assertNotFound();
    }

    public function test_le_bordereau_exige_la_permission_de_lecture(): void
    {
        $entree = $this->entree();
        $this->valider($entree)->assertOk();

        $sansDroit = User::create([
            'name' => 'Sans droit', 'last_name' => 'Test', 'user_name' => 'sans_droit_br',
            'email' => 'sans-droit-br@example.com', 'password' => bcrypt('password'),
        ]);

        $this->actingAs($sansDroit)
            ->get(route('stock.entrees.bordereau-reception', $entree->id))
            ->assertForbidden();
    }

    public function test_le_bordereau_se_genere_apres_validation(): void
    {
        $entree = $this->entree();
        $this->valider($entree)->assertOk();

        $reponse = $this->actingAs($this->magasinier)
            ->get(route('stock.entrees.bordereau-reception', $entree->id))
            ->assertOk();

        $this->assertStringContainsString('application/pdf', $reponse->headers->get('content-type'));
        // dompdf rend un corps complet (pas un flux) : on lit le contenu.
        $this->assertStringStartsWith('%PDF', $reponse->getContent());
    }

    /**
     * LE critère de BR-02 : pas de montants sur un document de quai. Le
     * livreur n'a pas à lire les prix négociés par l'établissement.
     */
    public function test_les_couts_sont_absents_du_bordereau_par_defaut(): void
    {
        config(['stock.afficher_couts_bordereau' => false]);

        $entree = $this->entree();
        $this->valider($entree)->assertOk();

        $html = $this->rendreBordereau($entree);

        $this->assertStringNotContainsString('Coût unitaire', $html);
        $this->assertStringNotContainsString('42 000', $html);
        $this->assertStringContainsString('sans mention de coûts', $html);
    }

    public function test_les_couts_apparaissent_si_l_etablissement_le_demande(): void
    {
        config(['stock.afficher_couts_bordereau' => true]);

        $entree = $this->entree();
        $this->valider($entree)->assertOk();

        $html = $this->rendreBordereau($entree);

        $this->assertStringContainsString('Coût unitaire', $html);
        $this->assertStringContainsString('42 000', $html);
    }

    public function test_le_bordereau_porte_le_bc_lie_et_le_numero_de_bl(): void
    {
        [$bon, $entree] = $this->entreeLiee();
        $this->valider($entree)->assertOk();

        $html = $this->rendreBordereau($entree);

        // Magasinier et livreur doivent voir la même référence de commande.
        $this->assertStringContainsString($bon->numero, $html);
        $this->assertStringContainsString('BL-2026-7788', $html);
        $this->assertStringContainsString($entree->fresh()->numero, $html);
    }

    /**
     * Le bordereau part chez le fournisseur : il doit porter l'établissement,
     * comme les autres imprimés du Stock, et non le nom technique de
     * l'application (config('app.name') vaut « Laravel » tant que le
     * déploiement ne l'a pas renseigné — un bon de livraison signé « Laravel »
     * n'a aucune valeur pour le contradictoire).
     */
    public function test_le_bordereau_porte_l_etablissement_et_pas_le_nom_technique(): void
    {
        config(['app.name' => 'Laravel']);

        $entree = $this->entree();
        $this->valider($entree)->assertOk();

        $html = $this->rendreBordereau($entree);

        $this->assertStringContainsString('CHU-YO', $html);
        $this->assertStringNotContainsString('Laravel', $html);
    }

    public function test_le_bordereau_porte_les_deux_cadres_de_signature(): void
    {
        $entree = $this->entree();
        $this->valider($entree)->assertOk();

        $html = $this->rendreBordereau($entree);

        // Sans contradiction, une réclamation ultérieure ne pèse rien.
        $this->assertStringContainsString('Le magasinier', $html);
        $this->assertStringContainsString('Le livreur', $html);
        $this->assertStringContainsString($this->magasinier->name, $html);
    }

    public function test_le_bordereau_liste_les_numeros_de_serie_en_annexe(): void
    {
        $entree = Entree::factory()->create(['magasin_id' => $this->magasin->id]);

        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => Article::factory()->equipement()->create(['modele' => 'ProBook 450'])->id,
            'quantite' => 2,
            'cout_unitaire' => 350000,
        ]);

        app(TamponService::class)->passerEnReferencement($entree);

        \Modules\Stock\Models\TamponEquipement::query()
            ->whereIn('ligne_entree_id', $entree->lignes()->select('id'))
            ->orderBy('id')->get()
            ->each(fn ($tampon, $i) => $tampon->update(['numero_serie' => 'SN-BORD-'.($i + 1)]));

        $this->valider($entree->refresh())->assertOk();

        $html = $this->rendreBordereau($entree);

        $this->assertStringContainsString('Annexe', $html);
        $this->assertStringContainsString('SN-BORD-1', $html);
        $this->assertStringContainsString('SN-BORD-2', $html);
    }

    public function test_le_bordereau_signale_le_bl_joint(): void
    {
        $entree = $this->entree();
        $this->deposer($entree);
        $this->valider($entree)->assertOk();

        $this->assertStringContainsString('bl-livreur.pdf', $this->rendreBordereau($entree));
    }

    /** Un contre-mouvement doit se voir sur le document qui circule. */
    public function test_une_contre_passation_ajoute_son_filigrane(): void
    {
        $entree = $this->entree();
        $this->valider($entree)->assertOk();

        $mouvement = Mouvement::query()->where('entree_id', $entree->id)->firstOrFail();

        app(\Modules\Stock\Services\MouvementService::class)->contreMouvement(
            $mouvement,
            'Erreur de comptage au quai.',
            $this->magasinier->id
        );

        $html = $this->rendreBordereau($entree);

        $this->assertStringContainsString('CONTRE-PASSÉ PARTIELLEMENT', $html);
        $this->assertStringContainsString('Erreur de comptage au quai.', $html);
    }

    /** Le HTML du gabarit, sans passer par le rendu PDF (plus lisible). */
    private function rendreBordereau(Entree $entree): string
    {
        $entree = $entree->fresh([
            'magasin', 'fournisseur', 'lignes.article', 'createur', 'valideur', 'bonCommande',
        ]);

        $controleur = new \ReflectionClass(\Modules\Stock\Http\Controllers\BordereauReceptionController::class);
        $instance = $controleur->newInstance();

        $unites = $controleur->getMethod('unitesSerialisees');
        $unites->setAccessible(true);

        $contre = $controleur->getMethod('contrePassation');
        $contre->setAccessible(true);

        return view('stock::pdf.bordereau_reception', [
            'entree' => $entree,
            'quantitatives' => $entree->lignes->filter(
                fn ($ligne) => $ligne->article_id !== null && $ligne->article?->nature !== 'equipement'
            ),
            'unites' => $unites->invoke($instance, $entree),
            'blFournisseurs' => $entree->documents()
                ->where('est_supprime', false)
                ->where('type', Document::TYPE_BL_FOURNISSEUR)
                ->get(),
            'afficherCouts' => (bool) config('stock.afficher_couts_bordereau', false),
            'contrePassation' => $contre->invoke($instance, $entree),
            'qr' => null,
            'genereLe' => now(),
        ])->render();
    }
}
