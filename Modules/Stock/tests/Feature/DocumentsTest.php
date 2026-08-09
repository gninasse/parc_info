<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\User;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Models\Transfert;
use Tests\TestCase;

/**
 * Diligence 5 — pièces jointes des bons : envoi, téléchargement contrôlé,
 * suppression, gardes (bon validé, permissions, formats).
 */
class DocumentsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Entree $entree;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Document::DISQUE);

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);

        $this->user = User::create([
            'name' => 'Magasinier', 'last_name' => 'Test', 'user_name' => 'magasinier',
            'email' => 'magasinier@example.com', 'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('Magasinier');

        $this->entree = Entree::factory()->create(['magasin_id' => Magasin::factory()->create()->id]);
    }

    private function url(string $suffixe = ''): string
    {
        return "/stock/documents/entrees/{$this->entree->id}{$suffixe}";
    }

    public function test_ajout_de_plusieurs_pieces_jointes(): void
    {
        $reponse = $this->actingAs($this->user)
            ->post($this->url(), [
                'fichiers' => [
                    UploadedFile::fake()->create('bon-livraison.pdf', 120, 'application/pdf'),
                    UploadedFile::fake()->image('colis.png'),
                ],
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertStringContainsString('2 pièce(s) jointe(s)', $reponse->json('message'));
        $this->assertSame(2, $this->entree->documents()->count());

        // Les fichiers sont bien sur le disque PRIVÉ
        $this->entree->documents->each(
            fn (Document $doc) => Storage::disk(Document::DISQUE)->assertExists($doc->chemin)
        );

        $pdf = $this->entree->documents()->where('nom_original', 'bon-livraison.pdf')->first();
        $this->assertSame('application/pdf', $pdf->mime);
        $this->assertSame($this->user->id, $pdf->created_by);
        $this->assertTrue($pdf->estPdf());
    }

    public function test_liste_et_telechargement_controle(): void
    {
        $this->actingAs($this->user)->post($this->url(), [
            'fichiers' => [UploadedFile::fake()->create('facture.pdf', 50, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertOk();

        $piece = $this->entree->documents()->first();

        // La liste renvoie les métadonnées et l'URL de téléchargement
        $donnees = $this->actingAs($this->user)
            ->getJson($this->url())
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $donnees);
        $this->assertSame('facture.pdf', $donnees[0]['nom']);
        $this->assertSame(route('stock.documents.download', ['entrees', $this->entree->id, $piece->id]), $donnees[0]['url']);

        // Téléchargement : le fichier sort par la route, jamais par une URL publique
        $this->actingAs($this->user)
            ->get($this->url("/{$piece->id}"))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=facture.pdf');
    }

    public function test_suppression_efface_aussi_le_fichier(): void
    {
        $this->actingAs($this->user)->post($this->url(), [
            'fichiers' => [UploadedFile::fake()->create('a-supprimer.pdf', 10, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertOk();

        $piece = $this->entree->documents()->first();
        $chemin = $piece->chemin;

        $this->actingAs($this->user)
            ->deleteJson($this->url("/{$piece->id}"))
            ->assertOk();

        $this->assertSame(0, $this->entree->documents()->count());
        Storage::disk(Document::DISQUE)->assertMissing($chemin);
    }

    public function test_bon_valide_verrouille_les_pieces_jointes(): void
    {
        $this->actingAs($this->user)->post($this->url(), [
            'fichiers' => [UploadedFile::fake()->create('avant-validation.pdf', 10, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertOk();

        $piece = $this->entree->documents()->first();
        $chemin = $piece->chemin;
        $this->entree->forceFill(['statut' => Entree::STATUT_VALIDE])->save();

        // Plus aucun ajout après validation : le dossier est figé.
        $this->actingAs($this->user)
            ->post($this->url(), ['fichiers' => [UploadedFile::fake()->create('apres.pdf', 10, 'application/pdf')]], ['Accept' => 'application/json'])
            ->assertStatus(409);

        // La suppression n'est plus un refus sec : depuis la reprise des
        // bordereaux, elle exige un motif et laisse une pierre tombale.
        $this->actingAs($this->user)
            ->deleteJson($this->url("/{$piece->id}"))
            ->assertStatus(422)
            ->assertJsonValidationErrors('motif');

        $this->assertTrue($piece->fresh()->exists, 'La pièce ne doit pas disparaître sans motif.');
        Storage::disk(Document::DISQUE)->assertExists($chemin);

        // … et la consultation reste ouverte tant que rien n'a été supprimé.
        $this->actingAs($this->user)->getJson($this->url())->assertOk();
        $this->actingAs($this->user)->get($this->url("/{$piece->id}"))->assertOk();
    }

    public function test_formats_et_taille_controles(): void
    {
        $this->actingAs($this->user)
            ->post($this->url(), [
                'fichiers' => [UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload')],
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $tailleMax = (int) config('stock.documents.taille_max_ko');
        $this->actingAs($this->user)
            ->post($this->url(), [
                'fichiers' => [UploadedFile::fake()->create('enorme.pdf', $tailleMax + 100, 'application/pdf')],
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertSame(0, $this->entree->documents()->count());
    }

    public function test_permissions_par_type_de_document(): void
    {
        $lecteur = User::create([
            'name' => 'L', 'last_name' => 'T', 'user_name' => 'lecteur',
            'email' => 'lecteur@example.com', 'password' => bcrypt('x'),
        ]);
        $lecteur->givePermissionTo('stock.entrees.index');

        // Lecture autorisée, écriture refusée
        $this->actingAs($lecteur)->getJson($this->url())->assertOk();
        $this->actingAs($lecteur)
            ->post($this->url(), ['fichiers' => [UploadedFile::fake()->create('x.pdf', 5, 'application/pdf')]], ['Accept' => 'application/json'])
            ->assertStatus(403);

        // Une permission d'un AUTRE type de bon ne donne rien sur les entrées
        $autre = User::create([
            'name' => 'A', 'last_name' => 'T', 'user_name' => 'autre',
            'email' => 'autre@example.com', 'password' => bcrypt('x'),
        ]);
        $autre->givePermissionTo(['stock.sorties.index', 'stock.sorties.store']);

        $this->actingAs($autre)->getJson($this->url())->assertStatus(403);
        $this->actingAs($autre)
            ->post($this->url(), ['fichiers' => [UploadedFile::fake()->create('y.pdf', 5, 'application/pdf')]], ['Accept' => 'application/json'])
            ->assertStatus(403);

        // Invité → login
        $this->app['auth']->forgetGuards();
        $this->get($this->url())->assertRedirect(route('login'));
    }

    public function test_les_trois_types_de_bons_acceptent_des_pieces(): void
    {
        $magasin = Magasin::factory()->create();
        $sortie = Sortie::factory()->create(['magasin_id' => $magasin->id]);
        $transfert = Transfert::factory()->create([
            'magasin_source_id' => $magasin->id,
            'magasin_cible_id' => Magasin::factory()->create()->id,
        ]);

        $this->user->givePermissionTo(['stock.sorties.store', 'stock.transferts.store']);

        foreach ([['sorties', $sortie], ['transferts', $transfert]] as [$type, $bon]) {
            $this->actingAs($this->user)
                ->post("/stock/documents/{$type}/{$bon->id}", [
                    'fichiers' => [UploadedFile::fake()->create("piece-{$type}.pdf", 10, 'application/pdf')],
                ], ['Accept' => 'application/json'])
                ->assertOk();

            $this->assertSame(1, $bon->documents()->count(), $type);
        }
    }

    public function test_supprimer_le_bon_emporte_ses_pieces(): void
    {
        $this->actingAs($this->user)->post($this->url(), [
            'fichiers' => [UploadedFile::fake()->create('avec-le-bon.pdf', 10, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertOk();

        $chemin = $this->entree->documents()->first()->chemin;

        $this->user->givePermissionTo('stock.entrees.destroy');
        $this->actingAs($this->user)
            ->deleteJson(route('stock.entrees.destroy', $this->entree->id))
            ->assertOk();

        $this->assertSame(0, Document::query()->count());
        Storage::disk(Document::DISQUE)->assertMissing($chemin);
    }

    public function test_type_de_bon_inconnu_404(): void
    {
        $this->actingAs($this->user)
            ->getJson("/stock/documents/inventaires/{$this->entree->id}")
            ->assertStatus(404);
    }
}
