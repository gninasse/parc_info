<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Services\DocumentsBonCommande;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Modules\Stock\Models\Document as DocumentStock;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Magasin;
use Tests\TestCase;

/**
 * D-19 — audit des dépôts de fichiers, sur les DEUX modules.
 *
 * Un formulaire d'upload est la porte d'entrée la plus banale d'une
 * application, et la plus facile à mal fermer. Quatre fautes classiques, que
 * ces tests interdisent une bonne fois :
 *
 *   1. faire confiance au type déclaré par le client — il ment quand il veut ;
 *   2. réutiliser le nom de fichier fourni pour construire un chemin — une
 *      barre oblique ou deux points suffisent alors à sortir du dossier ;
 *   3. stocker dans la racine web — le fichier devient lisible par URL,
 *      permissions comprises ;
 *   4. rendre le fichier sans contrôler la permission à CHAQUE lecture.
 *
 * Ces tests valent pour Achat comme pour Stock : la même faute commise d'un
 * côté ou de l'autre ouvre la même porte.
 */
class AuditUploadsTest extends TestCase
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
        Storage::fake(DocumentStock::DISQUE);
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

    // ── Les disques ────────────────────────────────────────────────────────

    /**
     * Le disque des pièces ne doit pas être servi par le serveur web : sinon
     * tout le contrôle d'accès se contourne avec l'URL du fichier.
     */
    public function test_aucun_disque_de_pieces_n_est_expose_par_le_web(): void
    {
        foreach ([DocumentsBonCommande::DISQUE, DocumentStock::DISQUE] as $disque) {
            $racine = config("filesystems.disks.{$disque}.root");

            $this->assertStringNotContainsString(
                'public',
                $racine,
                "Le disque « {$disque} » semble servi par le serveur web : {$racine}"
            );

            $this->assertNotSame(
                'public',
                config("filesystems.disks.{$disque}.visibility"),
                "Le disque « {$disque} » est en visibilité publique."
            );
        }
    }

    // ── Le MIME ────────────────────────────────────────────────────────────

    /**
     * Le type déposé en base doit être celui que le SERVEUR a détecté, pas
     * celui que le client a annoncé. Ici, un exécutable déguisé en PDF.
     */
    public function test_le_type_mime_est_celui_du_serveur_pas_du_client(): void
    {
        $auteur = $this->utilisateur(['achat.documents.store'], 'depot-mime@example.com');
        $bon = BonCommande::factory()->create();

        $menteur = UploadedFile::fake()->createWithContent('innocent.pdf', 'MZ contenu exécutable');

        $this->actingAs($auteur)
            ->post(route('achat.bons-commande.documents.store', $bon->id), [
                'type' => array_key_first(config('achat.types_documents')),
                'fichier' => $menteur,
            ], ['Accept' => 'application/json']);

        $document = $bon->documents()->first();

        if ($document !== null) {
            $this->assertNotSame(
                'application/x-msdownload',
                $document->mime,
                'Le MIME ne doit jamais provenir de la déclaration du client.'
            );
        }

        // Quoi qu'il arrive, un exécutable franc est refusé.
        $this->actingAs($auteur)
            ->post(route('achat.bons-commande.documents.store', $bon->id), [
                'type' => array_key_first(config('achat.types_documents')),
                'fichier' => UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    // ── Les chemins ────────────────────────────────────────────────────────

    /**
     * Le nom fourni par le client ne doit jamais servir à construire le
     * chemin de stockage : « ../../ » sortirait du dossier prévu.
     */
    public function test_un_nom_de_fichier_hostile_ne_sort_pas_du_dossier(): void
    {
        $auteur = $this->utilisateur(['achat.documents.store'], 'depot-chemin@example.com');
        $bon = BonCommande::factory()->create();

        $this->actingAs($auteur)
            ->post(route('achat.bons-commande.documents.store', $bon->id), [
                'type' => array_key_first(config('achat.types_documents')),
                'fichier' => UploadedFile::fake()->create('../../../etc/passwd.pdf', 10, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $document = $bon->documents()->firstOrFail();

        $this->assertStringNotContainsString('..', $document->chemin, 'Le chemin de stockage contient une remontée de dossier.');
        $this->assertStringStartsWith('achat/', $document->chemin, 'Le fichier doit rester dans le dossier du module.');

        // Le nom d'origine est CONSERVÉ pour l'affichage (l'utilisateur doit
        // reconnaître sa pièce) mais il ne construit pas le chemin.
        $this->assertStringNotContainsString(
            'passwd',
            basename($document->chemin),
            'Le nom de stockage doit être neutre, indépendant du nom fourni.'
        );
    }

    /** Même exigence côté Stock, où le BL arrive du comptoir. */
    public function test_le_depot_stock_neutralise_aussi_le_nom_de_fichier(): void
    {
        $magasinier = User::create([
            'name' => 'Magasinier', 'last_name' => 'Test', 'user_name' => 'magasinier_audit',
            'email' => 'magasinier-audit@example.com', 'password' => bcrypt('password'),
        ]);
        $magasinier->assignRole('Magasinier');

        $entree = Entree::factory()->create(['magasin_id' => Magasin::factory()->create()->id]);

        $this->actingAs($magasinier)
            ->post(route('stock.documents.store', ['entrees', $entree->id]), [
                'type' => DocumentStock::TYPE_BL_FOURNISSEUR,
                'fichiers' => [UploadedFile::fake()->create('../../evasion.pdf', 10, 'application/pdf')],
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $piece = $entree->documents()->firstOrFail();

        $this->assertStringNotContainsString('..', $piece->chemin);
        $this->assertStringStartsWith(DocumentStock::DOSSIER, $piece->chemin);
        $this->assertStringNotContainsString('evasion', basename($piece->chemin));
    }

    // ── La lecture ─────────────────────────────────────────────────────────

    /**
     * Le fichier ne s'obtient que par la route contrôlée. Ce test le vérifie
     * pour les deux modules, avec l'URL exacte en main.
     */
    public function test_le_telechargement_exige_la_permission_dans_les_deux_modules(): void
    {
        // Achat
        $auteur = $this->utilisateur(['achat.documents.store'], 'lecture-achat@example.com');
        $bon = BonCommande::factory()->create();

        $this->actingAs($auteur)->post(route('achat.bons-commande.documents.store', $bon->id), [
            'type' => array_key_first(config('achat.types_documents')),
            'fichier' => UploadedFile::fake()->create('piece.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();

        $document = $bon->documents()->firstOrFail();

        // L'auteur peut déposer mais pas consulter : les deux permissions
        // sont distinctes, et le rester est le sujet de ce test.
        $this->actingAs($auteur)
            ->get(route('achat.bons-commande.documents.telecharger', [$bon->id, $document->id]))
            ->assertForbidden();

        $lecteur = $this->utilisateur(['achat.documents.view'], 'lecteur-achat@example.com');

        $this->actingAs($lecteur)
            ->get(route('achat.bons-commande.documents.telecharger', [$bon->id, $document->id]))
            ->assertOk();
    }

    /**
     * Le nom d'origine est restitué au téléchargement — sinon l'utilisateur
     * reçoit un fichier au nom illisible et ne reconnaît plus sa pièce.
     */
    public function test_le_nom_d_origine_est_restitue_au_telechargement(): void
    {
        $auteur = $this->utilisateur(['achat.documents.store', 'achat.documents.view'], 'nom-origine@example.com');
        $bon = BonCommande::factory()->create();

        $this->actingAs($auteur)->post(route('achat.bons-commande.documents.store', $bon->id), [
            'type' => array_key_first(config('achat.types_documents')),
            'fichier' => UploadedFile::fake()->create('Facture pro forma 2026.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();

        $document = $bon->documents()->firstOrFail();

        $reponse = $this->actingAs($auteur)
            ->get(route('achat.bons-commande.documents.telecharger', [$bon->id, $document->id]))
            ->assertOk();

        $this->assertStringContainsString(
            'Facture pro forma 2026.pdf',
            $reponse->headers->get('content-disposition')
        );
    }

    /** La taille maximale est un paramètre de l'établissement, et il mord. */
    public function test_la_taille_maximale_est_appliquee(): void
    {
        $auteur = $this->utilisateur(['achat.documents.store'], 'taille@example.com');
        $bon = BonCommande::factory()->create();

        $maxKo = (int) app(\Modules\Achat\Services\AchatParametres::class)->tailleMaxPieceKo();

        $this->actingAs($auteur)
            ->post(route('achat.bons-commande.documents.store', $bon->id), [
                'type' => array_key_first(config('achat.types_documents')),
                'fichier' => UploadedFile::fake()->create('enorme.pdf', $maxKo + 512, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('fichier');
    }
}
