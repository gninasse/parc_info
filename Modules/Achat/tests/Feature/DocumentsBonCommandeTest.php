<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\Document;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\AchatParametres;
use Modules\Achat\Services\ChronologieBonCommande;
use Modules\Achat\Services\DocumentsBonCommande;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\Activity;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-09 — pièces justificatives (M-05, M-08, IA-13, A16).
 *
 * Les critères durs :
 *
 *   - un utilisateur sans `documents.view` ne télécharge RIEN, même avec
 *     l'URL exacte (IA-13) ;
 *   - avant validation du bon : suppression réelle ; après : PIERRE TOMBALE
 *     motivée et signée, fichier physique effacé, ligne conservée (A16).
 */
class DocumentsBonCommandeTest extends TestCase
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

    /** Le gestionnaire documentaire complet. */
    private function gestionnaire(): User
    {
        return $this->utilisateur([
            'achat.bons_commande.index',
            'achat.documents.store',
            'achat.documents.view',
            'achat.documents.delete',
        ], 'gestionnaire-docs@example.com');
    }

    private function bon(string $statut = BonCommande::STATUT_BROUILLON): BonCommande
    {
        $bon = $statut === BonCommande::STATUT_BROUILLON
            ? BonCommande::factory()->create()
            : BonCommande::factory()->valide()->create();

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->create()->id,
        ]);

        return $bon;
    }

    private function deposer(BonCommande $bon, ?User $par = null, string $type = 'bc_signe'): Document
    {
        return app(DocumentsBonCommande::class)->deposer(
            $bon,
            UploadedFile::fake()->create('bc-signe.pdf', 120, 'application/pdf'),
            $type,
            $par ?? $this->gestionnaire()
        );
    }

    // ═══ M-05 : le dépôt ════════════════════════════════════════════════════

    public function test_le_depot_exige_la_permission_store(): void
    {
        $bon = $this->bon();
        $lecteur = $this->utilisateur(['achat.bons_commande.index'], 'lecteur-docs@example.com');

        $this->actingAs($lecteur)
            ->post(route('achat.bons-commande.documents.store', $bon->id), [
                'type' => 'bc_signe',
                'fichier' => UploadedFile::fake()->create('piece.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden()
            ->assertSee('achat.documents.store');
    }

    public function test_le_depot_range_le_fichier_sous_un_nom_neutre_hors_racine(): void
    {
        $bon = $this->bon();

        $this->actingAs($this->gestionnaire())
            ->post(route('achat.bons-commande.documents.store', $bon->id), [
                'type' => 'facture_proforma',
                'fichier' => UploadedFile::fake()->create('Facture N°42 (finale).pdf', 100, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonPath('data.type_label', 'Facture pro forma');

        $document = Document::query()->firstOrFail();

        // Le nom d'origine, potentiellement hostile, ne touche pas le disque.
        $this->assertStringNotContainsString('Facture', $document->chemin);
        $this->assertStringStartsWith(DocumentsBonCommande::DOSSIER."/{$bon->id}/", $document->chemin);
        $this->assertSame('Facture N°42 (finale).pdf', $document->nom_original);
        Storage::disk(DocumentsBonCommande::DISQUE)->assertExists($document->chemin);
    }

    public function test_le_depot_refuse_un_format_hors_liste(): void
    {
        $bon = $this->bon();

        $this->actingAs($this->gestionnaire())
            ->post(route('achat.bons-commande.documents.store', $bon->id), [
                'type' => 'autre',
                'fichier' => UploadedFile::fake()->create('script.sh', 10, 'application/x-sh'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fichier']);
    }

    /** La taille max est un PARAMÈTRE (A-08), pas une constante. */
    public function test_la_taille_max_est_le_parametre_de_l_etablissement(): void
    {
        $bon = $this->bon();
        app(AchatParametres::class)->set('taille_max_piece_mo', 1);

        $this->actingAs($this->gestionnaire())
            ->post(route('achat.bons-commande.documents.store', $bon->id), [
                'type' => 'autre',
                // 2 Mo > 1 Mo paramétré
                'fichier' => UploadedFile::fake()->create('gros.pdf', 2048, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fichier']);
    }

    public function test_un_type_inconnu_est_refuse(): void
    {
        $bon = $this->bon();

        $this->actingAs($this->gestionnaire())
            ->post(route('achat.bons-commande.documents.store', $bon->id), [
                'type' => 'selfie',
                'fichier' => UploadedFile::fake()->create('piece.pdf', 100, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_un_bon_annule_a_un_dossier_fige(): void
    {
        $bon = BonCommande::factory()->annule()->create();

        $this->actingAs($this->gestionnaire())
            ->postJson(route('achat.bons-commande.documents.store', $bon->id), [
                'type' => 'autre',
                'fichier' => UploadedFile::fake()->create('piece.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(409);
    }

    public function test_le_depot_est_journalise_et_raconte_dans_la_chronologie(): void
    {
        $bon = $this->bon();
        $this->deposer($bon);

        $activite = Activity::query()
            ->forModule('achat')
            ->where('description', DocumentsBonCommande::EVENEMENT_DEPOT)
            ->first();

        $this->assertNotNull($activite);
        $this->assertSame('bc-signe.pdf', $activite->properties->get('nom_original'));

        $chronologie = app(ChronologieBonCommande::class)->pour($bon);
        $this->assertStringContainsString('Pièce jointe déposée', $chronologie->last()['phrase']);
    }

    // ═══ IA-13 : le téléchargement contrôlé ═════════════════════════════════

    public function test_sans_documents_view_le_telechargement_est_un_403_meme_avec_l_url(): void
    {
        $bon = $this->bon();
        $document = $this->deposer($bon);

        // Il a l'index (il voit la fiche) mais pas documents.view.
        $lecteur = $this->utilisateur(['achat.bons_commande.index'], 'lecteur-docs@example.com');

        $this->actingAs($lecteur)
            ->get(route('achat.bons-commande.documents.telecharger', [$bon->id, $document->id]))
            ->assertForbidden()
            ->assertSee('achat.documents.view');
    }

    public function test_avec_documents_view_le_fichier_est_servi_sous_son_nom_d_origine(): void
    {
        $bon = $this->bon();
        $document = $this->deposer($bon);

        $this->actingAs($this->gestionnaire())
            ->get(route('achat.bons-commande.documents.telecharger', [$bon->id, $document->id]))
            ->assertOk()
            ->assertDownload('bc-signe.pdf');
    }

    /** Un document ne se télécharge pas via le bon d'un AUTRE : 404. */
    public function test_un_document_est_introuvable_depuis_un_autre_bon(): void
    {
        $bon = $this->bon();
        $document = $this->deposer($bon);
        $autre = $this->bon();

        $this->actingAs($this->gestionnaire())
            ->get(route('achat.bons-commande.documents.telecharger', [$autre->id, $document->id]))
            ->assertNotFound();
    }

    // ═══ La suppression : réelle avant, pierre tombale après (A16) ══════════

    public function test_avant_validation_la_suppression_est_reelle(): void
    {
        $bon = $this->bon(); // brouillon
        $document = $this->deposer($bon);
        $chemin = $document->chemin;

        $this->actingAs($this->gestionnaire())
            ->deleteJson(route('achat.bons-commande.documents.destroy', [$bon->id, $document->id]))
            ->assertOk();

        $this->assertDatabaseMissing('achat_documents', ['id' => $document->id]);
        Storage::disk(DocumentsBonCommande::DISQUE)->assertMissing($chemin);
    }

    public function test_apres_validation_la_suppression_sans_motif_est_refusee(): void
    {
        $bon = $this->bon(BonCommande::STATUT_VALIDE);
        $document = $this->deposer($bon);

        $this->actingAs($this->gestionnaire())
            ->deleteJson(route('achat.bons-commande.documents.destroy', [$bon->id, $document->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['motif']);

        // Rien n'a bougé : ni la ligne, ni le fichier.
        $this->assertFalse($document->fresh()->est_supprime);
        Storage::disk(DocumentsBonCommande::DISQUE)->assertExists($document->chemin);
    }

    public function test_apres_validation_la_suppression_motivee_pose_une_pierre_tombale(): void
    {
        $bon = $this->bon(BonCommande::STATUT_VALIDE);
        $document = $this->deposer($bon);
        $chemin = $document->chemin;
        $gestionnaire = $this->gestionnaire();

        $this->actingAs($gestionnaire)
            ->deleteJson(route('achat.bons-commande.documents.destroy', [$bon->id, $document->id]), [
                'motif' => 'Mauvais fichier : c\'était la facture d\'un autre bon.',
            ])
            ->assertOk()
            ->assertJsonPath('data.est_supprime', true);

        $document->refresh();

        // La ligne DEMEURE — motivée, signée, horodatée (A16).
        $this->assertTrue($document->est_supprime);
        $this->assertSame($gestionnaire->id, $document->supprime_par);
        $this->assertNotNull($document->supprime_le);
        $this->assertSame('Mauvais fichier : c\'était la facture d\'un autre bon.', $document->motif_suppression);
        $this->assertStringContainsString('Mauvais fichier', $document->libelle_pierre_tombale);

        // Le fichier physique, lui, est bien EFFACÉ.
        $this->assertNull($document->chemin);
        Storage::disk(DocumentsBonCommande::DISQUE)->assertMissing($chemin);

        // Et l'acte est au journal + dans la chronologie, en rouge.
        $chronologie = app(ChronologieBonCommande::class)->pour($bon);
        $this->assertStringContainsString('Pièce supprimée après validation', $chronologie->last()['phrase']);
        $this->assertSame('danger', $chronologie->last()['couleur']);
    }

    public function test_une_pierre_tombale_ne_se_telecharge_plus(): void
    {
        $bon = $this->bon(BonCommande::STATUT_VALIDE);
        $document = $this->deposer($bon);
        app(DocumentsBonCommande::class)->poserPierreTombale($document, $this->gestionnaire(), 'Pièce erronée.');

        $this->actingAs($this->gestionnaire())
            ->get(route('achat.bons-commande.documents.telecharger', [$bon->id, $document->id]))
            ->assertStatus(410);
    }

    public function test_une_pierre_tombale_ne_se_supprime_pas_deux_fois(): void
    {
        $bon = $this->bon(BonCommande::STATUT_VALIDE);
        $document = $this->deposer($bon);
        app(DocumentsBonCommande::class)->poserPierreTombale($document, $this->gestionnaire(), 'Pièce erronée.');

        $this->actingAs($this->gestionnaire())
            ->deleteJson(route('achat.bons-commande.documents.destroy', [$bon->id, $document->id]), [
                'motif' => 'Encore une fois.',
            ])
            ->assertStatus(409);
    }

    public function test_la_suppression_exige_sa_permission_dediee(): void
    {
        $bon = $this->bon();
        $document = $this->deposer($bon);

        $deposant = $this->utilisateur(
            ['achat.bons_commande.index', 'achat.documents.store', 'achat.documents.view'],
            'deposant-docs@example.com'
        );

        $this->actingAs($deposant)
            ->deleteJson(route('achat.bons-commande.documents.destroy', [$bon->id, $document->id]))
            ->assertForbidden()
            ->assertSee('achat.documents.delete');
    }

    // ═══ La liste de l'onglet ═══════════════════════════════════════════════

    public function test_la_liste_emet_les_drapeaux_et_la_pierre_tombale(): void
    {
        $bon = $this->bon(BonCommande::STATUT_VALIDE);
        $vivant = $this->deposer($bon);
        $mort = $this->deposer($bon, null, 'autre');
        app(DocumentsBonCommande::class)->poserPierreTombale($mort, $this->gestionnaire(), 'Doublon de la première pièce.');

        $donnees = collect(
            $this->actingAs($this->gestionnaire())
                ->getJson(route('achat.bons-commande.documents.index', $bon->id))
                ->assertOk()
                ->json('data')
        );

        $ligneVivante = $donnees->firstWhere('id', $vivant->id);
        $this->assertTrue($ligneVivante['peut_telecharger']);
        $this->assertTrue($ligneVivante['peut_supprimer']);
        $this->assertTrue($ligneVivante['suppression_motivee'], 'Le bon est engagé : la suppression doit passer par M-08.');
        $this->assertNotNull($ligneVivante['url_telechargement']);

        $ligneMorte = $donnees->firstWhere('id', $mort->id);
        $this->assertTrue($ligneMorte['est_supprime']);
        $this->assertFalse($ligneMorte['peut_telecharger']);
        $this->assertFalse($ligneMorte['peut_supprimer']);
        $this->assertNull($ligneMorte['url_telechargement']);
        $this->assertStringContainsString('Doublon', $ligneMorte['pierre_tombale']);
    }
}
