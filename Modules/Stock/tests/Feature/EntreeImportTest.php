<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Database\Factories\ParcInfoDeTest;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\TamponEquipement;
use Modules\Stock\Services\TamponService;
use Tests\TestCase;

/**
 * Commit D — import CSV/collage (MD-IMPORT) : parsing, rapport en 4
 * catégories, application sur les rangées vides avec le même code de
 * contrôle que la saisie unitaire.
 */
class EntreeImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Entree $entree;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);

        $this->user = User::create([
            'name' => 'Magasinier', 'last_name' => 'Test', 'user_name' => 'magasinier',
            'email' => 'magasinier@example.com', 'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('Magasinier');

        // Bon en référencement : 1 ligne modèle × 4 → 4 rangées vides
        $this->entree = Entree::factory()->create(['magasin_id' => Magasin::factory()->create()->id]);
        LigneEntree::factory()->create([
            'entree_id' => $this->entree->id,
            'article_id' => Article::factory()->equipement()->create()->id,
            'quantite' => 4,
        ]);
        app(TamponService::class)->passerEnReferencement($this->entree);
        $this->entree->refresh();
    }

    public function test_le_parsing_trim_et_dedoublonne(): void
    {
        $service = app(TamponService::class);

        $numeros = $service->parserContenu("  SN-1  \nSN-2;\n\n\nSN-1\r\nSN-3,\n   \n");

        $this->assertSame(['SN-1', 'SN-2', 'SN-3'], $numeros);
    }

    public function test_analyse_classe_en_quatre_categories(): void
    {
        // 1 doublon tampon (rangée déjà saisie), 1 déjà connu ParcInfo, 5 candidats pour 3 rangées vides
        TamponEquipement::query()
            ->whereIn('ligne_entree_id', $this->entree->lignes()->select('id'))
            ->orderBy('id')->first()
            ->update(['numero_serie' => 'SN-DEJA-SAISI']);

        $connu = ParcInfoDeTest::equipement(['numero_serie' => 'SN-PARCINFO']);

        $contenu = implode("\n", [
            'SN-DEJA-SAISI',   // doublon tampon (ligne 1)
            'SN-PARCINFO',     // déjà connu
            'SN-OK-1', 'SN-OK-2', 'SN-OK-3', // acceptés (3 rangées vides)
            'SN-EN-TROP',      // en trop
        ]);

        $reponse = $this->actingAs($this->user)
            ->postJson(route('stock.entrees.wizard.import', $this->entree->id), [
                'mode' => 'analyser',
                'contenu' => $contenu,
            ])
            ->assertOk()
            ->assertJsonPath('mode', 'analyser');

        $rapport = $reponse->json('rapport');
        $this->assertSame(['SN-OK-1', 'SN-OK-2', 'SN-OK-3'], $rapport['acceptes']);
        $this->assertSame('SN-DEJA-SAISI', $rapport['doublons_tampon'][0]['numero']);
        $this->assertSame('Déjà saisi ligne 1', $rapport['doublons_tampon'][0]['detail']);
        $this->assertSame('SN-PARCINFO', $rapport['deja_connus'][0]['numero']);
        $this->assertStringContainsString($connu->code_inventaire, $rapport['deja_connus'][0]['detail']);
        $this->assertSame('SN-EN-TROP', $rapport['en_trop'][0]['numero']);

        // L'analyse n'écrit RIEN
        $this->assertSame(1, TamponEquipement::query()->whereNotNull('numero_serie')->count());
    }

    public function test_appliquer_remplit_les_rangees_vides_dans_l_ordre(): void
    {
        $reponse = $this->actingAs($this->user)
            ->postJson(route('stock.entrees.wizard.import', $this->entree->id), [
                'mode' => 'appliquer',
                'contenu' => "SN-A\nSN-B",
            ])
            ->assertOk()
            ->assertJsonPath('progression.saisis', 2)
            ->assertJsonPath('progression.total', 4);

        $this->assertSame(['SN-A', 'SN-B'], $reponse->json('rapport.acceptes'));

        $tampons = TamponEquipement::query()
            ->whereIn('ligne_entree_id', $this->entree->lignes()->select('id'))
            ->orderBy('id')->pluck('numero_serie')->all();

        $this->assertSame(['SN-A', 'SN-B', null, null], $tampons);
    }

    public function test_appliquer_n_ecrase_jamais_une_rangee_saisie(): void
    {
        TamponEquipement::query()
            ->whereIn('ligne_entree_id', $this->entree->lignes()->select('id'))
            ->orderBy('id')->first()
            ->update(['numero_serie' => 'SN-MANUEL']);

        $this->actingAs($this->user)
            ->postJson(route('stock.entrees.wizard.import', $this->entree->id), [
                'mode' => 'appliquer',
                'contenu' => "SN-I1\nSN-I2\nSN-I3",
            ])
            ->assertOk()
            ->assertJsonPath('progression.saisis', 4);

        $tampons = TamponEquipement::query()
            ->whereIn('ligne_entree_id', $this->entree->lignes()->select('id'))
            ->orderBy('id')->pluck('numero_serie')->all();

        $this->assertSame(['SN-MANUEL', 'SN-I1', 'SN-I2', 'SN-I3'], $tampons);
    }

    public function test_import_par_fichier_csv(): void
    {
        $fichier = UploadedFile::fake()->createWithContent('series.csv', "SN-CSV-1\nSN-CSV-2\n");

        $this->actingAs($this->user)
            ->post(route('stock.entrees.wizard.import', $this->entree->id), [
                'mode' => 'appliquer',
                'fichier' => $fichier,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('progression.saisis', 2);

        $this->assertDatabaseHas('stock_tampon_equipements', ['numero_serie' => 'SN-CSV-1']);
    }

    public function test_import_vide_ou_blanc_refuse(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('stock.entrees.wizard.import', $this->entree->id), ['mode' => 'analyser'])
            ->assertStatus(422);

        // Uniquement des blancs : TrimStrings + ConvertEmptyStringsToNull
        // ramènent le collage à null → même refus 422 que le vide
        $this->actingAs($this->user)
            ->postJson(route('stock.entrees.wizard.import', $this->entree->id), [
                'mode' => 'appliquer',
                'contenu' => "   \n\n  \n",
            ])
            ->assertStatus(422);

        // Des lignes vides autour d'un numéro : seul le numéro est appliqué
        $reponse = $this->actingAs($this->user)
            ->postJson(route('stock.entrees.wizard.import', $this->entree->id), [
                'mode' => 'appliquer',
                'contenu' => "\n\n  SN-SEUL  \n\n",
            ])
            ->assertOk();

        $this->assertSame(['SN-SEUL'], $reponse->json('rapport.acceptes'));
        $this->assertSame(1, TamponEquipement::query()->whereNotNull('numero_serie')->count());
    }

    public function test_import_refuse_hors_referencement(): void
    {
        app(TamponService::class)->retourBrouillon($this->entree);

        $this->actingAs($this->user)
            ->postJson(route('stock.entrees.wizard.import', $this->entree->id), [
                'mode' => 'appliquer',
                'contenu' => 'SN-X',
            ])
            ->assertStatus(409);
    }

    public function test_doublon_interne_au_collage_compte_une_seule_fois(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('stock.entrees.wizard.import', $this->entree->id), [
                'mode' => 'appliquer',
                'contenu' => "SN-DUP\nSN-DUP\nSN-DUP",
            ])
            ->assertOk()
            ->assertJsonPath('progression.saisis', 1);

        $this->assertSame(1, TamponEquipement::query()->where('numero_serie', 'SN-DUP')->count());
    }
}
